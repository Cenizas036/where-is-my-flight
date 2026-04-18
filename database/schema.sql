-- ================================================================
--  WHERE IS MY FLIGHT — PostgreSQL Schema
--  Database: wimf
-- ================================================================

-- Enable extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";     -- trigram search for flight/airport lookup
CREATE EXTENSION IF NOT EXISTS "btree_gist";  -- needed for exclusion constraints

-- ─────────────────────────────────────────────
-- USERS — Passenger accounts
-- ─────────────────────────────────────────────
CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email           VARCHAR(255) UNIQUE NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    display_name    VARCHAR(100) NOT NULL,
    avatar_url      TEXT,
    trust_level     SMALLINT NOT NULL DEFAULT 1 CHECK (trust_level BETWEEN 1 AND 5),
    total_contributions INTEGER NOT NULL DEFAULT 0,
    accurate_contributions INTEGER NOT NULL DEFAULT 0,
    is_verified     BOOLEAN NOT NULL DEFAULT FALSE,
    is_moderator    BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    last_login_at   TIMESTAMPTZ
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_trust ON users(trust_level);

-- ─────────────────────────────────────────────
-- AIRPORTS — Reference data
-- ─────────────────────────────────────────────
CREATE TABLE airports (
    id              SERIAL PRIMARY KEY,
    iata_code       CHAR(3) UNIQUE NOT NULL,
    icao_code       CHAR(4) UNIQUE,
    name            VARCHAR(255) NOT NULL,
    city            VARCHAR(100) NOT NULL,
    country         VARCHAR(100) NOT NULL,
    latitude        DECIMAL(10, 7) NOT NULL,
    longitude       DECIMAL(10, 7) NOT NULL,
    timezone        VARCHAR(50) NOT NULL,
    total_gates     INTEGER,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_airports_iata ON airports(iata_code);
CREATE INDEX idx_airports_city ON airports USING gin(city gin_trgm_ops);

-- ─────────────────────────────────────────────
-- AIRLINES — Reference data
-- ─────────────────────────────────────────────
CREATE TABLE airlines (
    id              SERIAL PRIMARY KEY,
    iata_code       CHAR(2) UNIQUE NOT NULL,
    icao_code       CHAR(3) UNIQUE,
    name            VARCHAR(255) NOT NULL,
    country         VARCHAR(100),
    logo_url        TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_airlines_iata ON airlines(iata_code);

-- ─────────────────────────────────────────────
-- FLIGHTS — Core flight records
-- ─────────────────────────────────────────────
CREATE TABLE flights (
    id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    flight_number       VARCHAR(10) NOT NULL,          -- e.g. "AA1234"
    airline_id          INTEGER REFERENCES airlines(id),
    departure_airport_id INTEGER NOT NULL REFERENCES airports(id),
    arrival_airport_id  INTEGER NOT NULL REFERENCES airports(id),
    
    -- Schedule
    scheduled_departure TIMESTAMPTZ NOT NULL,
    scheduled_arrival   TIMESTAMPTZ NOT NULL,
    actual_departure    TIMESTAMPTZ,
    actual_arrival      TIMESTAMPTZ,
    estimated_departure TIMESTAMPTZ,
    estimated_arrival   TIMESTAMPTZ,

    -- Status
    status              VARCHAR(20) NOT NULL DEFAULT 'scheduled'
                        CHECK (status IN ('scheduled', 'boarding', 'departed', 
                                          'in_air', 'landed', 'arrived', 
                                          'delayed', 'cancelled', 'diverted')),
    
    -- Gate info
    departure_gate      VARCHAR(10),
    arrival_gate        VARCHAR(10),
    departure_terminal  VARCHAR(10),
    arrival_terminal    VARCHAR(10),
    baggage_claim       VARCHAR(10),
    
    -- Aircraft
    aircraft_type       VARCHAR(50),
    aircraft_reg        VARCHAR(20),
    
    -- Delay info
    delay_minutes       INTEGER DEFAULT 0,
    delay_reason        VARCHAR(100),
    
    -- External
    external_id         VARCHAR(100),  -- ID from AviationStack/FlightAware
    
    -- Metadata
    flight_date         DATE NOT NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_flights_number ON flights(flight_number);
CREATE INDEX idx_flights_date ON flights(flight_date);
CREATE INDEX idx_flights_status ON flights(status);
CREATE INDEX idx_flights_departure ON flights(departure_airport_id, scheduled_departure);
CREATE INDEX idx_flights_arrival ON flights(arrival_airport_id, scheduled_arrival);
CREATE INDEX idx_flights_composite ON flights(flight_number, flight_date);

-- ─────────────────────────────────────────────
-- GATE CONTRIBUTIONS — Community gate edits
-- ─────────────────────────────────────────────
CREATE TABLE gate_contributions (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    flight_id       UUID NOT NULL REFERENCES flights(id) ON DELETE CASCADE,
    user_id         UUID NOT NULL REFERENCES users(id),
    
    gate_number     VARCHAR(10) NOT NULL,
    terminal        VARCHAR(10),
    contribution_type VARCHAR(20) NOT NULL DEFAULT 'gate_update'
                    CHECK (contribution_type IN ('gate_update', 'terminal_update', 
                                                  'baggage_update', 'status_update')),
    
    -- Trust & moderation
    confidence_score DECIMAL(4,3) NOT NULL DEFAULT 0.500,
    is_verified     BOOLEAN NOT NULL DEFAULT FALSE,
    is_live         BOOLEAN NOT NULL DEFAULT FALSE,
    verified_by     UUID REFERENCES users(id),
    moderation_note TEXT,
    
    -- Corroboration
    corroboration_count INTEGER NOT NULL DEFAULT 0,
    
    -- Location proof (optional — nearby airport check)
    latitude        DECIMAL(10, 7),
    longitude       DECIMAL(10, 7),
    
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_contributions_flight ON gate_contributions(flight_id);
CREATE INDEX idx_contributions_user ON gate_contributions(user_id);
CREATE INDEX idx_contributions_live ON gate_contributions(is_live) WHERE is_live = TRUE;
CREATE INDEX idx_contributions_pending ON gate_contributions(is_verified) WHERE is_verified = FALSE;

-- ─────────────────────────────────────────────
-- CORROBORATIONS — Users confirming others' edits
-- ─────────────────────────────────────────────
CREATE TABLE corroborations (
    id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    contribution_id     UUID NOT NULL REFERENCES gate_contributions(id) ON DELETE CASCADE,
    user_id             UUID NOT NULL REFERENCES users(id),
    agrees              BOOLEAN NOT NULL,  -- true = confirms, false = disputes
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE(contribution_id, user_id)  -- one vote per user per contribution
);

CREATE INDEX idx_corroborations_contribution ON corroborations(contribution_id);

-- ─────────────────────────────────────────────
-- TRUST SCORES — User reliability tracking
-- ─────────────────────────────────────────────
CREATE TABLE trust_scores (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Scoring components
    accuracy_rate   DECIMAL(5,4) NOT NULL DEFAULT 0.5000,
    recency_weight  DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
    volume_bonus    DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
    
    -- Composite
    composite_score DECIMAL(5,4) NOT NULL DEFAULT 0.5000,
    
    -- History
    total_contributions     INTEGER NOT NULL DEFAULT 0,
    verified_contributions  INTEGER NOT NULL DEFAULT 0,
    disputed_contributions  INTEGER NOT NULL DEFAULT 0,
    
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX idx_trust_user ON trust_scores(user_id);

-- ─────────────────────────────────────────────
-- PREDICTIONS — Delay prediction records
-- ─────────────────────────────────────────────
CREATE TABLE predictions (
    id                  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    flight_id           UUID NOT NULL REFERENCES flights(id) ON DELETE CASCADE,
    
    -- Prediction outputs
    delay_probability   DECIMAL(5,4) NOT NULL,           -- 0.0000 to 1.0000
    estimated_delay_min INTEGER NOT NULL DEFAULT 0,
    confidence_interval_low  INTEGER,
    confidence_interval_high INTEGER,
    
    -- Top causes (ranked)
    primary_cause       VARCHAR(50),   -- weather, atc, aircraft_rotation, crew, congestion
    secondary_cause     VARCHAR(50),
    
    -- Model metadata
    model_version       VARCHAR(20) NOT NULL,
    feature_vector      JSONB,         -- store the features used for this prediction
    
    -- Weather context at prediction time
    weather_condition   VARCHAR(50),
    wind_speed_kts      DECIMAL(5,1),
    visibility_miles    DECIMAL(5,1),
    ceiling_feet        INTEGER,
    
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_predictions_flight ON predictions(flight_id);
CREATE INDEX idx_predictions_created ON predictions(created_at DESC);

-- ─────────────────────────────────────────────
-- FLIGHT WATCHES — User flight tracking
-- ─────────────────────────────────────────────
CREATE TABLE flight_watches (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    flight_id       UUID NOT NULL REFERENCES flights(id) ON DELETE CASCADE,
    notify_gate_change  BOOLEAN NOT NULL DEFAULT TRUE,
    notify_delay        BOOLEAN NOT NULL DEFAULT TRUE,
    notify_status       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE(user_id, flight_id)
);

CREATE INDEX idx_watches_user ON flight_watches(user_id);
CREATE INDEX idx_watches_flight ON flight_watches(flight_id);

-- ─────────────────────────────────────────────
-- HISTORICAL DELAYS — Training data for Spark
-- ─────────────────────────────────────────────
CREATE TABLE historical_delays (
    id                  SERIAL PRIMARY KEY,
    flight_number       VARCHAR(10) NOT NULL,
    airline_iata        CHAR(2) NOT NULL,
    departure_iata      CHAR(3) NOT NULL,
    arrival_iata        CHAR(3) NOT NULL,
    flight_date         DATE NOT NULL,
    scheduled_departure TIMESTAMPTZ NOT NULL,
    actual_departure    TIMESTAMPTZ,
    delay_minutes       INTEGER NOT NULL DEFAULT 0,
    
    -- Weather at departure time
    weather_condition   VARCHAR(50),
    temperature_c       DECIMAL(4,1),
    wind_speed_kts      DECIMAL(5,1),
    visibility_miles    DECIMAL(5,1),
    precipitation_mm    DECIMAL(5,1),
    
    -- Airport context
    airport_congestion  DECIMAL(4,3),  -- 0-1 congestion score
    day_of_week         SMALLINT,
    hour_of_day         SMALLINT,
    
    -- Cause
    delay_cause         VARCHAR(50),
    
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_historical_route ON historical_delays(departure_iata, arrival_iata);
CREATE INDEX idx_historical_airline ON historical_delays(airline_iata);
CREATE INDEX idx_historical_date ON historical_delays(flight_date);

-- ─────────────────────────────────────────────
-- FUNCTIONS — Auto-update timestamps
-- ─────────────────────────────────────────────
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER trg_flights_updated_at
    BEFORE UPDATE ON flights
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER trg_contributions_updated_at
    BEFORE UPDATE ON gate_contributions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- ─────────────────────────────────────────────
-- VIEWS — Convenience queries
-- ─────────────────────────────────────────────
CREATE VIEW v_live_departures AS
SELECT 
    f.id,
    f.flight_number,
    al.name AS airline_name,
    al.logo_url AS airline_logo,
    dep.iata_code AS departure_iata,
    dep.name AS departure_airport,
    arr.iata_code AS arrival_iata,
    arr.name AS arrival_airport,
    f.scheduled_departure,
    f.estimated_departure,
    f.actual_departure,
    f.status,
    f.departure_gate,
    f.departure_terminal,
    f.delay_minutes,
    f.delay_reason,
    f.aircraft_type
FROM flights f
JOIN airports dep ON f.departure_airport_id = dep.id
JOIN airports arr ON f.arrival_airport_id = arr.id
LEFT JOIN airlines al ON f.airline_id = al.id
WHERE f.flight_date = CURRENT_DATE
  AND f.status NOT IN ('arrived', 'cancelled')
ORDER BY f.scheduled_departure ASC;

CREATE VIEW v_contribution_queue AS
SELECT 
    gc.id,
    gc.gate_number,
    gc.terminal,
    gc.contribution_type,
    gc.confidence_score,
    gc.corroboration_count,
    gc.created_at,
    u.display_name AS contributor_name,
    u.trust_level AS contributor_trust,
    ts.composite_score AS contributor_score,
    f.flight_number,
    f.scheduled_departure
FROM gate_contributions gc
JOIN users u ON gc.user_id = u.id
LEFT JOIN trust_scores ts ON u.id = ts.user_id
JOIN flights f ON gc.flight_id = f.id
WHERE gc.is_verified = FALSE
ORDER BY gc.confidence_score DESC, gc.created_at ASC;
