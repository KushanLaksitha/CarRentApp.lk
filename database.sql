CREATE DATABASE car_rent_db;

USE car_rent_db;

CREATE TABLE subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_name VARCHAR(255) NOT NULL UNIQUE,
    available BOOLEAN DEFAULT TRUE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    pickup_location VARCHAR(50) NOT NULL,
    dropoff_location VARCHAR(50) NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    adults INT NOT NULL,
    children INT NOT NULL,
    special_request TEXT,
    payment_method ENUM('paypal', 'directcheck', 'banktransfer') NOT NULL,
    booking_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create an index on email for faster searching
CREATE INDEX idx_email ON bookings(email);

-- Optional: Create a unique constraint to prevent duplicate bookings
ALTER TABLE bookings 
ADD CONSTRAINT unique_booking 
UNIQUE (first_name, last_name, email, pickup_date, pickup_time);

INSERT INTO cars (car_name) VALUES
('Toyota Axio'),
('Toyota Aqua'),
('Nissan Sunny'),
('Honda Fit'),
('Suzuki Alto'),
('Perodua Bezza'),
('Toyota Prius'),
('Mazda Axela'),
('Nissan X-Trail'),
('Toyota Hiace'),
('Suzuki Wagon R'),
('Honda Vessel'),
('Toyota Corolla'),
('Kia Sportage'),
('Hyundai Tucson');


