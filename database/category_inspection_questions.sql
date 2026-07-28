USE tooltrack;

ALTER TABLE inspection_templates
    ADD COLUMN category_id INT UNSIGNED NULL AFTER name,
    ADD INDEX idx_inspection_template_category (category_id),
    ADD CONSTRAINT fk_inspection_template_category
        FOREIGN KEY (category_id) REFERENCES tool_categories(id) ON DELETE CASCADE;

-- Existing templates remain defaults because category_id is NULL.

-- Example category-specific templates. They are inserted only when those
-- categories exist and no matching template already exists.
INSERT INTO inspection_templates (name, category_id, inspection_type, active)
SELECT CONCAT(tc.name, ' Inspection'), tc.id, 'Both', 1
FROM tool_categories tc
WHERE tc.name IN ('Power Tools', 'Hand Tools', 'Safety Equipment')
  AND NOT EXISTS (
      SELECT 1 FROM inspection_templates it
      WHERE it.category_id = tc.id AND it.inspection_type = 'Both'
  );

-- Power Tools
SET @power_category = (SELECT id FROM tool_categories WHERE name='Power Tools' LIMIT 1);
SET @power_template = (SELECT id FROM inspection_templates WHERE category_id=@power_category AND inspection_type='Both' LIMIT 1);
INSERT INTO inspection_questions (template_id, question_text, question_type, options_json, required, sort_order)
SELECT @power_template, q.question_text, q.question_type, q.options_json, q.required, q.sort_order
FROM (
    SELECT 'Are the battery and charger included?' question_text, 'YesNo' question_type, NULL options_json, 1 required, 10 sort_order
    UNION ALL SELECT 'Is the power cord, battery housing, or plug damaged?', 'YesNo', NULL, 1, 20
    UNION ALL SELECT 'Does the trigger or power switch operate correctly?', 'YesNo', NULL, 1, 30
    UNION ALL SELECT 'Does the tool run without unusual noise, vibration, smoke, or odor?', 'YesNo', NULL, 1, 40
    UNION ALL SELECT 'Are guards, handles, blades, bits, and accessories secure?', 'YesNo', NULL, 1, 50
    UNION ALL SELECT 'Describe missing parts, damage, or operational problems.', 'Textarea', NULL, 0, 60
    UNION ALL SELECT 'Overall working condition', 'Condition', '["Excellent","Good","Fair","Poor","Not Working"]', 1, 100
) q
WHERE @power_template IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM inspection_questions WHERE template_id=@power_template);

-- Hand Tools
SET @hand_category = (SELECT id FROM tool_categories WHERE name='Hand Tools' LIMIT 1);
SET @hand_template = (SELECT id FROM inspection_templates WHERE category_id=@hand_category AND inspection_type='Both' LIMIT 1);
INSERT INTO inspection_questions (template_id, question_text, question_type, options_json, required, sort_order)
SELECT @hand_template, q.question_text, q.question_type, q.options_json, q.required, q.sort_order
FROM (
    SELECT 'Are all pieces in the set or box present?' question_text, 'YesNo' question_type, NULL options_json, 1 required, 10 sort_order
    UNION ALL SELECT 'Are handles secure and free of cracks or splinters?', 'YesNo', NULL, 1, 20
    UNION ALL SELECT 'Are jaws, ratchets, hinges, or adjustment mechanisms working correctly?', 'YesNo', NULL, 1, 30
    UNION ALL SELECT 'Are cutting or striking surfaces free of dangerous damage?', 'YesNo', NULL, 1, 40
    UNION ALL SELECT 'Is there excessive rust, bending, mushrooming, or wear?', 'YesNo', NULL, 1, 50
    UNION ALL SELECT 'Describe missing pieces, damage, or excessive wear.', 'Textarea', NULL, 0, 60
    UNION ALL SELECT 'Overall working condition', 'Condition', '["Excellent","Good","Fair","Poor","Not Working"]', 1, 100
) q
WHERE @hand_template IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM inspection_questions WHERE template_id=@hand_template);

-- Safety Equipment
SET @safety_category = (SELECT id FROM tool_categories WHERE name='Safety Equipment' LIMIT 1);
SET @safety_template = (SELECT id FROM inspection_templates WHERE category_id=@safety_category AND inspection_type='Both' LIMIT 1);
INSERT INTO inspection_questions (template_id, question_text, question_type, options_json, required, sort_order)
SELECT @safety_template, q.question_text, q.question_type, q.options_json, q.required, q.sort_order
FROM (
    SELECT 'Is the equipment clean and sanitary?' question_text, 'YesNo' question_type, NULL options_json, 1 required, 10 sort_order
    UNION ALL SELECT 'Are straps, buckles, clips, seals, and adjustment points intact?', 'YesNo', NULL, 1, 20
    UNION ALL SELECT 'Are there cracks, tears, punctures, chemical damage, or contamination?', 'YesNo', NULL, 1, 30
    UNION ALL SELECT 'Is the certification or expiration date still valid?', 'YesNo', NULL, 1, 40
    UNION ALL SELECT 'Does the equipment fit and function as designed?', 'YesNo', NULL, 1, 50
    UNION ALL SELECT 'Describe contamination, damage, missing components, or expiration concerns.', 'Textarea', NULL, 0, 60
    UNION ALL SELECT 'Overall working condition', 'Condition', '["Excellent","Good","Fair","Poor","Not Working"]', 1, 100
) q
WHERE @safety_template IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM inspection_questions WHERE template_id=@safety_template);
