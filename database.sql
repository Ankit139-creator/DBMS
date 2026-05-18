-- =====================================================
-- Canteen Review System - Database Setup
-- DBMS Mini Project
-- =====================================================


-- =====================================================
-- Step 3: Create Tables
-- =====================================================

-- Table 1: students - stores registered student info
CREATE TABLE IF NOT EXISTS students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 2: food_items - stores food menu items
CREATE TABLE IF NOT EXISTS food_items (
    food_id INT PRIMARY KEY AUTO_INCREMENT,
    food_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

-- Table 3: reviews - stores food reviews by students
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    food_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(food_id) ON DELETE CASCADE
);

-- =====================================================
-- Step 4: Insert Sample Food Items
-- =====================================================

INSERT INTO food_items (food_name, price) VALUES
('Masala Dosa', 40.00),
('Idli Sambar', 30.00),
('Veg Biryani', 80.00),
('Paneer Butter Masala', 90.00),
('Chole Bhature', 60.00),
('Veg Fried Rice', 70.00),
('Samosa', 15.00),
('Tea / Chai', 10.00),
('Cold Coffee', 40.00),
('Veg Sandwich', 35.00);

-- =====================================================
-- Step 5: Insert Sample Students (password: "password123")
-- Note: Passwords are hashed using PHP password_hash()
-- The hash below corresponds to "password123"
-- =====================================================

INSERT INTO students (name, email, password) VALUES
('Rahul Sharma', 'rahul@example.com', '$2y$10$YourHashedPasswordHere1234567890abcdefghij'),
('Priya Patel', 'priya@example.com', '$2y$10$YourHashedPasswordHere1234567890abcdefghij');

-- =====================================================
-- Step 6: Insert Sample Reviews
-- =====================================================

INSERT INTO reviews (student_id, food_id, rating, review_text) VALUES
(1, 1, 5, 'Best masala dosa in the campus! Crispy and delicious.'),
(1, 3, 4, 'Veg biryani is flavorful but could use more spices.'),
(2, 2, 4, 'Idli sambar is fresh and tasty. Good portion size.'),
(2, 7, 3, 'Samosa is decent but sometimes oily.');

-- =====================================================
-- Useful Queries for Reference
-- =====================================================

-- View all reviews with student name and food name
-- SELECT r.review_id, s.name, f.food_name, r.rating, r.review_text, r.created_at
-- FROM reviews r
-- JOIN students s ON r.student_id = s.student_id
-- JOIN food_items f ON r.food_id = f.food_id
-- ORDER BY r.created_at DESC;

-- View average rating per food item
-- SELECT f.food_name, ROUND(AVG(r.rating), 1) as avg_rating, COUNT(r.review_id) as total_reviews
-- FROM food_items f
-- LEFT JOIN reviews r ON f.food_id = r.food_id
-- GROUP BY f.food_id
-- ORDER BY avg_rating DESC;
