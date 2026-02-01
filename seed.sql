USE reservations_app;

INSERT INTO tables (label, capacity) VALUES
('A1', 2), ('A2', 4), ('B3', 6), ('C1', 4), ('D2', 8), ('E4', 2);

INSERT INTO reservations (name, party_size, reservation_time)
VALUES ('John Smith', 4, '2026-01-10 19:00:00');

-- example ticket code: AB12CD34
INSERT INTO tickets (reservation_id, code)
VALUES (1, 'AB12CD34');
