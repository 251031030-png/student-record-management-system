-- Student Record Management System
-- Run this script in MySQL (for example, using phpMyAdmin or MySQL Workbench).

CREATE DATABASE IF NOT EXISTS student_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE student_management;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    department VARCHAR(80) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    cgpa DECIMAL(3,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_semester CHECK (semester BETWEEN 1 AND 12),
    CONSTRAINT chk_cgpa CHECK (cgpa BETWEEN 0.00 AND 4.00)
);

-- Sample data: 10 records, as required by the project brief.
INSERT INTO students (name, email, department, semester, cgpa) VALUES
('Ayesha Rahman', 'ayesha.rahman@example.com', 'CSE', 5, 3.78),
('Tanvir Hasan', 'tanvir.hasan@example.com', 'EEE', 3, 3.45),
('Nusrat Jahan', 'nusrat.jahan@example.com', 'BBA', 7, 3.82),
('Sakib Ahmed', 'sakib.ahmed@example.com', 'CSE', 4, 3.20),
('Maliha Islam', 'maliha.islam@example.com', 'English', 6, 3.65),
('Rafiul Karim', 'rafiul.karim@example.com', 'EEE', 8, 3.51),
('Farzana Akter', 'farzana.akter@example.com', 'CSE', 2, 3.91),
('Imran Hossain', 'imran.hossain@example.com', 'BBA', 5, 3.30),
('Sadia Chowdhury', 'sadia.chowdhury@example.com', 'English', 3, 3.72),
('Mahmudul Islam', 'mahmudul.islam@example.com', 'CSE', 7, 3.58);

-- Key SQL examples used by the application:
-- CREATE: see CREATE TABLE above.
-- INSERT: see sample INSERT above; the PHP form also uses a prepared INSERT.
-- SELECT: SELECT * FROM students;
-- SEARCH: SELECT * FROM students WHERE name LIKE '%Ayesha%';
-- UPDATE: UPDATE students SET semester = 6 WHERE id = 1;
-- DELETE: DELETE FROM students WHERE id = 1;
-- AGGREGATE: SELECT COUNT(*), AVG(cgpa), MAX(cgpa) FROM students;
