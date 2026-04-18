<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WIMF-specific tables: airports, airlines, flights, gate_contributions,
 * trust_scores, corroborations, flight_watches, predictions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add WIMF-specific columns to existing users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name', 100)->nullable()->after('name');
            $table->string('avatar_url')->nullable();
            $table->integer('trust_level')->default(1);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_moderator')->default(false);
            $table->string('password_hash')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('total_contributions')->default(0);
            $table->integer('accurate_contributions')->default(0);
        });

        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('iata_code', 3)->unique();
            $table->string('icao_code', 4)->nullable();
            $table->string('name');
            $table->string('city');
            $table->string('country');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone')->nullable();
            $table->integer('total_gates')->nullable();
            $table->timestamps();
        });

        Schema::create('airlines', function (Blueprint $table) {
            $table->id();
            $table->string('iata_code', 2)->unique();
            $table->string('icao_code', 3)->nullable();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        Schema::create('flights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('flight_number', 10);
            $table->unsignedBigInteger('airline_id')->nullable();
            $table->unsignedBigInteger('departure_airport_id')->nullable();
            $table->unsignedBigInteger('arrival_airport_id')->nullable();
            $table->timestamp('scheduled_departure')->nullable();
            $table->timestamp('scheduled_arrival')->nullable();
            $table->timestamp('actual_departure')->nullable();
            $table->timestamp('actual_arrival')->nullable();
            $table->timestamp('estimated_departure')->nullable();
            $table->timestamp('estimated_arrival')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->string('departure_gate', 10)->nullable();
            $table->string('arrival_gate', 10)->nullable();
            $table->string('departure_terminal', 10)->nullable();
            $table->string('arrival_terminal', 10)->nullable();
            $table->string('baggage_claim', 20)->nullable();
            $table->string('aircraft_type', 10)->nullable();
            $table->string('aircraft_reg', 20)->nullable();
            $table->integer('delay_minutes')->default(0);
            $table->string('delay_reason')->nullable();
            $table->string('external_id')->nullable();
            $table->date('flight_date');
            $table->timestamps();

            $table->foreign('airline_id')->references('id')->on('airlines')->nullOnDelete();
            $table->foreign('departure_airport_id')->references('id')->on('airports')->nullOnDelete();
            $table->foreign('arrival_airport_id')->references('id')->on('airports')->nullOnDelete();
            $table->index(['flight_number', 'flight_date']);
            $table->index('status');
        });

        Schema::create('gate_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('flight_id');
            $table->uuid('user_id');
            $table->string('gate_number', 10);
            $table->string('terminal', 10)->nullable();
            $table->string('contribution_type', 30)->default('gate_update');
            $table->decimal('confidence_score', 6, 4)->default(0.5);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_live')->default(false);
            $table->uuid('verified_by')->nullable();
            $table->text('moderation_note')->nullable();
            $table->integer('corroboration_count')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->foreign('flight_id')->references('id')->on('flights')->cascadeOnDelete();
            $table->index(['flight_id', 'is_live']);
        });

        Schema::create('trust_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->decimal('accuracy_rate', 6, 4)->default(0.5);
            $table->decimal('recency_weight', 6, 4)->default(1.0);
            $table->decimal('volume_bonus', 6, 4)->default(0.0);
            $table->decimal('composite_score', 6, 4)->default(0.5);
            $table->integer('total_contributions')->default(0);
            $table->integer('verified_contributions')->default(0);
            $table->integer('disputed_contributions')->default(0);
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('corroborations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contribution_id');
            $table->uuid('user_id');
            $table->boolean('agrees')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->foreign('contribution_id')->references('id')->on('gate_contributions')->cascadeOnDelete();
            $table->unique(['contribution_id', 'user_id']);
        });

        Schema::create('flight_watches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('flight_id');
            $table->boolean('notify_gate_change')->default(true);
            $table->boolean('notify_delay')->default(true);
            $table->boolean('notify_status')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->foreign('flight_id')->references('id')->on('flights')->cascadeOnDelete();
            $table->unique(['user_id', 'flight_id']);
        });

        Schema::create('predictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('flight_id');
            $table->decimal('delay_probability', 5, 4)->default(0.0);
            $table->integer('estimated_delay_min')->default(0);
            $table->integer('confidence_interval_low')->nullable();
            $table->integer('confidence_interval_high')->nullable();
            $table->string('primary_cause')->nullable();
            $table->string('secondary_cause')->nullable();
            $table->string('model_version')->nullable();
            $table->json('feature_vector')->nullable();
            $table->string('weather_condition')->nullable();
            $table->decimal('wind_speed_kts', 6, 2)->nullable();
            $table->decimal('visibility_miles', 6, 2)->nullable();
            $table->integer('ceiling_feet')->nullable();
            $table->timestamps();

            $table->foreign('flight_id')->references('id')->on('flights')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
        Schema::dropIfExists('flight_watches');
        Schema::dropIfExists('corroborations');
        Schema::dropIfExists('trust_scores');
        Schema::dropIfExists('gate_contributions');
        Schema::dropIfExists('flights');
        Schema::dropIfExists('airlines');
        Schema::dropIfExists('airports');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name', 'avatar_url', 'trust_level', 'is_verified',
                'is_moderator', 'password_hash', 'last_login_at',
                'total_contributions', 'accurate_contributions',
            ]);
        });
    }
};
