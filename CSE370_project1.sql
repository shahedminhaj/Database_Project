-- Database Creation
CREATE DATABASE IF NOT EXISTS CSE370_project1;
USE CSE370_project1;

-- Users Table
CREATE TABLE Users (
    User_ID INT PRIMARY KEY AUTO_INCREMENT,
    NID VARCHAR(20) UNIQUE NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(20),
    Password VARCHAR(255) NOT NULL,
    Address TEXT,
    User_Type ENUM('Citizen', 'Admin') DEFAULT 'Citizen',
    Balance DECIMAL(10,2) DEFAULT 100.00,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services Table
CREATE TABLE Services (
    Service_ID INT PRIMARY KEY AUTO_INCREMENT,
    Service_Name VARCHAR(100) NOT NULL,
    Description TEXT,
    Fee DECIMAL(10,2) NOT NULL,
    Dept VARCHAR(100),
    Icon VARCHAR(50) DEFAULT 'fa-cogs',
    Color VARCHAR(50) DEFAULT '#667eea',
    Status ENUM('Active', 'Inactive') DEFAULT 'Active'
);

-- Service Requests
CREATE TABLE Service_Requests (
    Request_ID INT PRIMARY KEY AUTO_INCREMENT,
    User_ID INT,
    Service_ID INT,
    Request_Details TEXT,
    Payment_Status ENUM('Pending', 'Paid', 'Due') DEFAULT 'Due',
    Payment_Amount DECIMAL(10,2),
    Payment_Date DATETIME NULL,
    Status ENUM('Submitted', 'Processing', 'Completed') DEFAULT 'Submitted',
    Submission_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Completion_Date DATETIME NULL,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Service_ID) REFERENCES Services(Service_ID)
);

-- Payments Table
CREATE TABLE Payments (
    Payment_ID INT PRIMARY KEY AUTO_INCREMENT,
    User_ID INT,
    Request_ID INT,
    Amount DECIMAL(10,2),
    Payment_Method VARCHAR(50),
    Transaction_ID VARCHAR(100),
    Payment_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Success', 'Failed', 'Pending') DEFAULT 'Pending',
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Request_ID) REFERENCES Service_Requests(Request_ID)
);

-- Complaints Table
CREATE TABLE Complaints (
    Complaint_ID INT PRIMARY KEY AUTO_INCREMENT,
    User_ID INT,
    Title VARCHAR(200),
    Description TEXT,
    Category VARCHAR(100),
    Location VARCHAR(255),
    Priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    Status ENUM('Pending', 'Approved', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending',
    Admin_Comments TEXT,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Resolved_At DATETIME NULL,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID)
);

-- Insert ONLY ONE Admin User (pre-defined admin account)
-- Password: admin123 = 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9
INSERT INTO Users (NID, Name, Email, Phone, Password, Address, User_Type, Balance) VALUES
('9999999999', 'System Admin', 'admin@city.com', '01700000000', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'City Corporation Headquarters', 'Admin', 0.00);

-- Insert Services
INSERT INTO Services (Service_Name, Description, Fee, Dept, Icon, Color) VALUES
('Waste Collection', 'Regular garbage collection service from your doorstep', 200.00, 'Sanitation', 'fa-trash-alt', '#27ae60'),
('Water Connection', 'New water supply connection for residential/commercial use', 1500.00, 'Water Supply', 'fa-tint', '#3498db'),
('Building Permit', 'Permission for new construction or renovation', 5000.00, 'Engineering', 'fa-building', '#e74c3c'),
('Property Tax', 'Annual property tax payment and assessment', 3000.00, 'Taxation', 'fa-landmark', '#9b59b6'),
('Street Light Repair', 'Report and repair of faulty street lights', 0.00, 'Electrical', 'fa-lightbulb', '#f39c12'),
('Trade License', 'Business license for commercial establishments', 2500.00, 'Commerce', 'fa-briefcase', '#1abc9c'),
('Birth Certificate', 'Apply for new birth certificate', 100.00, 'Registration', 'fa-baby', '#34495e'),
('Parking Permit', 'Residential or commercial parking permission', 1200.00, 'Transport', 'fa-parking', '#00cec9'),
('Electricity Bill', 'Pay electricity bills online', 0.00, 'Electrical', 'fa-bolt', '#f1c40f'),
('Marriage Certificate', 'Registration and certificate for marriage', 500.00, 'Registration', 'fa-heart', '#e84393'),
('Death Certificate', 'Registration of death and certificate issuance', 100.00, 'Registration', 'fa-cross', '#7f8c8d'),
('Road Repair', 'Report road damage for repair', 0.00, 'Engineering', 'fa-road', '#d35400'),
('Drainage Cleaning', 'Clear blocked drainage systems', 0.00, 'Sanitation', 'fa-water', '#2980b9'),
('Noise Complaint', 'Report excessive noise pollution', 0.00, 'Police', 'fa-volume-up', '#8e44ad'),
('Park Maintenance', 'Report issues in public parks', 0.00, 'Parks & Recreation', 'fa-tree', '#27ae60');

-- Insert Sample Complaints (Optional - for testing)
INSERT INTO Complaints (User_ID, Title, Description, Category, Location, Priority, Status) VALUES
(1, 'Garbage Collection Issue', 'Garbage has not been collected for 5 days in our neighborhood', 'Sanitation', 'Green Road Area, Sector 5', 'High', 'Pending'),
(1, 'Broken Street Light', 'Street light near house number 45 is not working, causing safety issues at night', 'Electricity', 'House 45, Road 8', 'Medium', 'Pending'),
(1, 'Water Pipeline Leakage', 'Main water pipeline is leaking near the market, wasting water', 'Water Supply', 'Main Market, Central Area', 'High', 'Approved'),
(1, 'Pothole on Main Road', 'Large pothole on main road causing traffic and accidents', 'Road', 'Main Road near City Hospital', 'High', 'In Progress'),
(1, 'Park Equipment Broken', 'Children playground equipment in central park is broken', 'Park', 'Central Public Park', 'Medium', 'Resolved');