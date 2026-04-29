USE english_learning_app;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('user','teacher','admin') NOT NULL DEFAULT 'user' AFTER password,
    ADD COLUMN IF NOT EXISTS status ENUM('active','locked') NOT NULL DEFAULT 'active' AFTER role,
    ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) NULL AFTER last_login_at,
    ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER avatar;

ALTER TABLE vocabulary_sets
    ADD COLUMN IF NOT EXISTS status ENUM('active','pending','hidden','deleted') NOT NULL DEFAULT 'active' AFTER visibility,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS approved_by INT UNSIGNED NULL AFTER deleted_at,
    ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by,
    ADD COLUMN IF NOT EXISTS category_id INT UNSIGNED NULL AFTER approved_at,
    ADD COLUMN IF NOT EXISTS level ENUM('beginner','elementary','intermediate','upper_intermediate','advanced') NOT NULL DEFAULT 'beginner' AFTER category_id;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS set_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    set_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_set_tag (set_id, tag_id),
    CONSTRAINT fk_set_tags_set FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id) ON DELETE CASCADE,
    CONSTRAINT fk_set_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS flashcard_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    flashcard_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_flashcard_tag (flashcard_id, tag_id),
    CONSTRAINT fk_flashcard_tags_flashcard FOREIGN KEY (flashcard_id) REFERENCES flashcards(id) ON DELETE CASCADE,
    CONSTRAINT fk_flashcard_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    target_role ENUM('all','user','teacher','admin') NOT NULL DEFAULT 'all',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcements_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('bug','content_error','suggestion','other') NOT NULL DEFAULT 'other',
    target_type ENUM('set','flashcard','class','system') NOT NULL DEFAULT 'system',
    target_id INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    screenshot_path VARCHAR(255) NULL,
    status ENUM('open','reviewing','resolved','rejected') NOT NULL DEFAULT 'open',
    admin_reply TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE feedback
    ADD COLUMN IF NOT EXISTS screenshot_path VARCHAR(255) NULL AFTER message;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    target_type VARCHAR(80) NOT NULL,
    target_id INT UNSIGNED NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    ip_address VARCHAR(100) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_action (action),
    INDEX idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('text','number','boolean','json') NOT NULL DEFAULT 'text',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(80) NOT NULL DEFAULT 'bi-award',
    condition_type VARCHAR(80) NOT NULL,
    condition_value INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_achievement (user_id, achievement_id),
    CONSTRAINT fk_user_achievements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_achievements_achievement FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS study_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    mode VARCHAR(50) NOT NULL,
    set_id INT UNSIGNED NULL,
    cards_studied INT NOT NULL DEFAULT 0,
    duration_seconds INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_study_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_study_sessions_set FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_goals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    daily_cards_goal INT NOT NULL DEFAULT 20,
    daily_minutes_goal INT NOT NULL DEFAULT 10,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_goals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_sets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    set_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_set (user_id, set_id),
    CONSTRAINT fk_saved_sets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_sets_set FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    set_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    due_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignments_class FOREIGN KEY (class_id) REFERENCES learning_classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_set FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    test_result_id INT UNSIGNED NULL,
    status ENUM('not_started','in_progress','completed','late') NOT NULL DEFAULT 'not_started',
    completed_at DATETIME NULL,
    UNIQUE KEY uq_assignment_submission (assignment_id, user_id),
    CONSTRAINT fk_assignment_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_submissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_submissions_test FOREIGN KEY (test_result_id) REFERENCES test_results(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password, role, status, created_at)
VALUES
('Admin User', 'admin@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', 'admin', 'active', NOW()),
('Teacher User', 'teacher@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', 'teacher', 'active', NOW())
ON DUPLICATE KEY UPDATE role = VALUES(role), status = VALUES(status);

UPDATE users SET role = 'user', status = 'active' WHERE role IS NULL OR role = '';
UPDATE vocabulary_sets SET status = 'active', approved_at = COALESCE(approved_at, NOW()) WHERE is_public = 1 AND status = 'active';

INSERT INTO categories (name, slug, description) VALUES
('General English', 'general-english', 'Từ vựng tiếng Anh thông dụng'),
('Business', 'business', 'Từ vựng công việc và kinh doanh'),
('Travel', 'travel', 'Từ vựng du lịch')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO tags (name, slug) VALUES
('daily', 'daily'),
('work', 'work'),
('travel', 'travel'),
('beginner', 'beginner')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO achievements (code, name, description, icon, condition_type, condition_value) VALUES
('first_set', 'First Set', 'Tạo bộ từ đầu tiên.', 'bi-collection', 'sets_created', 1),
('first_test', 'First Test', 'Hoàn thành bài test đầu tiên.', 'bi-ui-checks-grid', 'tests_completed', 1),
('cards_100', '100 Cards Learned', 'Học 100 flashcards.', 'bi-lightning-charge', 'cards_learned', 100),
('perfect_score', 'Perfect Score', 'Đạt 100% trong bài test.', 'bi-trophy', 'perfect_score', 100),
('streak_7', '7 Day Streak', 'Học liên tục 7 ngày.', 'bi-calendar-check', 'streak_days', 7)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'English Learning App', 'text'),
('site_tagline', 'Học tiếng Anh chủ động mỗi ngày', 'text'),
('registration_enabled', '1', 'boolean'),
('public_set_moderation', '0', 'boolean'),
('maintenance_mode', '0', 'boolean'),
('default_test_questions', '20', 'number'),
('blast_default_seconds', '60', 'number'),
('contact_email', 'support@example.com', 'text'),
('footer_text', 'English Learning App', 'text'),
('leaderboard_enabled', '1', 'boolean'),
('feedback_enabled', '1', 'boolean'),
('public_library_enabled', '1', 'boolean')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type);
