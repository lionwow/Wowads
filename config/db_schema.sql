-- Database schema for WowAds (MySQL)

-- Primero creamos agencies (sin FK a users)
CREATE TABLE agencies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  website VARCHAR(255),
  owner_user_id INT,
  status VARCHAR(50) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Ahora creamos users con FK a agencies
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','agency','client') NOT NULL DEFAULT 'client',
  credits DECIMAL(10,2) DEFAULT 0,
  status VARCHAR(20) DEFAULT 'active',
  agency_id INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL
);

-- Actualizamos agencies con FK a users (después de que users exista)
ALTER TABLE agencies ADD FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  status VARCHAR(50) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

CREATE TABLE campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT,
  client_id INT,
  name VARCHAR(255) NOT NULL,
  status VARCHAR(50) DEFAULT 'draft',
  budget DECIMAL(10,2) DEFAULT 0,
  start_date DATE,
  end_date DATE,
  config JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

CREATE TABLE ads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT,
  name VARCHAR(255),
  status VARCHAR(50) DEFAULT 'draft',
  ad_type VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

CREATE TABLE creatives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT,
  type ENUM('image','video','copy') NOT NULL,
  content TEXT,
  metadata JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
);

CREATE TABLE strategies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  config JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE brand_dna (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT,
  user_id INT,
  social_account_id INT,
  fanpage_id VARCHAR(100),
  fanpage_name VARCHAR(255),
  name VARCHAR(255) NOT NULL,
  description TEXT,
  brand_values TEXT,
  business_description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Social Accounts (Facebook, TikTok, Google)
CREATE TABLE social_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  agency_id INT,
  platform VARCHAR(50) NOT NULL,
  account_id VARCHAR(255) NOT NULL,
  account_name VARCHAR(255) NOT NULL,
  access_token TEXT,
  refresh_token TEXT,
  token_expires DATETIME,
  status VARCHAR(20) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL
);

CREATE TABLE ai_generations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  creative_id INT,
  user_id INT,
  type ENUM('image','copy','video') NOT NULL,
  prompt TEXT,
  result TEXT,
  provider VARCHAR(50),
  cost DECIMAL(10,4) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (creative_id) REFERENCES creatives(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Images table
CREATE TABLE images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  agency_id INT,
  social_account_id INT,
  name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500),
  prompt TEXT,
  status VARCHAR(20) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL,
  FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE SET NULL
);

-- Sample strategies
INSERT INTO strategies (name, description, config) VALUES
('E-commerce Ventas', 'Estrategia enfocada en conversiones para tiendas online', '{"objective":"conversions","placement":["facebook","instagram"]}'),
('Awareness Marca', 'Estrategia para aumentar reconocimiento de marca', '{"objective":"brand_awareness","placement":["facebook","instagram","audience_network"]}'),
('Tráfico Web', 'Estrategia para dirigir tráfico al sitio web', '{"objective":"traffic","placement":["facebook","instagram"]}'),
('Leads y Captación', 'Estrategia para generar leads y contactos', '{"objective":"lead_generation","placement":["facebook","instagram"]}');

-- Settings table for API credentials
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
