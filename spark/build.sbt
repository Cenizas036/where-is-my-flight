// ================================================================
//  WHERE IS MY FLIGHT — Spark Delay Prediction Pipeline
// ================================================================
//  Trains a Random Forest model on historical delay data segmented
//  by route, airline, time-of-day, weather, and airport congestion.
// ================================================================

name := "wimf-prediction"
organization := "app.wimf"
version := "1.0.0-SNAPSHOT"
scalaVersion := "2.13.12"  // Spark 3.5 requires Scala 2.13

libraryDependencies ++= Seq(
  // ── Spark Core ──
  "org.apache.spark"  %% "spark-core"    % "3.5.0" % "provided",
  "org.apache.spark"  %% "spark-sql"     % "3.5.0" % "provided",
  "org.apache.spark"  %% "spark-mllib"   % "3.5.0" % "provided",

  // ── Database ──
  "org.postgresql"    %  "postgresql"     % "42.7.1",

  // ── Redis (for publishing predictions) ──
  "io.lettuce"        %  "lettuce-core"   % "6.3.1.RELEASE",

  // ── JSON ──
  "io.circe"          %% "circe-core"     % "0.14.6",
  "io.circe"          %% "circe-generic"  % "0.14.6",
  "io.circe"          %% "circe-parser"   % "0.14.6",

  // ── Testing ──
  "org.scalatest"     %% "scalatest"      % "3.2.17" % Test,
)

// ── Assembly Settings ──
assembly / assemblyMergeStrategy := {
  case PathList("META-INF", xs @ _*) => MergeStrategy.discard
  case "reference.conf"               => MergeStrategy.concat
  case x                              => MergeStrategy.first
}

assembly / assemblyJarName := "wimf-prediction-assembly.jar"
