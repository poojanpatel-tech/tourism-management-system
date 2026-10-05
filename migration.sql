-- 1. Modify Destinations
ALTER TABLE destinations
ADD COLUMN state VARCHAR(100) DEFAULT NULL AFTER country,
ADD COLUMN best_season VARCHAR(150) DEFAULT NULL AFTER description,
ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER best_season,
ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER image,
ADD COLUMN slug VARCHAR(150) DEFAULT NULL AFTER destination_name,
ADD COLUMN seo_title VARCHAR(255) DEFAULT NULL AFTER slug,
ADD COLUMN seo_description TEXT DEFAULT NULL AFTER seo_title;

-- 2. Modify Packages
ALTER TABLE packages
ADD COLUMN plan_type VARCHAR(50) DEFAULT 'Custom' AFTER destination_id,
ADD COLUMN duration_days INT DEFAULT 0 AFTER duration,
ADD COLUMN price_type VARCHAR(50) DEFAULT 'Per Person' AFTER price,
ADD COLUMN price_note VARCHAR(255) DEFAULT 'Final quote depends on dates, accommodation, group size and selected services.' AFTER price_type,
ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER image,
ADD COLUMN display_order INT DEFAULT 0 AFTER featured,
ADD COLUMN slug VARCHAR(150) DEFAULT NULL AFTER package_name,
ADD COLUMN seo_title VARCHAR(255) DEFAULT NULL AFTER slug,
ADD COLUMN seo_description TEXT DEFAULT NULL AFTER seo_title,
ADD COLUMN best_time VARCHAR(150) DEFAULT NULL AFTER seo_description;

-- 3. Create Itinerary
CREATE TABLE plan_itinerary (
    itinerary_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    day_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    morning TEXT,
    afternoon TEXT,
    evening TEXT,
    overnight TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE
);

-- 4. Create Highlights
CREATE TABLE plan_highlights (
    highlight_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    highlight_text TEXT NOT NULL,
    display_order INT DEFAULT 0,
    FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE
);

-- 5. Create Plan Images (Gallery)
CREATE TABLE plan_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    display_order INT DEFAULT 0,
    FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE
);

-- 6. Create Enquiries
CREATE TABLE enquiries (
    enquiry_id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT DEFAULT NULL,
    package_id INT DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50),
    travel_date DATE,
    travellers INT DEFAULT 1,
    duration VARCHAR(50),
    budget VARCHAR(50),
    interests TEXT,
    subject VARCHAR(255),
    message TEXT,
    status ENUM('new', 'contacted', 'in_discussion', 'quoted', 'confirmed', 'closed', 'cancelled') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE SET NULL
);

-- 7. Create Business Settings
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'Patel Travels'),
('tagline', 'Curated Travel Experiences'),
('email', 'info@pateltravels.local'),
('phone', '+91 9876 543 210'),
('whatsapp', '+91 9876 543 210'),
('address', 'SBR, Ahmedabad, Gujarat, India'),
('working_hours', 'Mon - Sat, 10:00 AM - 6:00 PM'),
('instagram', 'https://instagram.com/'),
('facebook', 'https://facebook.com/'),
('google_maps', 'https://maps.google.com/'),
('footer_description', 'Thoughtfully designed journeys, unforgettable destinations and travel experiences created around the way you want to explore.');

