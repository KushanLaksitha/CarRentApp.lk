-- Create the database
CREATE DATABASE car_rent_db;

USE car_rent_db;

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create subscribers table
CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cars table
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_name VARCHAR(255) NOT NULL UNIQUE,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create bookings table
CREATE TABLE IF NOT EXISTS bookings (
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

-- Insert sample cars
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

-- Insert a default admin user (password: Admin@123)
INSERT INTO admin_users (username, full_name, email, password) 
VALUES ('admin', 'System Administrator', 'admin@example.com', '$2y$10$8K1p/hxQhWxMxPRaLhmbU.Fs1jE9XPLvzPv.mZxwAaNqGGHLB2qVi');

-- Insert sample bookings for testing
INSERT INTO bookings (first_name, last_name, email, mobile, pickup_location, dropoff_location, pickup_date, pickup_time, adults, children, payment_method) VALUES
('John', 'Doe', 'john@example.com', '1234567890', 'Airport', 'Hotel', '2025-03-01', '10:00:00', 2, 1, 'paypal'),
('Jane', 'Smith', 'jane@example.com', '9876543210', 'Hotel', 'Airport', '2025-03-02', '14:00:00', 1, 0, 'directcheck'),
('Mike', 'Johnson', 'mike@example.com', '5555555555', 'City Center', 'Beach Resort', '2025-03-03', '09:00:00', 3, 2, 'banktransfer');

-- Insert sample subscribers
INSERT INTO subscribers (email) VALUES
('subscriber1@example.com'),
('subscriber2@example.com'),
('subscriber3@example.com');