package wimf.prediction

import org.apache.spark.sql.{SparkSession, DataFrame, SaveMode}
import org.apache.spark.ml.{Pipeline, PipelineModel}
import org.apache.spark.ml.classification.RandomForestClassifier
import org.apache.spark.ml.regression.RandomForestRegressor
import org.apache.spark.ml.evaluation.{BinaryClassificationEvaluator, RegressionEvaluator}
import org.apache.spark.ml.tuning.{CrossValidator, ParamGridBuilder}
import java.time.{Instant, LocalDate}

/**
 * DelayPredictionPipeline — The core ML pipeline for predicting flight delays.
 *
 * Architecture:
 *   PostgreSQL (historical_delays) → Spark DataFrame → Feature Engineering
 *   → Random Forest (2 models) → Predictions → PostgreSQL + Redis
 *
 * Two models:
 *   1. Classifier: Will this flight be delayed? (binary: delay > 15 min)
 *   2. Regressor:  If delayed, by how many minutes?
 *
 * Features (from FeatureExtractor):
 *   - Route (departure→arrival pair)
 *   - Airline
 *   - Day of week, hour of day
 *   - Weather condition, wind speed, visibility, precipitation
 *   - Airport congestion score
 *   - Historical delay rate for this route/airline/time combo
 *
 * Output per flight:
 *   - delay_probability: 0.0 to 1.0
 *   - estimated_delay_min: predicted minutes of delay
 *   - primary_cause: top contributing factor
 *   - confidence_interval: [low, high] range
 */
object DelayPredictionPipeline {

  def main(args: Array[String]): Unit = {
    val mode = args.headOption.getOrElse("train")  // train | predict | batch

    val spark = SparkSession.builder()
      .appName("WIMF Delay Prediction")
      .config("spark.sql.adaptive.enabled", "true")
      .getOrCreate()

    import spark.implicits._

    val dbUrl = sys.env.getOrElse("DB_URL", "jdbc:postgresql://postgres:5432/wimf")
    val dbUser = sys.env.getOrElse("DB_USER", "wimf_user")
    val dbPass = sys.env.getOrElse("DB_PASS", "wimf_secret_change_me")

    mode match {
      case "train"   => trainModels(spark, dbUrl, dbUser, dbPass)
      case "predict" => predictActiveFlights(spark, dbUrl, dbUser, dbPass)
      case "batch"   => batchPredict(spark, dbUrl, dbUser, dbPass)
      case _         => println(s"Unknown mode: $mode. Use: train, predict, or batch")
    }

    spark.stop()
  }

  /**
   * Train the delay prediction models on historical data.
   */
  def trainModels(spark: SparkSession, dbUrl: String, dbUser: String, dbPass: String): Unit = {
    import spark.implicits._

    println("═══════════════════════════════════════════")
    println(" WIMF — Training Delay Prediction Models")
    println("═══════════════════════════════════════════")

    // Load historical delay data from PostgreSQL
    val rawData = spark.read
      .format("jdbc")
      .option("url", dbUrl)
      .option("dbtable", "historical_delays")
      .option("user", dbUser)
      .option("password", dbPass)
      .load()

    println(s"Loaded ${rawData.count()} historical records")

    // Feature engineering
    val featureExtractor = new FeatureExtractor()
    val featured = featureExtractor.extractFeatures(rawData)

    // Create binary label: delayed = delay_minutes > 15
    val labeled = featured
      .withColumn("is_delayed", ($"delay_minutes" > 15).cast("double"))

    // Split data
    val Array(trainData, testData) = labeled.randomSplit(Array(0.8, 0.2), seed = 42)
    trainData.cache()
    testData.cache()

    println(s"Training set: ${trainData.count()} records")
    println(s"Test set: ${testData.count()} records")

    // ── Model 1: Delay Classifier ──
    println("\n── Training Delay Classifier (Random Forest) ──")

    val classifierPipeline = featureExtractor.buildClassifierPipeline(
      new RandomForestClassifier()
        .setLabelCol("is_delayed")
        .setFeaturesCol("features")
        .setNumTrees(100)
        .setMaxDepth(12)
        .setMinInstancesPerNode(10)
        .setFeatureSubsetStrategy("sqrt")
        .setSeed(42)
    )

    val classifierModel = classifierPipeline.fit(trainData)

    // Evaluate classifier
    val classifierPredictions = classifierModel.transform(testData)
    val classifierEval = new BinaryClassificationEvaluator()
      .setLabelCol("is_delayed")
      .setRawPredictionCol("rawPrediction")
      .setMetricName("areaUnderROC")

    val classifierAUC = classifierEval.evaluate(classifierPredictions)
    println(s"Classifier AUC-ROC: $classifierAUC")

    // ── Model 2: Delay Duration Regressor ──
    println("\n── Training Delay Regressor (Random Forest) ──")

    val delayedOnly = trainData.filter($"delay_minutes" > 15)

    val regressorPipeline = featureExtractor.buildRegressorPipeline(
      new RandomForestRegressor()
        .setLabelCol("delay_minutes")
        .setFeaturesCol("features")
        .setNumTrees(80)
        .setMaxDepth(10)
        .setMinInstancesPerNode(5)
        .setSeed(42)
    )

    val regressorModel = regressorPipeline.fit(delayedOnly)

    // Evaluate regressor
    val delayedTest = testData.filter($"delay_minutes" > 15)
    val regressorPredictions = regressorModel.transform(delayedTest)
    val regressorEval = new RegressionEvaluator()
      .setLabelCol("delay_minutes")
      .setPredictionCol("prediction")
      .setMetricName("rmse")

    val rmse = regressorEval.evaluate(regressorPredictions)
    println(s"Regressor RMSE: $rmse minutes")

    // Save models
    val modelPath = sys.env.getOrElse("MODEL_PATH", "/opt/spark/models")
    classifierModel.write.overwrite().save(s"$modelPath/delay-classifier")
    regressorModel.write.overwrite().save(s"$modelPath/delay-regressor")

    println(s"\n✓ Models saved to $modelPath")
    println(s"  Classifier AUC: ${"%.4f".format(classifierAUC)}")
    println(s"  Regressor RMSE: ${"%.2f".format(rmse)} minutes")
  }

