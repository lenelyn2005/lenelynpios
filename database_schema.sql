-- College Automated Scheduling System Database Schema
-- Run this script to create all required tables

CREATE DATABASE IF NOT EXISTS college_scheduling;
USE college_scheduling;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Year levels table
CREATE TABLE IF NOT EXISTS year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sections table
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    year_level_id INT,
    course_id INT,
    max_students INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    department_id INT,
    course_id INT,
    year_level_id INT,
    units INT DEFAULT 3,
    hours_per_week INT DEFAULT 3,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE CASCADE
);

-- Teachers table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    department_id INT,
    employment_type ENUM('full_time', 'part_time') NOT NULL,
    monthly_hours INT DEFAULT 160, -- For full-time teachers
    daily_hours INT DEFAULT 8,     -- For part-time teachers
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id VARCHAR(20) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    section_id INT,
    year_level_id INT,
    course_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
    FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    capacity INT DEFAULT 30,
    room_type ENUM('classroom', 'laboratory', 'lecture_hall', 'computer_lab') DEFAULT 'classroom',
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Teacher subjects assignment
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    subject_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
);

-- Schedules table
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    subject_id INT,
    section_id INT,
    room_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    semester VARCHAR(20) DEFAULT '1st Semester',
    academic_year VARCHAR(20) DEFAULT '2024-2025',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Schedule conflicts log
CREATE TABLE IF NOT EXISTS schedule_conflicts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conflict_type ENUM('teacher', 'room', 'section', 'capacity') NOT NULL,
    entity_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT,
    resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications table (added to fix fatal error in teacher_notifications.php)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- Clear existing admin and insert default admin account
DELETE FROM admins WHERE username = 'admin';
INSERT INTO admins (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- hashed 'admin123'

-- Insert default year levels
INSERT INTO year_levels (name) VALUES 
('1st Year'), ('2nd Year'), ('3rd Year'), ('4th Year'), ('5th Year') 
ON DUPLICATE KEY UPDATE name=name;

-- Insert default departments
INSERT INTO departments (name) VALUES 
('Bs Computer Science'), ('Bs Information Technology'), ('Bs Information System'), ('Bs TOurism') 
ON DUPLICATE KEY UPDATE name=name;

-- Insert default courses
INSERT INTO courses (name, department_id) VALUES 
('Bachelor of Science in Computer Science', 1),
('Bachelor of Science in Information Technology', 2),
('Bachelor of Science in Information System', 3),
('Bachelor of Science in Tourism', 4)
ON DUPLICATE KEY UPDATE name=name;

-- Clear existing sections to ensure correct IDs
DELETE FROM sections;

-- Insert default sections with explicit IDs
INSERT INTO sections (id, name, year_level_id, course_id) VALUES 
(1, 'CS-1A', 1, 1), 
(2, 'CS-1B', 1, 1),
(3, 'IT-1A', 1, 2), 
(4, 'IT-1B', 1, 2),
(5, 'IS-1A', 1, 3), 
(6, 'IS-1B', 1, 3),
(7, 'TOURISM-1A', 1, 4), 
(8, 'TOURISM-1B', 1, 4);

-- Insert default rooms
INSERT INTO rooms (name, capacity, room_type, location) VALUES 
('Room 101', 30, 'classroom', 'Building A - 1st Floor'),
('Room 102', 30, 'classroom', 'Building A - 1st Floor'),
('Room 103', 30, 'classroom', 'Building A - 1st Floor'),
('Lab 201', 25, 'computer_lab', 'Building A - 2nd Floor'),
('Lab 202', 25, 'computer_lab', 'Building A - 2nd Floor'),
('Lecture Hall 301', 100, 'lecture_hall', 'Building A - 3rd Floor')
ON DUPLICATE KEY UPDATE name=name;

-- Insert sample subjects
INSERT INTO subjects (name, code, department_id, course_id, year_level_id, units, hours_per_week) VALUES 
('Programming Fundamentals', 'CS101', 1, 1, 1, 3, 3),
('Data Structures', 'CS102', 1, 1, 2, 3, 3),
('Database Management', 'IT101', 2, 2, 1, 3, 3),
('Web Development', 'IT102', 2, 2, 2, 3, 3),
('System Analysis and Design', 'IS101', 3, 3, 1, 3, 3),
('Information Systems', 'IS102', 3, 3, 2, 3, 3),
('Tourism Principles', 'TOUR101', 4, 4, 1, 3, 3),
('Tourism Management', 'TOUR102', 4, 4, 2, 3, 3)
ON DUPLICATE KEY UPDATE name=name;

-- Insert sample teachers
INSERT INTO teachers (username, password, first_name, last_name, email, department_id, employment_type, monthly_hours, daily_hours) VALUES 
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'john.smith@college.edu', 1, 'full_time', 160, 8),
('teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Doe', 'jane.doe@college.edu', 2, 'full_time', 160, 8),
('teacher3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Johnson', 'mike.johnson@college.edu', 3, 'part_time', 80, 4),
('teacher4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Wilson', 'sarah.wilson@college.edu', 4, 'full_time', 160, 8)
ON DUPLICATE KEY UPDATE username=username;

-- Insert sample students
INSERT INTO students (id, first_name, last_name, email, section_id, year_level_id, course_id) VALUES 
('2024-0001', 'Alice', 'Brown', 'alice.brown@student.college.edu', 1, 1, 1),
('2024-0002', 'Bob', 'Davis', 'bob.davis@student.college.edu', 1, 1, 1),
('2024-0003', 'Carol', 'Miller', 'carol.miller@student.college.edu', 2, 1, 1),
('2024-0004', 'David', 'Garcia', 'david.garcia@student.college.edu', 3, 1, 2),
('2024-0005', 'Eva', 'Martinez', 'eva.martinez@student.college.edu', 4, 1, 2)
ON DUPLICATE KEY UPDATE id=id;

-- Assign teachers to subjects
INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES 
(1, 1), (1, 2),  -- John Smith teaches CS subjects
(2, 3), (2, 4),  -- Jane Doe teaches IT subjects
(3, 5), (3, 6),  -- Mike Johnson teaches IS subjects
(4, 7), (4, 8)   -- Sarah Wilson teaches Tourism subjects
ON DUPLICATE KEY UPDATE teacher_id=teacher_id;

-- Insert sample notifications for testing
INSERT INTO notifications (teacher_id, title, message, type) VALUES 
(1, 'Schedule Generated', 'Your schedule for the semester has been automatically generated.', 'success'),
(1, 'Room Change', 'Your class for CS101 on Monday has been moved to Room 102.', 'warning'),
(2, 'Conflict Resolved', 'The scheduling conflict for your IT101 class has been resolved.', 'info'),
(3, 'New Assignment', 'You have been assigned to teach IS102 for 2nd Year.', 'success');
