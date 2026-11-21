CREATE DATABASE IF NOT EXISTS akuafo CHARACTER SET utf8mb4;
USE akuafo;

DROP TABLE IF EXISTS order_items, orders, box_sizes, boxes, hampers, bulk_items, recipes, farmers, products;

CREATE TABLE products (id INT PRIMARY KEY, name VARCHAR(120), twi VARCHAR(120), price DECIMAL(10,2), unit VARCHAR(80),
  category VARCHAR(60), region VARCHAR(60), farmer VARCHAR(80), image VARCHAR(120), description TEXT, badge VARCHAR(40) NULL);
CREATE TABLE boxes (id VARCHAR(40) PRIMARY KEY, name VARCHAR(80), tagline VARCHAR(160), image VARCHAR(120), color VARCHAR(10), contents TEXT, note TEXT);
CREATE TABLE box_sizes (id INT AUTO_INCREMENT PRIMARY KEY, box_id VARCHAR(40), idx INT, label VARCHAR(20), description VARCHAR(40), price DECIMAL(10,2), items VARCHAR(40));
CREATE TABLE hampers (id VARCHAR(40) PRIMARY KEY, name VARCHAR(100), occasion VARCHAR(60), price DECIMAL(10,2), min_notice VARCHAR(20),
  image VARCHAR(120), description TEXT, contents TEXT, serves VARCHAR(30), badge VARCHAR(30) NULL);
CREATE TABLE bulk_items (id VARCHAR(40) PRIMARY KEY, name VARCHAR(80), detail VARCHAR(80), price DECIMAL(10,2), image VARCHAR(120));
CREATE TABLE recipes (id INT PRIMARY KEY, name VARCHAR(100), subtitle VARCHAR(100), time VARCHAR(20), difficulty VARCHAR(20), serves INT,
  image VARCHAR(120), ingredients TEXT, method TEXT, produce TEXT, tags TEXT);
CREATE TABLE farmers (id INT PRIMARY KEY, name VARCHAR(80), region VARCHAR(60), location VARCHAR(60), image VARCHAR(120),
  produce TEXT, story TEXT, certifications TEXT, hectares DECIMAL(5,1) NULL, season VARCHAR(80));
CREATE TABLE orders (id INT AUTO_INCREMENT PRIMARY KEY, ref VARCHAR(20) UNIQUE, subtotal DECIMAL(10,2), delivery DECIMAL(10,2),
  pass_fee DECIMAL(10,2), total DECIMAL(10,2), delivery_date DATE, time_window VARCHAR(40), pay_method VARCHAR(20), phone VARCHAR(20) NULL,
  status VARCHAR(20) DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, item_key VARCHAR(60), name VARCHAR(120), type VARCHAR(20),
  price DECIMAL(10,2), qty INT, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE);
