CREATE DATABASE IF NOT EXISTS abba_house_db;
USE abba_house_db;

CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL
);

CREATE TABLE orphans (
    orphan_id VARCHAR(10) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female') NOT NULL,
    date_of_birth DATE NOT NULL,
    date_of_enrollment DATE NOT NULL,
    has_disability ENUM('Yes','No') NOT NULL,
    photo_path VARCHAR(255),
    birth_cert_path VARCHAR(255),
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orphan_admin FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    orphan_id VARCHAR(10) NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present','Absent') NOT NULL DEFAULT 'Absent',
    UNIQUE KEY unique_attendance (orphan_id, attendance_date),
    CONSTRAINT fk_attendance_orphan FOREIGN KEY (orphan_id) REFERENCES orphans(orphan_id) ON DELETE CASCADE
);

CREATE TABLE contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (email, password, full_name)
VALUES ('admin@email.com', 'admin123', 'Admin User');

INSERT INTO orphans (orphan_id, full_name, gender, date_of_birth, date_of_enrollment, has_disability, admin_id)
VALUES
('ORP001', 'Rina Brown', 'Female', '2018-04-12', CURDATE(), 'No', 1),
('ORP002', 'Remo Blake', 'Male', '2017-08-22', CURDATE(), 'No', 1),
('ORP003', 'Ruth Biko', 'Female', '2019-01-05', CURDATE(), 'Yes', 1),
('ORP004', 'Ryan Banda', 'Male', '2018-11-18', CURDATE(), 'No', 1);
