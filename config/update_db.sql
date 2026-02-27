-- Actualizaciones necesarias para la base de datos existente

-- Agregar campo status a users si no existe
ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER credits;

-- Agregar campos para recuperación de contraseña
ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL AFTER status;
ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL AFTER reset_token;

-- Actualizar usuarios existentes a status active
UPDATE users SET status = 'active' WHERE status IS NULL;

-- Crear tabla social_accounts si no existe
CREATE TABLE IF NOT EXISTS social_accounts (
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

-- Crear usuario admin por defecto (password: admin123)
-- INSERT INTO users (name, email, password_hash, role, status, credits) 
-- VALUES ('Super Admin', 'admin@wowads.com', '$2y$10$abcdefghijklmnopqrstuv', 'admin', 'active', 0);
