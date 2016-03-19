CREATE TABLE users (
	id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(255) UNIQUE,
	email VARCHAR(255) UNIQUE,
	password VARCHAR(255),
	salt VARCHAR(255),
	roles VARCHAR(255),
	registration_hash VARCHAR(64) UNIQUE,
	forgot_password_hash VARCHAR(64) UNIQUE,
	forgot_password_date DATETIME,
	optin INT(1),
	active  INT(1),
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
