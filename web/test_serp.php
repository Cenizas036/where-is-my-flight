<?php
$json = file_get_contents('https://serpapi.com/search.json?engine=google_flights&departure_id=DEL&arrival_id=BOM&outbound_date=2026-04-20&currency=INR&hl=en&api_key=6dd624adab02958e8fea4cefcd6e2eb03540614abc5453d57167227e593ba4fc&type=2');
$data = json_decode($json, true);
echo "KEYS: " . implode(", ", array_keys($data)) . "\n";
print_r($data['best_flights'][0] ?? null);
