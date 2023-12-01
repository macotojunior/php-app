CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    date DATE NOT NULL,
    status BOOLEAN NOT NULL
);

INSERT INTO user (name, username, date, status) VALUES
('Jimi Hendrix', 'jimi_hendrix', '2023-01-01', TRUE),
('Steve Ray Vaughan', 'srv', '2023-01-08', TRUE),
('Eddie Van Halen', 'van_halen', '2023-01-07', FALSE);