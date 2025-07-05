-- Çalışan Talep Yönetim Sistemi - Veritabanı Şeması
-- MySQL 8.0+ uyumlu

CREATE DATABASE IF NOT EXISTS employee_requests_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE employee_requests_db;

-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    manager_id INT NULL,
    role ENUM('employee', 'manager', 'admin') DEFAULT 'employee',
    phone VARCHAR(20),
    hire_date DATE,
    salary DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Talep türleri tablosu
CREATE TABLE request_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Talepler tablosu
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_type_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    amount DECIMAL(10,2),
    urgency ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_type_id) REFERENCES request_types(id)
);

-- Onaylar tablosu
CREATE TABLE approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    approver_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Dosya ekleri tablosu
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
);

-- Bildirimler tablosu
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sistem ayarları tablosu
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Varsayılan talep türleri
INSERT INTO request_types (name, description) VALUES
('İzin', 'Yıllık izin, hastalık izni, mazeret izni vb.'),
('Avans', 'Maaş avansı talebi'),
('Fazla Mesai', 'Fazla mesai ücreti talebi'),
('Ekipman', 'Ofis ekipmanı, bilgisayar, telefon vb. talepleri'),
('Özel Talep', 'Diğer özel durumlar için genel talep');

-- Varsayılan admin kullanıcı (şifre: admin123)
INSERT INTO users (employee_id, email, password_hash, first_name, last_name, department, position, role) VALUES
('ADMIN001', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Kullanıcı', 'BT', 'Sistem Yöneticisi', 'admin');

-- Varsayılan sistem ayarları
INSERT INTO settings (setting_key, setting_value, description) VALUES
('company_name', 'Şirket Adı', 'Şirket adı'),
('max_file_size', '5242880', 'Maksimum dosya boyutu (byte)'),
('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'İzin verilen dosya türleri'),
('email_notifications', '1', 'E-posta bildirimlerini aktif et'),
('auto_approval_limit', '1000', 'Otomatik onay limiti (TL)');

-- İndeksler
CREATE INDEX idx_users_employee_id ON users(employee_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_requests_user_id ON requests(user_id);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_requests_created_at ON requests(created_at);
CREATE INDEX idx_approvals_request_id ON approvals(request_id);
CREATE INDEX idx_approvals_approver_id ON approvals(approver_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);