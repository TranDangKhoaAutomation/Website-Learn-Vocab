CREATE DATABASE IF NOT EXISTS english_learning_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE english_learning_app;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS test_results;
DROP TABLE IF EXISTS user_progress;
DROP TABLE IF EXISTS vocabulary_set_permissions;
DROP TABLE IF EXISTS flashcards;
DROP TABLE IF EXISTS vocabulary_sets;
DROP TABLE IF EXISTS class_members;
DROP TABLE IF EXISTS learning_classes;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE learning_classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    invite_code VARCHAR(24) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_learning_classes_owner
        FOREIGN KEY (owner_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE class_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('owner', 'member') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_member (class_id, user_id),
    CONSTRAINT fk_class_members_class
        FOREIGN KEY (class_id) REFERENCES learning_classes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_class_members_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vocabulary_sets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    visibility ENUM('private', 'public', 'class') NOT NULL DEFAULT 'private',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vocabulary_sets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_vocabulary_sets_class
        FOREIGN KEY (class_id) REFERENCES learning_classes(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE flashcards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    set_id INT UNSIGNED NOT NULL,
    term VARCHAR(190) NOT NULL,
    definition VARCHAR(255) NOT NULL,
    example_sentence TEXT NULL,
    pronunciation VARCHAR(120) NULL,
    image_url VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_flashcards_set
        FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vocabulary_set_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    set_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('viewer', 'editor', 'admin') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_set_permission (set_id, user_id),
    CONSTRAINT fk_set_permissions_set
        FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_set_permissions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    set_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NOT NULL,
    mode VARCHAR(50) NOT NULL,
    correct_count INT UNSIGNED NOT NULL DEFAULT 0,
    wrong_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_answer TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_progress (user_id, set_id, card_id, mode),
    CONSTRAINT fk_user_progress_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_progress_set
        FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_progress_card
        FOREIGN KEY (card_id) REFERENCES flashcards(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    set_id INT UNSIGNED NOT NULL,
    score INT UNSIGNED NOT NULL DEFAULT 0,
    total_questions INT UNSIGNED NOT NULL DEFAULT 0,
    mode VARCHAR(50) NOT NULL DEFAULT 'test',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_test_results_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_test_results_set
        FOREIGN KEY (set_id) REFERENCES vocabulary_sets(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, name, email, password, created_at) VALUES
(1, 'Trần Đăng Khoa', 'khoa@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', NOW()),
(2, 'Student User', 'student@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', NOW());

INSERT INTO learning_classes (id, owner_id, name, description, invite_code, created_at) VALUES
(1, 1, 'English A1 Class', 'Lớp học mẫu dành cho người mới bắt đầu.', 'KHOA-A1', NOW());

INSERT INTO class_members (class_id, user_id, role, created_at) VALUES
(1, 1, 'owner', NOW()),
(1, 2, 'member', NOW());

INSERT INTO vocabulary_sets (id, user_id, class_id, title, description, is_public, visibility, created_at, updated_at) VALUES
(1, 1, NULL, 'Basic English Vocabulary', 'Những từ vựng tiếng Anh cơ bản dùng hằng ngày.', 1, 'public', NOW(), NOW()),
(2, 1, NULL, 'Business English', 'Từ vựng thường gặp trong công việc, họp hành và giao tiếp với khách hàng.', 0, 'private', NOW(), NOW()),
(3, 1, 1, 'Travel English', 'Từ vựng cần thiết khi đi du lịch, đặt phòng và di chuyển.', 0, 'class', NOW(), NOW());

INSERT INTO flashcards (set_id, term, definition, example_sentence, pronunciation, image_url, created_at) VALUES
(1, 'apple', 'quả táo', 'I eat an apple every morning.', '/ˈæp.əl/', '', NOW()),
(1, 'book', 'quyển sách', 'This book is very interesting.', '/bʊk/', '', NOW()),
(1, 'water', 'nước', 'Please drink more water.', '/ˈwɔː.tər/', '', NOW()),
(1, 'school', 'trường học', 'My brother goes to school by bus.', '/skuːl/', '', NOW()),
(1, 'teacher', 'giáo viên', 'The teacher explains the lesson clearly.', '/ˈtiː.tʃər/', '', NOW()),
(1, 'improve', 'cải thiện', 'You can improve your English by practicing every day.', '/ɪmˈpruːv/', '', NOW()),
(1, 'family', 'gia đình', 'My family has dinner together every night.', '/ˈfæm.əl.i/', '', NOW()),
(1, 'friend', 'bạn bè', 'She is my best friend.', '/frend/', '', NOW()),
(1, 'listen', 'lắng nghe', 'Listen carefully to the pronunciation.', '/ˈlɪs.ən/', '', NOW()),
(1, 'write', 'viết', 'Write your answer in the blank.', '/raɪt/', '', NOW()),

(2, 'meeting', 'cuộc họp', 'We have a meeting at ten o clock.', '/ˈmiː.tɪŋ/', '', NOW()),
(2, 'customer', 'khách hàng', 'The customer asked for more information.', '/ˈkʌs.tə.mər/', '', NOW()),
(2, 'business', 'kinh doanh', 'She studies business at university.', '/ˈbɪz.nɪs/', '', NOW()),
(2, 'schedule', 'lịch trình', 'Please check the project schedule.', '/ˈskedʒ.uːl/', '', NOW()),
(2, 'invoice', 'hóa đơn', 'The invoice was sent by email.', '/ˈɪn.vɔɪs/', '', NOW()),
(2, 'budget', 'ngân sách', 'We need to reduce the marketing budget.', '/ˈbʌdʒ.ɪt/', '', NOW()),
(2, 'deadline', 'hạn chót', 'The deadline is next Friday.', '/ˈded.laɪn/', '', NOW()),
(2, 'contract', 'hợp đồng', 'They signed the contract yesterday.', '/ˈkɑːn.trækt/', '', NOW()),
(2, 'manager', 'quản lý', 'The manager approved the plan.', '/ˈmæn.ə.dʒər/', '', NOW()),
(2, 'presentation', 'bài thuyết trình', 'Her presentation was clear and professional.', '/ˌprez.ənˈteɪ.ʃən/', '', NOW()),

(3, 'airport', 'sân bay', 'We arrived at the airport early.', '/ˈer.pɔːrt/', '', NOW()),
(3, 'hotel', 'khách sạn', 'The hotel is near the beach.', '/hoʊˈtel/', '', NOW()),
(3, 'ticket', 'vé', 'I bought a train ticket online.', '/ˈtɪk.ɪt/', '', NOW()),
(3, 'passport', 'hộ chiếu', 'Do not forget your passport.', '/ˈpæs.pɔːrt/', '', NOW()),
(3, 'luggage', 'hành lý', 'My luggage is heavy.', '/ˈlʌɡ.ɪdʒ/', '', NOW()),
(3, 'reservation', 'sự đặt chỗ', 'I made a reservation for two nights.', '/ˌrez.ərˈveɪ.ʃən/', '', NOW()),
(3, 'map', 'bản đồ', 'Can you show me the map?', '/mæp/', '', NOW()),
(3, 'beach', 'bãi biển', 'We spent the afternoon on the beach.', '/biːtʃ/', '', NOW()),
(3, 'station', 'nhà ga', 'The bus station is across the street.', '/ˈsteɪ.ʃən/', '', NOW()),
(3, 'tourist', 'khách du lịch', 'Many tourists visit the old city.', '/ˈtʊr.ɪst/', '', NOW());

INSERT INTO test_results (user_id, set_id, score, total_questions, mode, created_at) VALUES
(1, 1, 8, 10, 'test', NOW());
