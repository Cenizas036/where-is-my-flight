// ================================================================
//  WHERE IS MY FLIGHT — Scala.js Frontend Build Configuration
// ================================================================
//  Compiles reactive UI components to optimized JavaScript:
//    - FlightBoard: Real-time departure/arrival board
//    - PredictionCard: Delay probability badges
//    - GateEditWidget: Community gate editing
//    - DelayBadge: Visual delay indicators
//    - WebSocketClient: Persistent WS connection manager
// ================================================================

name := "wimf-frontend"
organization := "app.wimf"
version := "1.0.0-SNAPSHOT"
scalaVersion := "3.4.1"

enablePlugins(ScalaJSPlugin)

// ── Scala.js Settings ──
scalaJSUseMainModuleInitializer := true
scalaJSLinkerConfig ~= { _.withModuleKind(ModuleKind.ESModule) }

libraryDependencies ++= Seq(
  // Scala.js DOM API
  "org.scala-js"       %%% "scalajs-dom"    % "2.8.0",

  // Laminar — Reactive UI library for Scala.js (like React but functional)
  "com.raquo"          %%% "laminar"        % "17.0.0",

  // Airstream — Reactive streams (comes with Laminar)
  "com.raquo"          %%% "airstream"      % "17.0.0",

  // HTTP client for Scala.js
  "org.scalajs"        %%% "scalajs-java-securerandom" % "1.0.0",

  // JSON
  "io.circe"           %%% "circe-core"     % "0.14.6",
  "io.circe"           %%% "circe-generic"  % "0.14.6",
  "io.circe"           %%% "circe-parser"   % "0.14.6",
  "io.circe"           %%% "circe-scalajs"  % "0.14.6",

  // Testing
  "org.scalameta"      %%% "munit"          % "0.7.29" % Test,
)

// ── Output ──
// Compiled JS goes to: target/scala-3.4.1/wimf-frontend-opt/
// This is mounted into Laravel's public/js/scalajs/ via Docker volume
