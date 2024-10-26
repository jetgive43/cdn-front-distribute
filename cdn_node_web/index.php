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
                'isBlocked' => $block['isBlocked'],
                'countryCode' => $block['countryCode']
            ];
        }

        // Sort by start IP
        usort($sorted_data, function($a, $b) {
            return $a['start'] - $b['start'];
        });

        apcu_store('block_data', $sorted_data);
    }
}


// Function to fetch and cache block data
function fetchAndCachePortugalBackData() {
    global $options; // Access the global options variable
    $data = file_get_contents("http://slave.host-palace.net/portual_cdn_api", false, $options);
    if ($data !== false) {
        $portugal_back_data = json_decode($data, true);
        foreach ($portugal_back_data as $d) {
            apcu_store(strtolower($d['domain']) , intval($d['disable']) );
        }
    }
    apcu_store('portugal_back_read_flag', 1);
}

// Check if block data is cached
try {
    $block_data = apcu_fetch('block_data');
    $portugal_back_read_flag = apcu_fetch('portugal_back_read_flag');
} catch (Exception $e) {
    fetchAndCacheBlockData();
    fetchAndCachePortugalBackData();
}

try {
    if ($block_data === false) {
     // If not cached, fetch from the API and cache it
        fetchAndCacheBlockData();
        $block_data = apcu_fetch('block_data');
    }
    if ($portugal_back_read_flag === false) {
        fetchAndCachePortugalBackData();
    }
} catch (Exception $e) {
    // Handle the exception (e.g., log it or set a default value for $block_data)
    error_log("Error fetching or caching block data: " . $e->getMessage());
    $block_data = [];
}

try {
    // Server_name  *.xxx.com
    // HTTP_HOST  user_ruquested.xxx.com

    $domain_disable = apcu_fetch( strtolower( $_SERVER["SERVER_NAME"] ) );
    if( $domain_disable == 1 ){
        $url = "http://origi-" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
        header('Access-Control-Allow-Origin: *');
        header("Location: $url", true, 302);
        return;
    }
    $wildcard_flag = ( substr($_SERVER["SERVER_NAME"], 0, 1) == '*' );

} catch (Exception $e) {

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
            return [
                'blockStatus' => $data[$mid]['isBlocked'],
                'countryCode' => ($data[$mid]['countryCode'] === null || $data[$mid]['countryCode'] === "") ? "xx" : $data[$mid]['countryCode']
            ];
        }
    }

    return [
        'blockStatus' => 2, // Not found, meaning not blocked
        'countryCode' => "xx" // No country code for unmatched IP
    ];
}



// Check the block status using binary search
try {
    if ($block_data) {
        $searchResult = binarySearch($block_data, $hash);
        $block_value = $searchResult['blockStatus'];
        $country_code = $searchResult['countryCode'];
    }
} catch (Exception $e) {
    // Handle the exception here
    error_log("Error in binary search: " . $e->getMessage());
    $block_value = 0; // Set a default value in case of error
    $country_code = "xx";
}




if ($block_value == 1) { // block
    $url = "http://block-" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
} else if ($block_value == 0) { // not blocked
    if( $wildcard_flag ){
        // Country specific redirection based on Wildcard
        $url = "http://front-".$country_code."-". $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
    } else {
        $url = "http://front-" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
    }
} else {
    $url = "http://origi-" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
}




// Redirect to the appropriate URL
// echo $block_value
header('Access-Control-Allow-Origin: *'); 
if( isset( $_SERVER['SERVER_ADDR'] ) )
header('special_header: '.$_SERVER['SERVER_ADDR']); 
header("Location: $url", true, 302);
?>
