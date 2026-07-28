<?php
declare(strict_types=1);

require_once __DIR__ . '/../checkout/_common.php';

/**
 * Return an application-relative route. Pass this value to redirect().
 */
function inspection_path(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

/**
 * Return a browser-ready URL including the configured application folder.
 */
function inspection_public_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . inspection_path($path);
}

function inspection_template_for(string $type, ?int $categoryId = null): ?array
{
    // Prefer a category-specific template, then fall back to a global template.
    // Within each level, an exact Checkout/Checkin template outranks Both.
    $stmt = db()->prepare(
        'SELECT it.*, tc.name AS category_name
         FROM inspection_templates it
         LEFT JOIN tool_categories tc ON tc.id = it.category_id
         WHERE it.active = 1
           AND it.inspection_type IN (?, "Both")
           AND (it.category_id = ? OR it.category_id IS NULL)
         ORDER BY
           (it.category_id = ?) DESC,
           (it.inspection_type = ?) DESC,
           it.id ASC
         LIMIT 1'
    );
    $stmt->execute([$type, $categoryId, $categoryId, $type]);
    $template = $stmt->fetch();

    return is_array($template) ? $template : null;
}

function inspection_template_for_tool(string $type, int $toolId): ?array
{
    $stmt = db()->prepare('SELECT category_id FROM tools WHERE id = ? LIMIT 1');
    $stmt->execute([$toolId]);
    $categoryId = $stmt->fetchColumn();

    return inspection_template_for(
        $type,
        $categoryId !== false && $categoryId !== null ? (int)$categoryId : null
    );
}

function inspection_questions(int $templateId): array
{
    $stmt = db()->prepare(
        'SELECT *
         FROM inspection_questions
         WHERE template_id = ? AND active = 1
         ORDER BY sort_order, id'
    );
    $stmt->execute([$templateId]);

    return $stmt->fetchAll();
}

function inspection_create_queue(string $type, array $items, string $returnPath): string
{
    $key = bin2hex(random_bytes(16));

    $_SESSION['inspection_queues'][$key] = [
        'type' => $type,
        'items' => array_values($items),
        'index' => 0,
        'return_path' => inspection_path($returnPath),
        'created_at' => time(),
    ];

    return inspection_path('/inspections/queue.php?queue=' . rawurlencode($key));
}

function inspection_answer_value(array $question, mixed $value): array
{
    $text = null;
    $boolean = null;
    $number = null;

    if ($question['question_type'] === 'YesNo') {
        $boolean = (string)$value === '1' ? 1 : 0;
    } elseif ($question['question_type'] === 'Number') {
        $number = $value === '' ? null : (float)$value;
    } else {
        $text = trim((string)$value);
    }

    return [$text, $boolean, $number];
}

