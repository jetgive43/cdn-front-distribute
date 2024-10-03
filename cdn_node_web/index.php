<?php
$options = stream_context_create(array('http' =>
    array(
        'timeout' => 1 // 1 second
    )
));

$hash = ip2long($_SERVER['REMOTE_ADDR']);
$block_value = 0;

// Function to fetch and cache block data
function fetchAndCacheBlockData() {
    global $options; // Access the global options variable
    $data = file_get_contents("http://blocking.middlewaresv.xyz/api/blockedip/all", false, $options);
    if ($data !== false) {
        $block_data = json_decode($data, true);
        
        // Prepare a sorted array for binary search
        $sorted_data = [];
        foreach ($block_data as $block) {
            $sorted_data[] = [
                'start' => $block['startip'],
                'end' => $block['endip'],
                'isBlocked' => $block['isBlocked']
            ];
        }
        
        // Sort by start IP
        usort($sorted_data, function($a, $b) {
            return $a['start'] - $b['start'];
        });
        
        apcu_store('block_data', $sorted_data);
    }
}

// Check if block data is cached
$block_data = apcu_fetch('block_data');

try {
    if ($block_data === false) {
        // If not cached, fetch from the API and cache it
        fetchAndCacheBlockData();
        $block_data = apcu_fetch('block_data');
    }
} catch (Exception $e) {
    // Handle the exception (e.g., log it or set a default value for $block_data)
    error_log("Error fetching or caching block data: " . $e->getMessage());
    $block_data = [];
}

// Binary search function
function binarySearch($data, $ip) {
    $low = 0;
    $high = count($data) - 1;

    while ($low <= $high) {
        $mid = (int)(($low + $high) / 2);
        
        if ($ip < $data[$mid]['start']) {
            $high = $mid - 1;
        } elseif ($ip > $data[$mid]['end']) {
            $low = $mid + 1;
        } else {
            return $data[$mid]['isBlocked']; // Return the block status directly
        }
    }
    
    return 2; // Not found, meaning not blocked
}
  // Check the block status using binary search
  try {
      if ($block_data) {
          $block_value = binarySearch($block_data, $hash);
      }
  } catch (Exception $e) {
      // Handle the exception here
      error_log("Error in binary search: " . $e->getMessage());
      $block_value = 0; // Set a default value in case of error
  }

// Determine the redirect URL based on block_value
$port = $_SERVER['SERVER_PORT'] ? ':' . $_SERVER['SERVER_PORT'] : '';

if ($block_value == 1) { // block
    $url = "http://block-" . $_SERVER["SERVER_NAME"] . $port . $_SERVER['REQUEST_URI'];
} else if ($block_value == 0) { // not blocked
    $url = "http://front-" . $_SERVER["SERVER_NAME"] . $port . $_SERVER['REQUEST_URI'];
} else { // whitelisted (if applicable, adjust logic if needed)
    $url = "http://origi-" . $_SERVER["SERVER_NAME"] . $port . $_SERVER['REQUEST_URI'];
}


// Redirect to the appropriate URL
// echo $block_value
header("Location: $url", true, 302);
?>
