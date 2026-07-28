USE tooltrack;
CREATE TABLE IF NOT EXISTS inspection_templates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(150) NOT NULL,inspection_type ENUM('Checkout','Checkin','Both') NOT NULL DEFAULT 'Both',active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS inspection_questions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,template_id INT UNSIGNED NOT NULL,question_text VARCHAR(500) NOT NULL,question_type ENUM('YesNo','Text','Textarea','Select','Number','Condition') NOT NULL DEFAULT 'YesNo',options_json JSON NULL,required TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_iq_template FOREIGN KEY (template_id) REFERENCES inspection_templates(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS inspection_sessions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,inspection_type ENUM('Checkout','Checkin') NOT NULL,transaction_id BIGINT UNSIGNED NULL,checkout_item_id BIGINT UNSIGNED NULL,tool_id INT UNSIGNED NOT NULL,employee_id INT UNSIGNED NULL,template_id INT UNSIGNED NOT NULL,completed_by INT UNSIGNED NULL,overall_condition ENUM('Excellent','Good','Fair','Poor','Not Working') NULL,contents_complete TINYINT(1) NULL,working_condition TINYINT(1) NULL,notes TEXT NULL,completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_is_tool(tool_id),INDEX idx_is_transaction(transaction_id),CONSTRAINT fk_is_tool FOREIGN KEY(tool_id) REFERENCES tools(id) ON DELETE CASCADE,CONSTRAINT fk_is_employee FOREIGN KEY(employee_id) REFERENCES employees(id) ON DELETE SET NULL,CONSTRAINT fk_is_template FOREIGN KEY(template_id) REFERENCES inspection_templates(id),CONSTRAINT fk_is_user FOREIGN KEY(completed_by) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS inspection_responses (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,inspection_session_id BIGINT UNSIGNED NOT NULL,question_id INT UNSIGNED NOT NULL,answer_text TEXT NULL,answer_boolean TINYINT(1) NULL,answer_number DECIMAL(12,2) NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_ir_session FOREIGN KEY(inspection_session_id) REFERENCES inspection_sessions(id) ON DELETE CASCADE,CONSTRAINT fk_ir_question FOREIGN KEY(question_id) REFERENCES inspection_questions(id)) ENGINE=InnoDB;
INSERT INTO inspection_templates(name,inspection_type) SELECT 'Standard Box and Tool Inspection','Both' WHERE NOT EXISTS(SELECT 1 FROM inspection_templates WHERE name='Standard Box and Tool Inspection');
SET @tid=(SELECT id FROM inspection_templates WHERE name='Standard Box and Tool Inspection' LIMIT 1);
INSERT INTO inspection_questions(template_id,question_text,question_type,required,sort_order) VALUES
(@tid,'Is the correct box or case present?','YesNo',1,10),
(@tid,'Are all listed contents and accessories present?','YesNo',1,20),
(@tid,'List any missing contents or accessories.','Textarea',0,30),
(@tid,'Is the box or case damaged?','YesNo',1,40),
(@tid,'Describe any box, case, latch, hinge, or handle damage.','Textarea',0,50),
(@tid,'Is the tool or equipment clean?','YesNo',1,60),
(@tid,'Does the tool power on and operate correctly?','YesNo',1,70),
(@tid,'Are there any visible cracks, loose parts, exposed wires, leaks, or other safety concerns?','YesNo',1,80),
(@tid,'Describe any operational or safety problems.','Textarea',0,90),
(@tid,'Overall working condition','Condition',1,100);
UPDATE inspection_questions SET options_json='["Excellent","Good","Fair","Poor","Not Working"]' WHERE template_id=@tid AND question_type='Condition';
