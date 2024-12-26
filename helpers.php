<?php

/**
 * Get station IDs dynamically from file names in the specified directory.
 *
 * @param string $directory Path to the directory containing station files.
 * @return array Unique sorted station IDs.
 */
function getStationIdsFromFiles(string $directory): array
{
    $files = glob("$directory/DES_*.rep");
    $stationIds = [];

    foreach ($files as $file) {
        if (preg_match('/DES_(\d{5})/', basename($file), $matches)) {
            $stationIds[] = $matches[1];
        }
    }

    return array_unique($stationIds);
}

/**
 * Map station IDs to their names or use IDs as fallback.
 *
 * @param array $stationIds Array of station IDs.
 * @param array $stationNames Mapping of station IDs to names.
 * @return array Array of station names or IDs.
 */
function mapStations(array $stationIds, array $stationNames): array
{
    return array_map(fn($id) => $stationNames[$id] ?? $id, $stationIds);
}

/**
 * Simulate an API call and get the response as an associative array.
 *
 * @param string $station Selected station.
 * @return array Decoded API response.
 */
function getApiResponse(string $station): array
{
    $_GET['station'] = $station; // Set the station parameter
    ob_start(); // Start output buffering
    include 'api.php'; // Include the API script
    $response = ob_get_clean(); // Capture the API output

    header_remove('Content-Type'); // Clear headers set by api.php
    $data = json_decode($response, true);

    // Handle API errors
    if (isset($data['error'])) {
        die('API Error: ' . htmlspecialchars($data['error']));
    }

    return $data;
}

/**
 * Respond with a JSON error message and terminate the script.
 *
 * @param string $message The error message.
 * @return void
 */
function respondWithError(string $message): void
{
    echo json_encode(['error' => $message]);
    exit;
}

/**
 * Resolve the station ID and name from the input parameter.
 *
 * @param string $stationParam The station parameter (name or ID).
 * @param array $stationNames Mapping of station IDs to names.
 * @return array [stationId, stationName]
 */
function resolveStation(string $stationParam, array $stationNames): array
{
    if (is_numeric($stationParam)) {
        // Station ID is provided
        $stationId = $stationParam;
        $stationName = $stationNames[$stationId] ?? null;
    } else {
        // Station name is provided
        $stationName = $stationParam;
        $stationId = array_search($stationName, $stationNames, true) ?: null;
    }

    return [$stationId, $stationName];
}

/**
 * Find the latest data file for a given station ID.
 *
 * @param string $directory The directory to search for files.
 * @param string $stationId The station ID.
 * @return string|null The path to the latest file or null if not found.
 */
function findLatestFile(string $directory, string $stationId): ?string
{
    $files = glob("$directory/DES_{$stationId}*.rep");
    if (empty($files)) {
        return null;
    }

    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    return $files[0];
}

/**
 * Extract and format the timestamp from a file name.
 *
 * @param string $fileName The file name.
 * @param string $stationId The station ID.
 * @return string|null The formatted timestamp or null if not found.
 */
function extractTimestampFromFileName(string $fileName, string $stationId): ?string
{
    if (preg_match('/DES_' . $stationId . '(\d+).rep/', basename($fileName), $matches)) {
        $timestampRaw = $matches[1]; // e.g., "20241225104010"
        $timestamp = DateTime::createFromFormat('YmdHis', $timestampRaw);
        return $timestamp ? $timestamp->format('d M Y, H:i:s') : null;
    }

    return null;
}

/**
 * Read and process a data file into an array of metrics.
 *
 * @param string $filePath The path to the file.
 * @param array $metrics Mapping of metrics to units.
 * @return array|null The processed data or null if the file is empty.
 */
function readAndProcessFile(string $filePath, array $metrics): ?array
{
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return null;
    }

    return array_map(function ($line) use ($metrics) {
        $values = str_getcsv($line); // Parse the line
        $data = [];
        $index = 0;

        foreach ($metrics as $key => $unit) {
            $data[$key] = $values[$index] ?? null;
            $index++;
        }

        return $data;
    }, $lines);
}
