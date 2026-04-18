-- ================================================================
--  WHERE IS MY FLIGHT — Seed Data
-- ================================================================

-- ─────────────────────────────────────────────
-- AIRPORTS — Major world airports
-- ─────────────────────────────────────────────
INSERT INTO airports (iata_code, icao_code, name, city, country, latitude, longitude, timezone, total_gates) VALUES
('JFK', 'KJFK', 'John F. Kennedy International Airport',   'New York',     'United States', 40.6413111, -73.7781391, 'America/New_York', 128),
('LAX', 'KLAX', 'Los Angeles International Airport',        'Los Angeles',  'United States', 33.9425361, -118.4080744, 'America/Los_Angeles', 146),
('ORD', 'KORD', 'O''Hare International Airport',            'Chicago',      'United States', 41.9741625, -87.9073214, 'America/Chicago', 191),
('LHR', 'EGLL', 'London Heathrow Airport',                  'London',       'United Kingdom', 51.4700223, -0.4542955, 'Europe/London', 115),
('CDG', 'LFPG', 'Charles de Gaulle Airport',                'Paris',        'France', 49.0096906, 2.5479245, 'Europe/Paris', 220),
('DXB', 'OMDB', 'Dubai International Airport',              'Dubai',        'United Arab Emirates', 25.2531745, 55.3656728, 'Asia/Dubai', 135),
('SIN', 'WSSS', 'Singapore Changi Airport',                 'Singapore',    'Singapore', 1.3644202, 103.9915308, 'Asia/Singapore', 110),
('HND', 'RJTT', 'Tokyo Haneda Airport',                     'Tokyo',        'Japan', 35.5493932, 139.7798386, 'Asia/Tokyo', 94),
('DEL', 'VIDP', 'Indira Gandhi International Airport',      'New Delhi',    'India', 28.5561624, 77.0999578, 'Asia/Kolkata', 78),
('BOM', 'VABB', 'Chhatrapati Shivaji Maharaj International','Mumbai',       'India', 19.0895595, 72.8656144, 'Asia/Kolkata', 62),
('CCU', 'VECC', 'Netaji Subhas Chandra Bose International', 'Kolkata',      'India', 22.6520367, 88.4463573, 'Asia/Kolkata', 28),
('BBI', 'VEBS', 'Biju Patnaik International Airport',       'Bhubaneswar',  'India', 20.2443898, 85.8177936, 'Asia/Kolkata', 8),
('SFO', 'KSFO', 'San Francisco International Airport',      'San Francisco','United States', 37.6213129, -122.3789554, 'America/Los_Angeles', 115),
('ATL', 'KATL', 'Hartsfield-Jackson Atlanta International', 'Atlanta',      'United States', 33.6407282, -84.4277236, 'America/New_York', 195);

-- ─────────────────────────────────────────────
-- AIRLINES
-- ─────────────────────────────────────────────
INSERT INTO airlines (iata_code, icao_code, name, country) VALUES
('AA', 'AAL', 'American Airlines',          'United States'),
('UA', 'UAL', 'United Airlines',            'United States'),
('DL', 'DAL', 'Delta Air Lines',            'United States'),
('BA', 'BAW', 'British Airways',            'United Kingdom'),
('AF', 'AFR', 'Air France',                 'France'),
('EK', 'UAE', 'Emirates',                   'United Arab Emirates'),
('SQ', 'SIA', 'Singapore Airlines',         'Singapore'),
('AI', 'AIC', 'Air India',                  'India'),
('6E', 'IGO', 'IndiGo',                     'India'),
('UK', 'VTI', 'Vistara',                    'India'),
('SG', 'SEJ', 'SpiceJet',                   'India'),
('NH', 'ANA', 'All Nippon Airways',         'Japan');

-- ─────────────────────────────────────────────
-- DEMO USER (password: demo123)
-- ─────────────────────────────────────────────
INSERT INTO users (id, email, password_hash, display_name, trust_level, is_verified) VALUES
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'demo@wimf.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Traveler', 3, TRUE);

INSERT INTO trust_scores (user_id, accuracy_rate, composite_score, total_contributions, verified_contributions) VALUES
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 0.8500, 0.7800, 42, 36);

-- ─────────────────────────────────────────────
-- SAMPLE FLIGHTS (today's date dynamically)
-- ─────────────────────────────────────────────
INSERT INTO flights (flight_number, airline_id, departure_airport_id, arrival_airport_id, scheduled_departure, scheduled_arrival, status, departure_gate, departure_terminal, aircraft_type, flight_date) VALUES
('AA1234', 1, 1, 2, CURRENT_DATE + INTERVAL '8 hours',  CURRENT_DATE + INTERVAL '13 hours 30 minutes', 'scheduled', 'B22', 'T1', 'Boeing 777-300ER', CURRENT_DATE),
('UA5678', 2, 3, 1, CURRENT_DATE + INTERVAL '9 hours',  CURRENT_DATE + INTERVAL '12 hours 15 minutes', 'boarding',  'C14', 'T2', 'Boeing 787-9', CURRENT_DATE),
('DL9012', 3, 2, 3, CURRENT_DATE + INTERVAL '10 hours', CURRENT_DATE + INTERVAL '14 hours 45 minutes', 'scheduled', NULL,   'T5', 'Airbus A350-900', CURRENT_DATE),
('BA3456', 4, 4, 1, CURRENT_DATE + INTERVAL '7 hours',  CURRENT_DATE + INTERVAL '15 hours',            'delayed',   'A8',  'T5', 'Airbus A380-800', CURRENT_DATE),
('EK7890', 6, 6, 4, CURRENT_DATE + INTERVAL '6 hours',  CURRENT_DATE + INTERVAL '12 hours 30 minutes', 'in_air',    'D12', 'T3', 'Boeing 777-200LR', CURRENT_DATE),
('6E2345', 9, 9, 11, CURRENT_DATE + INTERVAL '5 hours',  CURRENT_DATE + INTERVAL '7 hours 30 minutes', 'scheduled', 'G3',  'T3', 'Airbus A320neo', CURRENT_DATE),
('AI6789', 8, 10, 9, CURRENT_DATE + INTERVAL '11 hours', CURRENT_DATE + INTERVAL '13 hours 30 minutes','scheduled', NULL,   'T2', 'Boeing 787-8', CURRENT_DATE),
('SQ1122', 7, 7, 8, CURRENT_DATE + INTERVAL '14 hours', CURRENT_DATE + INTERVAL '21 hours',            'scheduled', 'A15', 'T3', 'Airbus A350-900ULR', CURRENT_DATE);

-- ─────────────────────────────────────────────
-- SAMPLE PREDICTIONS
-- ─────────────────────────────────────────────
INSERT INTO predictions (flight_id, delay_probability, estimated_delay_min, primary_cause, secondary_cause, model_version, weather_condition, wind_speed_kts, visibility_miles)
SELECT id, 0.7200, 35, 'weather', 'congestion', 'v1.0.0', 'thunderstorm', 25.0, 3.5
FROM flights WHERE flight_number = 'BA3456';

INSERT INTO predictions (flight_id, delay_probability, estimated_delay_min, primary_cause, model_version, weather_condition, wind_speed_kts, visibility_miles)
SELECT id, 0.1500, 0, 'none', 'v1.0.0', 'clear', 8.0, 10.0
FROM flights WHERE flight_number = 'AA1234';
