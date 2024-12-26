<?php
// Set the correct content type
header('Content-Type: application/json');

// Load configuration and helpers
$config = include 'config.php';
require_once 'helpers.php';

// Directory containing station files
$dataDir = $config['directory'];

// Station ID to Name Mapping
$stationNames = $config['stations'];

// Define metrics and their corresponding units
$metrics = $config['metrics'];

// Get the station parameter from the query string
$stationParam = $_GET['station'] ?? null;

// Validate the station parameter
if (!$stationParam) {
    respondWithError('No station specified');
}

// Resolve station ID and name
[$stationId, $stationName] = resolveStation($stationParam, $stationNames);

// Find the latest data file for the station
$latestFile = findLatestFile($dataDir, $stationId);
if (!$latestFile) {
    respondWithError("No data files found for station: $stationId");
}

// Extract and format the timestamp from the file name
$timestamp = extractTimestampFromFileName($latestFile, $stationId);
if (!$timestamp) {
    respondWithError('Failed to extract timestamp from file name');
}

// Read and process the latest file
$data = readAndProcessFile($latestFile, $metrics);
if (!$data) {
    respondWithError("No data available in the latest file for station: $stationId");
}

// Output the JSON response
echo json_encode([
    'station' => $stationName,
    'station_id' => $stationId,
    'file' => basename($latestFile),
    'timestamp' => $timestamp,
    'units' => $metrics,
    'data' => $data,
], JSON_PRETTY_PRINT);
