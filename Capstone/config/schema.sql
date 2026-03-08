-- Create database
CREATE DATABASE IF NOT EXISTS school_registrar;
USE school_registrar;

-- Students table
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    course VARCHAR(100),
    year_level INT,
    status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Teachers table
CREATE TABLE IF NOT EXISTS teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_number VARCHAR(20) UNIQUE NOT NULL,
    gmail VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('teacher', 'registrar', 'admin') DEFAULT 'teacher',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Documents table
CREATE TABLE IF NOT EXISTS documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    document_name VARCHAR(100) NOT NULL UNIQUE,
    document_code VARCHAR(20) UNIQUE,
    description TEXT,
    processing_days INT DEFAULT 3,
    fee DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document Requests table
CREATE TABLE IF NOT EXISTS document_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_id INT NOT NULL,
    quantity INT DEFAULT 1,
    status ENUM('pending', 'processing', 'ready', 'released', 'cancelled') DEFAULT 'pending',
    purpose VARCHAR(255),
    notes TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scheduled_pickup_date DATETIME,
    released_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(document_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at)
);

-- Pickup Schedule table
CREATE TABLE IF NOT EXISTS pickup_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    scheduled_date DATETIME NOT NULL,
    pickup_location VARCHAR(255),
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(request_id) ON DELETE CASCADE,
    INDEX idx_scheduled_date (scheduled_date)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT,
    type ENUM('missing_document', 'pickup_schedule', 'status_update') DEFAULT 'status_update',
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    request_id INT,
    sent_via ENUM('email', 'system') DEFAULT 'email',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL,
    FOREIGN KEY (request_id) REFERENCES document_requests(request_id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
);

-- Transaction History table
CREATE TABLE IF NOT EXISTS transaction_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    student_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    action_by VARCHAR(50),
    status_from VARCHAR(50),
    status_to VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_request (request_id),
    INDEX idx_created_at (created_at)
);

-- Insert sample documents
INSERT INTO documents (document_name, document_code, description, processing_days, fee) VALUES
('PSA Birth Certificate', 'PSA', 'Philippine Statistics Authority Birth Certificate', 5, 35.00),
('Form 137', 'FORM137', 'Permanent Record / Form 137', 3, 0.00),
('Transcript of Records (TOR)', 'TOR', 'Official Transcript of Records', 3, 0.00),
('Certificate of Enrollment', 'COE', 'Certificate of Enrollment for current semester', 1, 0.00),
('Good Moral Certificate', 'GMC', 'Certificate of Good Moral Character', 3, 150.00),
('Diploma', 'DIPLOMA', 'Official Diploma', 5, 100.00),
('Course Description', 'CD', 'Course Description/Syllabus', 2, 0.00),
('Transfer Credential', 'TC', 'Transfer Credential for Transferring Students', 7, 50.00);

-- Create indexes for better performance
CREATE INDEX idx_student_email ON students(email);
CREATE INDEX idx_teacher_gmail ON teachers(gmail);
CREATE INDEX idx_request_status ON document_requests(status);
CREATE INDEX idx_request_date ON document_requests(requested_at);

