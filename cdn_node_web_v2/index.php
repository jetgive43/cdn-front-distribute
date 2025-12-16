<?php
$ipv4 = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
$is_ipv4 = ( $ipv4 == $_SERVER['REMOTE_ADDR'] );
$ip_hash = ip2long($_SERVER['REMOTE_ADDR']);

try {
    // Server_name  *.xxx.com
    // HTTP_HOST  user_requested.xxx.com
    $domain = apcu_fetch( strtolower( $_SERVER["SERVER_NAME"] ) );

    if($domain !== false) {
        $domain = json_decode($domain, true);
    }
    if( $domain["disable"] == 1 || !$is_ipv4 ){
        $url = "http://" . $domain["ip"] . $_SERVER['REQUEST_URI'];
        header('Content-Type: text/html; charset=UTF-8');
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


function binarySearchNormal(array $arr, $target) {
    $low = 0;
    $high = count($arr) - 1;

    while ($low <= $high) {
        $mid = floor(($low + $high) / 2);

        if ($arr[$mid] == $target) {
            return $mid; // Target found, return index
        } elseif ($arr[$mid] < $target) {
            $low = $mid + 1; // Search in the right half
        } else {
            $high = $mid - 1; // Search in the left half
        }
    }

    return false; // Target not found
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

$subDNS = explode(".", $_SERVER["HTTP_HOST"], 2)[0];
$masterDNS = explode(".", $domain["stream_dns_name"], 2)[1];
$blackhole_domains = json_decode(apcu_fetch('blackhole_domains'), true);

$use_cf_cdn = $domain && $domain["cf_cdn_list"] != null && strlen($domain["cf_cdn_list"]) > 1 && strpos($domain["cf_cdn_list"], strtoupper($country_code)) !== false;
if($use_cf_cdn){
    $cf_dns_list = json_decode(apcu_fetch(strtolower($domain["ip"])), true);
    if($cf_dns_list === null || count($cf_dns_list) == 0){
        $use_cf_cdn = false;
    } else {
        $random_dns = $cf_dns_list[array_rand($cf_dns_list)];
    }
}




if ($block_value == 1) { 
    $url = "http://block-" . ip2long($domain["ip"]) . "." . $blackhole_domains[array_rand($blackhole_domains)]["name"]. $_SERVER['REQUEST_URI'];
} else if ($block_value == 0 && $dns_country_enabled == 1) { // not blocked ip and and backnode is blocked from client's country
    if($use_cf_cdn) {
        $url = "http://" . $random_dns["record"].".".$random_dns["domain_name"] . $_SERVER['REQUEST_URI'];        
    } else {
        $url = "http://".$country_code."-" . $subDNS . "." . $masterDNS . $_SERVER['REQUEST_URI']; //http://xx-jwalt-1.treelive.ink/index2.php
    }
} else {
    $url = "http://" . $domain["ip"] . $_SERVER['REQUEST_URI'];
}

// Redirect to the appropriate URL
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *'); 
if( isset( $_SERVER['SERVER_ADDR'] ) )
  header('special_header: '.$_SERVER['SERVER_ADDR']); 

$url = utf8_decode($url);
header("Location: $url", true, 302);

?>
