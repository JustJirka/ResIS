CREATE DATABASE IF NOT EXISTS reservations_app
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservations_app;

CREATE TABLE reservations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NULL,
  party_size INT NULL,
  reservation_time DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tickets (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  reservation_id BIGINT UNSIGNED NOT NULL,
  code CHAR(8) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ticket_code (code),
  UNIQUE KEY uq_ticket_reservation (reservation_id),
  CONSTRAINT fk_ticket_reservation
    FOREIGN KEY (reservation_id) REFERENCES reservations(id)
    ON DELETE CASCADE
);

CREATE TABLE tables (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  label VARCHAR(50) NOT NULL,
  capacity INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE table_assignments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  ticket_id BIGINT UNSIGNED NOT NULL,
  table_id BIGINT UNSIGNED NOT NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_assignment_ticket (ticket_id),
  UNIQUE KEY uq_assignment_table (table_id),
  CONSTRAINT fk_assignment_ticket
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_assignment_table
    FOREIGN KEY (table_id) REFERENCES tables(id)
    ON DELETE RESTRICT
);
