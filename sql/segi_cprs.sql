-- Create and select the database used by the system.
CREATE DATABASE IF NOT EXISTS segi_cprs
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE segi_cprs;

-- Login accounts for the admin and lecturers.
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'lecturer') NOT NULL
) ENGINE=InnoDB;

INSERT INTO users (username, password, fullname, role) VALUES
('admin', 'admin123', 'Administrator', 'admin'),
('amizar', 'amizar123', 'Mr. Amizar', 'lecturer'),
('aqilah', 'aqilah123', 'Ms. Aqilah', 'lecturer'),
('rosmah', 'rosmah123', 'Ms. Rosmah', 'lecturer'),
('steven', 'steven123', 'Mr. Steven', 'lecturer'),
('shahidh', 'shahidh123', 'Mr. Shahidh', 'lecturer');

-- Academic programmes offered by the college.
CREATE TABLE programmes (
    programme_id INT AUTO_INCREMENT PRIMARY KEY,
    programme_code VARCHAR(10) NOT NULL UNIQUE,
    programme_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO programmes (programme_code, programme_name) VALUES
('CIT', 'Certificate in Information Technology'),
('DIT', 'Diploma in Information Technology'),
('BCS', 'Bachelor of Computer Science'),
('BIT', 'Bachelor of Information Technology');

-- Study modes belong to a specific programme.
CREATE TABLE study_modes (
    study_mode_id INT AUTO_INCREMENT PRIMARY KEY,
    programme_id INT NOT NULL,
    study_mode_name VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_programme_study_mode (programme_id, study_mode_name),
    FOREIGN KEY (programme_id) REFERENCES programmes(programme_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO study_modes (programme_id, study_mode_name) VALUES
(1, 'Full Time'),
(2, 'Full Time'), (2, 'Weekend'), (2, 'Micro Credential'),
(3, '2+0'), (3, '3+0'), (3, 'Weekend'),
(4, '2+0'), (4, '3+0'), (4, 'Weekend');

-- Students are linked to one programme through their study mode.
CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    study_mode_id INT NOT NULL,
    intake ENUM(
        'January 2024', 'June 2024', 'September 2024',
        'January 2025', 'June 2025', 'September 2025',
        'January 2026', 'June 2026', 'September 2026'
    ) NOT NULL,
    FOREIGN KEY (study_mode_id) REFERENCES study_modes(study_mode_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Twenty fictional students retained from the original demonstration records.
INSERT INTO students (student_id, student_name, study_mode_id, intake) VALUES
('SCPG2400025', 'Kavitha A/P Chandran', 2, 'January 2024'),
('SCPG2400093', 'Chan Mei Ling', 2, 'June 2024'),
('SCPG2500087', 'Lucas Wong Kah Chun', 2, 'January 2025'),
('SCPG2400203', 'Harini A/P Rajan', 2, 'September 2024'),
('SCPG2400204', 'Muhammad Adam bin Firdaus', 2, 'January 2025'),
('SCPG2400205', 'Siti Hajar binti Mohamad', 2, 'June 2025'),
('SCPG2400206', 'Wong Ee Lin', 2, 'September 2025'),
('SCPG2400207', 'Pravin A/L Kumar', 2, 'January 2026'),
('SCPG2400114', 'Nurul Aisyah binti Kamaruddin', 3, 'September 2024'),
('SCPG2500140', 'Muhammad Firas bin Azman', 3, 'September 2025'),
('SCPG2400401', 'Zulaikha binti Basri', 3, 'January 2024'),
('SCPG2400402', 'Kevin Lim Jun Hao', 3, 'September 2024'),
('SCPG2500401', 'Sharvin A/L Raj', 3, 'January 2025'),
('SCPG2400208', 'Irfan Hakimi bin Zulkifli', 5, 'June 2024'),
('SCPG2400209', 'Jessica Lee Xin Yi', 5, 'September 2024'),
('SCPG2400210', 'Thivya A/P Mogan', 5, 'January 2025'),
('SCPG2500201', 'Khalisah binti Omar', 6, 'June 2025'),
('SCPG2500202', 'Lim Jia Hao', 6, 'September 2025'),
('SCPG2500203', 'Nadia Farhana binti Ismail', 6, 'January 2026'),
('SCPG2500204', 'Vinesh A/L Selvan', 6, 'June 2026');

-- Each course must have a lecturer and one study mode.
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    study_mode_id INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    lecturer_id INT NOT NULL,
    clo1_name VARCHAR(50) DEFAULT '',
    clo1_description TEXT,
    clo1_weight INT DEFAULT 0,
    clo2_name VARCHAR(50) DEFAULT '',
    clo2_description TEXT,
    clo2_weight INT DEFAULT 0,
    clo3_name VARCHAR(50) DEFAULT '',
    clo3_description TEXT,
    clo3_weight INT DEFAULT 0,
    clo1_comment TEXT,
    clo2_comment TEXT,
    clo3_comment TEXT,
    FOREIGN KEY (study_mode_id) REFERENCES study_modes(study_mode_id) ON DELETE RESTRICT,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- A marks row also represents one student's enrollment in a course.
CREATE TABLE marks (
    mark_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    course_id INT NOT NULL,
    clo1_raw DECIMAL(5,2) DEFAULT 0,
    clo2_raw DECIMAL(5,2) DEFAULT 0,
    clo3_raw DECIMAL(5,2) DEFAULT 0,
    attendance_percentage DECIMAL(5,2) NOT NULL DEFAULT 100,
    remarks TEXT,
    UNIQUE KEY uq_marks_student_course (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Newly prepared fictional courses: four for Amizar, one for each other lecturer.
-- CLO 1 and CLO 2 assess coursework (60%); CLO 3 assesses the examination (40%).
INSERT INTO courses (
    course_id, course_code, course_name, study_mode_id, semester, lecturer_id,
    clo1_name, clo1_description, clo1_weight,
    clo2_name, clo2_description, clo2_weight,
    clo3_name, clo3_description, clo3_weight,
    clo1_comment, clo2_comment, clo3_comment
) VALUES
(1, 'DIT101', 'Programming Fundamentals', 2, 'Semester 1', 2,
    'CLO 1', 'Explain programming concepts', 30,
    'CLO 2', 'Build structured programs', 30,
    'CLO 3', 'Solve programming problems', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.'),
(2, 'DIT102', 'Database Fundamentals', 2, 'Semester 1', 2,
    'CLO 1', 'Explain relational models', 30,
    'CLO 2', 'Design relational databases', 30,
    'CLO 3', 'Write SQL queries', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.'),
(3, 'DIT201', 'Object-Oriented Programming', 2, 'Semester 1', 2,
    'CLO 1', 'Explain object-oriented principles', 30,
    'CLO 2', 'Implement classes and objects', 30,
    'CLO 3', 'Evaluate program solutions', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.'),
(4, 'DIT202', 'Web Development', 2, 'Semester 1', 2,
    'CLO 1', 'Explain web technologies', 30,
    'CLO 2', 'Build responsive web pages', 30,
    'CLO 3', 'Develop database-driven features', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.'),
(5, 'DIT103', 'Computer Networks', 3, 'Semester 1', 3,
    'CLO 1', 'Explain network models', 30,
    'CLO 2', 'Configure network components', 30,
    'CLO 3', 'Troubleshoot connectivity', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.'),
(6, 'DIT104', 'Systems Analysis and Design', 3, 'Semester 1', 4,
    'CLO 1', 'Identify system requirements', 30,
    'CLO 2', 'Model system processes', 30,
    'CLO 3', 'Evaluate system designs', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.'),
(7, 'BCS201', 'Software Engineering', 5, 'Semester 1', 5,
    'CLO 1', 'Explain software processes', 30,
    'CLO 2', 'Apply software design methods', 30,
    'CLO 3', 'Evaluate software quality', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.'),
(8, 'BCS202', 'Data Structures and Algorithms', 6, 'Semester 1', 6,
    'CLO 1', 'Explain data structures', 30,
    'CLO 2', 'Implement common algorithms', 30,
    'CLO 3', 'Analyse algorithm efficiency', 40,
    'Use short revision activities to reinforce the core concepts.',
    'Provide guided practice and feedback on practical work.',
    'Review examination techniques and offer support to students below the threshold.');

-- Each row enrolls an eligible student and supplies fictional saved results.
-- Grades and Pass/Resit/Retake status are calculated by the application.
INSERT INTO marks (student_id, course_id, clo1_raw, clo2_raw, clo3_raw, attendance_percentage, remarks) VALUES
('SCPG2400025', 1, 84, 80, 86, 98, 'Strong understanding and consistent assessment performance.'),
('SCPG2400093', 1, 66, 62, 36, 86, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2500087', 1, 34, 40, 56, 74, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400203', 1, 74, 70, 68, 94, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400204', 1, 56, 61, 52, 88, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400205', 1, 46, 44, 41, 78, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400206', 1, 89, 86, 92, 100, 'Strong understanding and consistent assessment performance.'),
('SCPG2400207', 1, 63, 68, 60, 92, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400025', 2, 85, 81, 87, 99, 'Strong understanding and consistent assessment performance.'),
('SCPG2400093', 2, 67, 63, 37, 87, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2500087', 2, 35, 41, 57, 75, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400203', 2, 75, 71, 69, 95, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400204', 2, 57, 62, 53, 89, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400205', 2, 47, 45, 42, 79, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400206', 2, 90, 87, 93, 100, 'Strong understanding and consistent assessment performance.'),
('SCPG2400207', 2, 64, 69, 61, 93, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400025', 3, 86, 82, 88, 100, 'Strong understanding and consistent assessment performance.'),
('SCPG2400093', 3, 68, 64, 38, 88, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2500087', 3, 36, 42, 58, 76, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400203', 3, 76, 72, 70, 96, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400204', 3, 58, 63, 54, 90, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400205', 3, 48, 46, 43, 80, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400206', 3, 91, 88, 94, 100, 'Strong understanding and consistent assessment performance.'),
('SCPG2400207', 3, 65, 70, 62, 94, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400025', 4, 87, 83, 89, 98, 'Strong understanding and consistent assessment performance.'),
('SCPG2400093', 4, 69, 65, 39, 86, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2500087', 4, 37, 43, 59, 74, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400203', 4, 77, 73, 71, 94, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400204', 4, 59, 64, 55, 88, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400205', 4, 49, 47, 44, 78, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400206', 4, 92, 89, 95, 100, 'Strong understanding and consistent assessment performance.'),
('SCPG2400207', 4, 66, 71, 63, 92, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400114', 5, 88, 84, 90, 99, 'Strong understanding and consistent assessment performance.'),
('SCPG2500140', 5, 70, 66, 40, 87, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2400401', 5, 38, 44, 60, 75, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400402', 5, 78, 74, 72, 95, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2500401', 5, 60, 65, 56, 89, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400114', 6, 84, 80, 86, 100, 'Strong understanding and consistent assessment performance.'),
('SCPG2500140', 6, 66, 62, 36, 88, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2400401', 6, 34, 40, 56, 76, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2400402', 6, 74, 70, 68, 96, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2500401', 6, 56, 61, 52, 90, 'Meets the assessment requirements; continue practising key topics.'),
('SCPG2400208', 7, 85, 81, 87, 98, 'Strong understanding and consistent assessment performance.'),
('SCPG2400209', 7, 67, 63, 37, 86, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2400210', 7, 35, 41, 57, 74, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2500201', 8, 86, 82, 88, 99, 'Strong understanding and consistent assessment performance.'),
('SCPG2500202', 8, 68, 64, 38, 87, 'Coursework is satisfactory; focus on examination preparation.'),
('SCPG2500203', 8, 36, 42, 58, 75, 'Needs additional support with coursework concepts and practical tasks.'),
('SCPG2500204', 8, 76, 72, 70, 95, 'Meets the assessment requirements; continue practising key topics.');
