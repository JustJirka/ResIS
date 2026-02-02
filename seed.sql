USE reservations_app;

INSERT INTO tables (label, position_x, position_y, capacity) VALUES
('A1',50.0,100.0, 2), ('A2',30.0,90.0, 4), ('B3',40.0,80.0, 6), ('C1',20.0,70.0, 4), ('D2',10.0,60.0, 8), ('E4',5.0,50.0, 2);

INSERT INTO reservations (name, party_size, reservation_time)
VALUES ('John Smith', 4, '2026-01-10 19:00:00');

-- example ticket code: AB12CD34
INSERT INTO tickets (reservation_id, code)
VALUES (1, 'AB12CD34');
