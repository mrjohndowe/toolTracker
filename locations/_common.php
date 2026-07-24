<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function locations_list(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM locations';

    if ($activeOnly) {
        $sql .= ' WHERE active = 1';
    }

    $sql .= ' ORDER BY name';

    return db()->query($sql)->fetchAll();
}

function storage_bins_for_location(int $locationId): array
{
    $stmt = db()->prepare(
        'SELECT *
         FROM storage_bins
         WHERE location_id = ? AND active = 1
         ORDER BY name'
    );
    $stmt->execute([$locationId]);

    return $stmt->fetchAll();
}

function generate_transfer_number(): string
{
    return 'TR-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function find_transfer(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT
            tr.*,
            lf.name AS from_location_name,
            lt.name AS to_location_name,
            ur.username AS requested_by_name,
            ua.username AS approved_by_name,
            uv.username AS received_by_name
         FROM transfer_requests tr
         INNER JOIN locations lf ON lf.id = tr.from_location_id
         INNER JOIN locations lt ON lt.id = tr.to_location_id
         LEFT JOIN users ur ON ur.id = tr.requested_by
         LEFT JOIN users ua ON ua.id = tr.approved_by
         LEFT JOIN users uv ON uv.id = tr.received_by
         WHERE tr.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function transfer_items(int $transferId): array
{
    $stmt = db()->prepare(
        'SELECT
            ti.*,
            t.name AS tool_name,
            t.internal_id,
            t.barcode,
            fb.name AS from_bin_name,
            tb.name AS to_bin_name
         FROM transfer_items ti
         INNER JOIN tools t ON t.id = ti.tool_id
         LEFT JOIN storage_bins fb ON fb.id = ti.from_storage_bin_id
         LEFT JOIN storage_bins tb ON tb.id = ti.to_storage_bin_id
         WHERE ti.transfer_id = ?
         ORDER BY t.name'
    );
    $stmt->execute([$transferId]);

    return $stmt->fetchAll();
}

function record_custody(
    int $toolId,
    string $eventType,
    ?int $fromLocationId,
    ?int $toLocationId,
    ?int $fromBinId,
    ?int $toBinId,
    ?int $transferId,
    ?int $userId,
    ?string $notes = null
): void {
    db()->prepare(
        'INSERT INTO tool_custody_history
         (tool_id, event_type, from_location_id, to_location_id,
          from_storage_bin_id, to_storage_bin_id, transfer_id, user_id, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $toolId,
        $eventType,
        $fromLocationId,
        $toLocationId,
        $fromBinId,
        $toBinId,
        $transferId,
        $userId,
        $notes,
    ]);
}
