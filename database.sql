-- Car Rental System Database Schema

CREATE DATABASE car_rental_system;
USE car_rental_system;

-- Users table to store user authentication information
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    nic_number VARCHAR(12) UNIQUE,
    driving_license VARCHAR(20),
    user_role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Car categories
CREATE TABLE car_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Available cars
CREATE TABLE cars (
    car_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    color VARCHAR(30),
    seating_capacity INT,
    fuel_type ENUM('petrol', 'diesel', 'hybrid', 'electric'),
    transmission ENUM('manual', 'automatic'),
    daily_rate DECIMAL(10, 2) NOT NULL,
    weekly_rate DECIMAL(10, 2),
    monthly_rate DECIMAL(10, 2),
    mileage INT,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    description TEXT,
    FOREIGN KEY (category_id) REFERENCES car_categories(category_id)
);

-- Bookings table
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    car_id INT,
    pickup_date DATETIME NOT NULL,
    return_date DATETIME NOT NULL,
    pickup_location VARCHAR(100),
    return_location VARCHAR(100),
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) DEFAULT 0,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (car_id) REFERENCES cars(car_id)
);

-- Payments table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'online_payment'),
    transaction_id VARCHAR(100),
    payment_status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    car_id INT,
    booking_id INT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (car_id) REFERENCES cars(car_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- Insert car categories
INSERT INTO car_categories (category_name, description)
VALUES 
('Economy', 'Affordable and fuel-efficient cars'),
('Compact', 'Small cars suitable for city driving'),
('Sedan', 'Medium-sized comfortable cars'),
('SUV', 'Sport Utility Vehicles with more space and comfort'),
('Luxury', 'Premium cars with high-end features');

-- Insert sample cars
INSERT INTO cars (category_id, brand, model, year, registration_number, color, seating_capacity, fuel_type, transmission, daily_rate, weekly_rate, monthly_rate, is_available, description,image_url)
VALUES
(1, 'Toyota', 'Vitz', 2018, 'CAR-1001', 'White', 5, 'petrol', 'automatic', 5000.00, 30000.00, 100000.00, TRUE, 'Compact and fuel-efficient car perfect for city driving','assets/images/vitz.jpg'),
(2, 'Suzuki', 'Swift', 2019, 'CAR-1002', 'Red', 5, 'petrol', 'manual', 4500.00, 28000.00, 90000.00, TRUE, 'Sporty hatchback with excellent fuel economy','assets/images/Swift.jpg'),
(3, 'Toyota', 'Corolla', 2020, 'CAR-1003', 'Silver', 5, 'petrol', 'automatic', 6000.00, 35000.00, 120000.00, TRUE, 'Comfortable sedan with ample space','assets/images/Corolla.jpg'),
(4, 'Honda', 'Vezel', 2019, 'CAR-1004', 'Blue', 5, 'hybrid', 'automatic', 7000.00, 42000.00, 140000.00, TRUE, 'Compact SUV with hybrid technology','assets/images/Vezel.png'),
(5, 'BMW', '5 Series', 2021, 'CAR-1005', 'Black', 5, 'petrol', 'automatic', 15000.00, 90000.00, 300000.00, TRUE, 'Luxury sedan with premium features','assets/images/bmw.jpg');