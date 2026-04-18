package wimf.prediction

import org.apache.spark.sql.{DataFrame, SparkSession}
import org.apache.spark.sql.functions._
import org.apache.spark.sql.types._
import org.apache.spark.ml.Pipeline
import org.apache.spark.ml.feature._
import org.apache.spark.ml.classification.RandomForestClassifier
import org.apache.spark.ml.regression.RandomForestRegressor

/**
 * FeatureExtractor — Transforms raw flight/weather data into ML-ready features.
 *
 * Feature Categories:
 * ─────────────────────────────────────────────────────────────
 * 1. Temporal:     day_of_week, hour_of_day, month, is_weekend, is_holiday_season
 * 2. Route:        departure_airport (indexed), arrival_airport (indexed)
 * 3. Airline:      airline_code (indexed)
 * 4. Weather:      condition (indexed), wind_speed, visibility, precipitation, ceiling
 * 5. Congestion:   airport_congestion score (0-1)
 * 6. Historical:   avg_delay_for_route, delay_rate_for_airline, delay_rate_for_hour
 *
 * All categorical features are StringIndexed then OneHotEncoded.
 * Numerical features are assembled directly into the feature vector.
 */
class FeatureExtractor {

  // Categorical columns to encode
  private val categoricalCols = Seq(
    "departure_iata", "arrival_iata", "airline_iata", "weather_condition"
  )

  // Numerical columns to include directly
  private val numericalCols = Seq(
    "day_of_week", "hour_of_day", "month",
    "wind_speed_kts", "visibility_miles", "precipitation_mm",
    "airport_congestion",
    "is_weekend", "is_peak_hour", "is_winter"
  )

  /**
   * Extract features from historical delay training data.
   */
  def extractFeatures(raw: DataFrame): DataFrame = {
    raw
      .withColumn("day_of_week",
        dayofweek(col("scheduled_departure")).cast(DoubleType))
      .withColumn("hour_of_day",
        hour(col("scheduled_departure")).cast(DoubleType))
      .withColumn("month",
        month(col("scheduled_departure")).cast(DoubleType))
      .withColumn("is_weekend",
        when(dayofweek(col("scheduled_departure")).isin(1, 7), 1.0).otherwise(0.0))
      .withColumn("is_peak_hour",
        when(hour(col("scheduled_departure")).between(6, 9) ||
             hour(col("scheduled_departure")).between(16, 20), 1.0).otherwise(0.0))
      .withColumn("is_winter",
        when(month(col("scheduled_departure")).isin(12, 1, 2), 1.0).otherwise(0.0))
      // Handle nulls in numeric columns
      .na.fill(0.0, numericalCols)
      // Handle nulls in categorical columns
      .na.fill("unknown", categoricalCols)
      // Cast delay_minutes to double for ML
      .withColumn("delay_minutes", col("delay_minutes").cast(DoubleType))
  }

  /**
   * Extract features for active flight prediction (no labels needed).
   */
  def extractFeaturesForPrediction(flights: DataFrame): DataFrame = {
    flights
      .withColumn("day_of_week",
        dayofweek(col("scheduled_departure")).cast(DoubleType))
      .withColumn("hour_of_day",
        hour(col("scheduled_departure")).cast(DoubleType))
      .withColumn("month",
        month(col("scheduled_departure")).cast(DoubleType))
      .withColumn("is_weekend",
        when(dayofweek(col("scheduled_departure")).isin(1, 7), 1.0).otherwise(0.0))
      .withColumn("is_peak_hour",
        when(hour(col("scheduled_departure")).between(6, 9) ||
             hour(col("scheduled_departure")).between(16, 20), 1.0).otherwise(0.0))
      .withColumn("is_winter",
        when(month(col("scheduled_departure")).isin(12, 1, 2), 1.0).otherwise(0.0))
      // Fill defaults for weather (may not have data yet)
      .withColumn("weather_condition", coalesce(col("weather_condition"), lit("unknown")))
      .withColumn("wind_speed_kts", coalesce(col("wind_speed_kts"), lit(0.0)))
      .withColumn("visibility_miles", coalesce(col("visibility_miles"), lit(10.0)))
      .withColumn("precipitation_mm", coalesce(col("precipitation_mm"), lit(0.0)))
      .withColumn("airport_congestion", coalesce(col("airport_congestion"), lit(0.3)))
      .na.fill("unknown", categoricalCols)
  }

  /**
   * Build the classifier pipeline (StringIndex → OneHot → VectorAssemble → RF).
   */
  def buildClassifierPipeline(classifier: RandomForestClassifier): Pipeline = {
    buildPipeline(classifier)
  }

  /**
   * Build the regressor pipeline.
   */
  def buildRegressorPipeline(regressor: RandomForestRegressor): Pipeline = {
    buildPipeline(regressor)
  }

  /**
   * Internal: Build a general ML pipeline with feature encoding stages.
   */
  private def buildPipeline(estimator: Any): Pipeline = {
    // StringIndexer stages for categorical columns
    val indexers = categoricalCols.map { col =>
      new StringIndexer()
        .setInputCol(col)
        .setOutputCol(s"${col}_idx")
        .setHandleInvalid("keep")
    }

    // OneHotEncoder stages
    val encoders = categoricalCols.map { col =>
      new OneHotEncoder()
        .setInputCol(s"${col}_idx")
        .setOutputCol(s"${col}_vec")
        .setDropLast(true)
    }

    // Assemble all features into a single vector
    val featureCols =
      categoricalCols.map(c => s"${c}_vec") ++
      numericalCols

    val assembler = new VectorAssembler()
      .setInputCols(featureCols.toArray)
      .setOutputCol("features")
      .setHandleInvalid("skip")

    // Build the pipeline
    val stages = indexers ++ encoders ++ Seq(assembler, estimator.asInstanceOf[org.apache.spark.ml.PipelineStage])
    new Pipeline().setStages(stages.toArray)
  }

  /**
   * Determine the primary delay cause based on feature importance
   * and current conditions.
   */
  def determinePrimaryCause(
    weatherCondition: String,
    windSpeedKts: Double,
    visibilityMiles: Double,
    congestion: Double,
    hourOfDay: Int
  ): String = {
    // Priority-based cause determination
    if (weatherCondition.toLowerCase.contains("thunderstorm") ||
        weatherCondition.toLowerCase.contains("snow") ||
        windSpeedKts > 35 ||
        visibilityMiles < 1.0) {
      "weather"
    } else if (congestion > 0.8) {
      "congestion"
    } else if (hourOfDay >= 17 && hourOfDay <= 21 && congestion > 0.6) {
      "atc"  // ATC flow control during evening rush
    } else if (weatherCondition.toLowerCase.contains("rain") || windSpeedKts > 25) {
      "weather"
    } else if (congestion > 0.5) {
      "aircraft_rotation"
    } else if (hourOfDay >= 22 || hourOfDay <= 5) {
      "crew"  // Crew rest requirements during off-peak
    } else {
      "none"
    }
  }
}
