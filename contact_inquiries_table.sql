USE terra_invest;

CREATE TABLE IF NOT EXISTS contact_inquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(24) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    inquiry_type ENUM('Buying','Selling','Verification','Investment','Legal','Other') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_inquiries_created_at (created_at),
    INDEX idx_contact_inquiries_email (email),
    INDEX idx_contact_inquiries_inquiry_type (inquiry_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
