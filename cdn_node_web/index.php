<?php
 
$ipv4 = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
$is_ipv4 = ( $ipv4 == $_SERVER['REMOTE_ADDR'] );
$ip_hash = ip2long($_SERVER['REMOTE_ADDR']);


try {
    // Server_name  *.xxx.com
    // HTTP_HOST  user_ruquested.xxx.com

    $domain_disable = apcu_fetch( strtolower( $_SERVER["SERVER_NAME"] ) );
    if( $domain_disable == 1 || !$is_ipv4 ){
        $url = "http://origi-" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
        header('Access-Control-Allow-Origin: *');
        header("Location: $url", true, 302);
        return;
    }
    $wildcard_flag = ( substr($_SERVER["SERVER_NAME"], 0, 1) == '*' );

} catch (Exception $e) {
    $wildcard_flag = false;
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



$block_value = 0;
$dns_country_enabled = 0;

// Check if block data is cached
try {
    $block_data = apcu_fetch('block_data');
} catch (Exception $e) {
    $block_data = [];
}


// Check the block status using binary search

try {
    if ( $is_ipv4 && $block_data ) {
        $searchResult = binarySearch($block_data, $ip_hash);
        $block_value = $searchResult['blockStatus'];
        $country_code = $searchResult['countryCode'];

        $hash = $_SERVER["SERVER_NAME"]."_".$country_code;
        $dns_country_enabled = apcu_fetch($hash);
    } else {
        $block_value = 2;
        $country_code = "xx";
        $dns_country_enabled = 0;

    }

} catch (Exception $e) {
    // Handle the exception here
    $block_value = 2;
    $country_code = "xx";
    $dns_country_enabled = 0;
}




if ($block_value == 1) { // block
    $url = "http://block-" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
} else if ($block_value == 0 && $dns_country_enabled == 1) { // not blocked
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

header('Access-Control-Allow-Origin: *'); 
if( isset( $_SERVER['SERVER_ADDR'] ) )
header('special_header: '.$_SERVER['SERVER_ADDR']); 
header("Location: $url", true, 302);
?>
