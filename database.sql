CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    email VARCHAR(100) NOT NULL
);

CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    roll_number VARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    date DATE NOT NULL,
    status ENUM('present', 'absent') NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id)
); 