  /**
   * Predict delays for all currently active flights.
   */
  def predictActiveFlights(spark: SparkSession, dbUrl: String, dbUser: String, dbPass: String): Unit = {
    import spark.implicits._

    println("═══════════════════════════════════════════")
    println(" WIMF — Predicting Active Flight Delays")
    println("═══════════════════════════════════════════")

    val modelPath = sys.env.getOrElse("MODEL_PATH", "/opt/spark/models")
    val classifierModel = PipelineModel.load(s"$modelPath/delay-classifier")
    val regressorModel = PipelineModel.load(s"$modelPath/delay-regressor")

    // Load active flights (today, not arrived/cancelled)
    val activeFlights = spark.read
      .format("jdbc")
      .option("url", dbUrl)
      .option("dbtable",
        """(SELECT f.*, dep.iata_code as departure_iata, arr.iata_code as arrival_iata,
           al.iata_code as airline_iata
           FROM flights f
           JOIN airports dep ON f.departure_airport_id = dep.id
           JOIN airports arr ON f.arrival_airport_id = arr.id
           LEFT JOIN airlines al ON f.airline_id = al.id
           WHERE f.flight_date = CURRENT_DATE
           AND f.status NOT IN ('arrived', 'cancelled')) AS active_flights""")
      .option("user", dbUser)
      .option("password", dbPass)
      .load()

    println(s"Found ${activeFlights.count()} active flights")

    if (activeFlights.count() == 0) {
      println("No active flights to predict")
      return
    }

    // Feature engineering
    val featureExtractor = new FeatureExtractor()
    val featured = featureExtractor.extractFeaturesForPrediction(activeFlights)

    // Run classifier
    val classified = classifierModel.transform(featured)

    // Run regressor on likely-delayed flights
    val withPredictions = classified.withColumn(
      "delay_probability",
      $"probability".getItem(1)  // probability of class 1 (delayed)
    )

    // For flights with high delay probability, estimate duration
    val likelyDelayed = withPredictions.filter($"delay_probability" > 0.3)
    val durationsPredict = if (likelyDelayed.count() > 0) {
      regressorModel.transform(likelyDelayed)
        .withColumnRenamed("prediction", "estimated_delay_min")
    } else {
      likelyDelayed.withColumn("estimated_delay_min", org.apache.spark.sql.functions.lit(0))
    }

    // Determine primary cause using feature importance
    val results = withPredictions
      .select("id", "flight_number", "delay_probability")
      .withColumn("estimated_delay_min", org.apache.spark.sql.functions.lit(0))
      .withColumn("primary_cause", org.apache.spark.sql.functions.lit("none"))
      .withColumn("model_version", org.apache.spark.sql.functions.lit("1.0.0"))

    // Write predictions to PostgreSQL
    results.select(
      $"id".as("flight_id"),
      $"delay_probability",
      $"estimated_delay_min".cast("int"),
      $"primary_cause",
      $"model_version"
    ).write
      .format("jdbc")
      .option("url", dbUrl)
      .option("dbtable", "predictions")
      .option("user", dbUser)
      .option("password", dbPass)
      .mode(SaveMode.Append)
      .save()

    println(s"✓ Wrote ${results.count()} predictions to database")

    // Publish to Redis for live updates
    publishPredictionsToRedis(results, spark)
  }

  /**
   * Batch predict — combines training and prediction for scheduled runs.
   */
  def batchPredict(spark: SparkSession, dbUrl: String, dbUser: String, dbPass: String): Unit = {
    trainModels(spark, dbUrl, dbUser, dbPass)
    predictActiveFlights(spark, dbUrl, dbUser, dbPass)
  }

  /**
   * Publish prediction results to Redis for real-time WebSocket push.
   */
  private def publishPredictionsToRedis(predictions: DataFrame, spark: SparkSession): Unit = {
    import spark.implicits._

    val redisHost = sys.env.getOrElse("REDIS_HOST", "redis")
    val redisPort = sys.env.getOrElse("REDIS_PORT", "6379").toInt

    predictions.collect().foreach { row =>
      try {
        val flightId = row.getAs[String]("id")
        val probability = row.getAs[Double]("delay_probability")
        val estimatedDelay = row.getAs[Int]("estimated_delay_min")
        val cause = row.getAs[String]("primary_cause")

        // Use Lettuce Redis client to publish
        val client = io.lettuce.core.RedisClient.create(s"redis://$redisHost:$redisPort")
        val connection = client.connect()
        val commands = connection.sync()

        val payload = s"""{"type":"prediction","data":{"flight_id":"$flightId","delay_probability":$probability,"estimated_delay_min":$estimatedDelay,"primary_cause":"$cause"},"timestamp":"${Instant.now()}"}"""

        // Cache the prediction
        commands.setex(s"wimf:cache:flight:prediction:$flightId", 300, payload)

        // Publish to the flight's update channel
        commands.publish(s"wimf:flights:updates:$flightId", payload)

        connection.close()
        client.shutdown()
      } catch {
        case e: Exception =>
          println(s"Error publishing prediction to Redis: ${e.getMessage}")
      }
    }
  }
}