function save_inspection(array $item, array $answers, ?int $userId, ?string $notes): int
{
    $type = (string)$item['type'];
    $template = inspection_template_for_tool($type, (int)$item['tool_id']);

    if ($template === null) {
        throw new RuntimeException('No active inspection template is configured.');
    }

    $questions = inspection_questions((int)$template['id']);
    $questionMap = [];

    foreach ($questions as $question) {
        $questionId = (int)$question['id'];
        $questionMap[$questionId] = $question;
        $value = $answers[$questionId] ?? null;

        if ((int)$question['required'] === 1 && ($value === null || $value === '')) {
            throw new RuntimeException('Please answer: ' . $question['question_text']);
        }
    }

    $overallCondition = null;
    $contentsComplete = null;
    $workingCondition = null;

    foreach ($questions as $question) {
        $value = $answers[(int)$question['id']] ?? null;
        $questionText = strtolower((string)$question['question_text']);

        if ($question['question_type'] === 'Condition' && $value !== null && $value !== '') {
            $overallCondition = (string)$value;
        }

        if (str_contains($questionText, 'all listed contents')) {
            $contentsComplete = ($value === null || $value === '')
                ? null
                : ((string)$value === '1' ? 1 : 0);
        }

        if (str_contains($questionText, 'power on and operate')) {
            $workingCondition = ($value === null || $value === '')
                ? null
                : ((string)$value === '1' ? 1 : 0);
        }
    }

    $pdo = db();
    $pdo->prepare(
        'INSERT INTO inspection_sessions
         (inspection_type, transaction_id, checkout_item_id, tool_id,
          employee_id, template_id, completed_by, overall_condition,
          contents_complete, working_condition, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $type,
        $item['transaction_id'] ?? null,
        $item['checkout_item_id'] ?? null,
        $item['tool_id'],
        $item['employee_id'] ?? null,
        $template['id'],
        $userId,
        $overallCondition,
        $contentsComplete,
        $workingCondition,
        $notes,
    ]);

    $sessionId = (int)$pdo->lastInsertId();
    $insertResponse = $pdo->prepare(
        'INSERT INTO inspection_responses
         (inspection_session_id, question_id, answer_text, answer_boolean, answer_number)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($answers as $questionId => $value) {
        $questionId = (int)$questionId;

        if (!isset($questionMap[$questionId])) {
            continue;
        }

        [$text, $boolean, $number] = inspection_answer_value($questionMap[$questionId], $value);
        $insertResponse->execute([$sessionId, $questionId, $text, $boolean, $number]);
    }

    if ($type === 'Checkin') {
        finalize_checkin_from_inspection(
            $item,
            $overallCondition,
            $contentsComplete,
            $workingCondition,
            $notes,
            $userId
        );
    }

    return $sessionId;
}

function finalize_checkin_from_inspection(
    array $item,
    ?string $condition,
    ?int $contentsComplete,
    ?int $workingCondition,
    ?string $notes,
    ?int $userId
): void {
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT ci.*, t.status AS tool_status, t.tool_condition,
                t.name, t.barcode
         FROM checkout_items ci
         INNER JOIN tools t ON t.id = ci.tool_id
         WHERE ci.id = ? AND ci.return_status = "Pending"
         FOR UPDATE'
    );
    $stmt->execute([(int)$item['checkout_item_id']]);
    $row = $stmt->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('This item was already returned.');
    }

    $condition = $condition ?: 'Good';
    $toolStatus = 'Available';
    $disposition = 'Returned to inventory';

    if ($workingCondition === 0 || $condition === 'Not Working') {
        $toolStatus = 'Repair';
        $disposition = 'Returned and routed to repair';
    } elseif ($contentsComplete === 0 || $condition === 'Poor') {
        $toolStatus = 'Inspection';
        $disposition = 'Returned and held for inspection';
    }

    $inspectionNotes = trim(implode("\n", array_filter([
        $disposition,
        $notes,
    ])));

    // A physical return is always recorded as Returned. Repair and Inspection
    // describe the tool's next location/status, not whether it was returned.
    $pdo->prepare(
        'UPDATE checkout_items
         SET returned_at = NOW(),
             return_condition = ?,
             return_status = "Returned",
             inspection_notes = ?,
             returned_by = ?
         WHERE id = ?'
    )->execute([
        $condition,
        $inspectionNotes !== '' ? $inspectionNotes : null,
        $userId,
        $row['id'],
    ]);

    $pdo->prepare(
        'UPDATE tools
         SET status = ?, tool_condition = ?
         WHERE id = ?'
    )->execute([$toolStatus, $condition, $row['tool_id']]);

    $pdo->prepare(
        'INSERT INTO tool_status_history
         (tool_id, old_status, new_status, old_condition,
          new_condition, notes, changed_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $row['tool_id'],
        $row['tool_status'],
        $toolStatus,
        $row['tool_condition'],
        $condition,
        $inspectionNotes !== '' ? $inspectionNotes : 'Returned after required inspection',
        $userId,
    ]);

    record_scan(
        'Tool Return',
        (string)$row['barcode'],
        true,
        $disposition,
        (int)($item['employee_id'] ?? 0),
        (int)$row['tool_id'],
        (int)$row['transaction_id']
    );

    // checkout/_common.php is now always loaded, so this runs on the inspection
    // request and closes the transaction as soon as no Pending items remain.
    update_transaction_status((int)$row['transaction_id']);
}

function inspection_render_field(array $question): string
{
    $id = (int)$question['id'];
    $name = 'answers[' . $id . ']';
    $required = (int)$question['required'] === 1 ? ' required' : '';
    $type = (string)$question['question_type'];

    if ($type === 'YesNo') {
        return '<select name="' . e($name) . '"' . $required . '>'
            . '<option value="">Select</option>'
            . '<option value="1">Yes</option>'
            . '<option value="0">No</option>'
            . '</select>';
    }

    if ($type === 'Textarea') {
        return '<textarea name="' . e($name) . '" rows="3"' . $required . '></textarea>';
    }

    if ($type === 'Number') {
        return '<input type="number" step="0.01" name="' . e($name) . '"' . $required . '>';
    }

    if ($type === 'Select' || $type === 'Condition') {
        $options = json_decode((string)($question['options_json'] ?? '[]'), true) ?: [];
        $html = '<select name="' . e($name) . '"' . $required . '>'
            . '<option value="">Select</option>';

        foreach ($options as $option) {
            $html .= '<option value="' . e((string)$option) . '">'
                . e((string)$option)
                . '</option>';
        }

        return $html . '</select>';
    }

    return '<input name="' . e($name) . '"' . $required . '>';
}
