// ================================================================
//  WHERE IS MY FLIGHT — Play Framework (Scala) Build Configuration
// ================================================================

name := "wimf-api"
organization := "app.wimf"
version := "1.0.0-SNAPSHOT"
scalaVersion := "3.4.1"

lazy val root = (project in file("."))
  .enablePlugins(PlayScala)
  .settings(
    libraryDependencies ++= Seq(
      // ── Play Framework Core ──
      guice,
      ws,
      filters,

      // ── Database ──
      "org.playframework"     %% "play-slick"            % "6.1.0",
      "org.playframework"     %% "play-slick-evolutions" % "6.1.0",
      "org.postgresql"        %  "postgresql"             % "42.7.1",

      // ── Redis (Lettuce) ──
      "io.lettuce"            %  "lettuce-core"          % "6.3.1.RELEASE",

      // ── Kafka ──
      "org.apache.kafka"      %  "kafka-clients"         % "3.6.1",
      "org.apache.kafka"      %% "kafka-streams-scala"   % "3.6.1",

      // ── JSON ──
      "com.typesafe.play"     %% "play-json"             % "2.10.3",
      "io.circe"              %% "circe-core"            % "0.14.6",
      "io.circe"              %% "circe-generic"         % "0.14.6",
      "io.circe"              %% "circe-parser"          % "0.14.6",

      // ── HTTP Client (for external flight APIs) ──
      "com.softwaremill.sttp.client3" %% "core" % "3.9.2",
      "com.softwaremill.sttp.client3" %% "async-http-client-backend-future" % "3.9.2",

      // ── Caching ──
      "com.github.ben-manes.caffeine" % "caffeine" % "3.1.8",

      // ── Testing ──
      "org.scalatestplus.play" %% "scalatestplus-play"   % "7.0.0" % Test,
      "org.mockito"            %% "mockito-scala"        % "1.17.30" % Test,
    ),

    // ── Compiler Options ──
    scalacOptions ++= Seq(
      "-deprecation",
      "-feature",
      "-unchecked",
      "-Xfatal-warnings",
    ),

    // ── Play Configuration ──
    PlayKeys.playDefaultPort := 9000,
  )
