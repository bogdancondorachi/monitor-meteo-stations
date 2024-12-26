<?php
// Set the correct Content-Type header
header('Content-Type: text/html; charset=UTF-8');

// Include the helper functions
require_once 'helpers.php';

// Load configuration
$config = include 'config.php';

// Directory containing station files
$dataDir = $config['directory'];

// Fetch station IDs from file names
$stationIds = getStationIdsFromFiles($dataDir);

// Map station IDs to names or use IDs as fallback
$stations = mapStations($stationIds, $config['stations']);

// Get the selected station from the query parameter (default to the first station in the list)
$selectedStation = $_GET['station'] ?? (is_array($stations) ? $stations[0] : '');

// Fetch API response for the selected station
$data = getApiResponse($selectedStation);

// Extract data values
$dataValues = $data['data'][0] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stations Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-gray-100">
    <div class="max-w-container relative mx-auto mt-10 w-full px-4 sm:px-6 lg:px-8">
      <div class="sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Station: <?= htmlspecialchars($data['station'] ?? $data['station_id']) ?></h1>
          <p><span class="text-base font-semibold text-gray-900">Loaded file:</span> <?= htmlspecialchars($data['file']) ?></p>
          <p><span class="text-base font-semibold text-gray-900">Timestamp:</span> <?= htmlspecialchars($data['timestamp']) ?></p>
        </div>
        <div class="w-80">
          <form method="GET">
            <label for="station" class="block text-sm/6 font-medium text-gray-900">Select station</label>
            <div class="mb-6 grid grid-cols-1">
              <select id="station" name="station" onchange="this.form.submit()" class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-1.5 pl-3 pr-8 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                <?php foreach ($stations as $station): ?>  
                <option value="<?= htmlspecialchars($station) ?>" <?= $station === $selectedStation ? 'selected' : '' ?>>
                  <?= htmlspecialchars($station) ?>
                </option>
                <?php endforeach ?>
              </select>
              <svg class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" data-slot="icon">
                <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
              </svg>
            </div>
          </form>
        </div>
      </div>

      <div>
        <?php if (!empty($data['data'])): ?>
        <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-4">
          <?php foreach ($data['data'][0] as $key => $value): ?>
          <div class="overflow-hidden rounded-lg <?= $value === '/' || $value === null ? 'bg-red-50' : 'bg-white' ?> px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 capitalize">
              <?= ucfirst(str_replace('_', ' ', $key)) ?>:
            </dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
              <?= htmlspecialchars($value ?? 'null') . ($value !== null ? ' ' . htmlspecialchars($data['units'][$key] ?? '') : '') ?>
            </dd>
          </div>
          <?php endforeach ?>
        </dl>
        <?php else: ?>
          <p>No data available for this station.</p>
        <?php endif ?>
      </div>
    </div>
  </body>
</html>
