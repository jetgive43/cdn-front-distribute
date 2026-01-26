<?php
$ipv4 = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
$is_ipv4 = ( $ipv4 == $_SERVER['REMOTE_ADDR'] );
$country_code = $_SERVER['GEOIP_COUNTRY_CODE'] ?? "xx";
try {
    $domain = apcu_fetch( strtolower( $_SERVER["SERVER_NAME"] ) ) ?? '';
    if($domain !== '') {
        $domain = json_decode($domain, true);
    }
    if( !$is_ipv4 ){
        $url = "http://" . $domain["ip"] . $_SERVER['REQUEST_URI'];
        header('Content-Type: text/html; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header("Location: $url", true, 302);
        return;
    }       
} catch (Exception $e) {
}

$subDNS = explode(".", $_SERVER["HTTP_HOST"], 2)[0];
$masterDNS = explode(".", $domain["stream_dns_name"], 2)[1];



$use_stream_cdn = array_key_exists("stream_cdn_regions", $domain) && $domain["stream_cdn_regions"] != null && strlen($domain["stream_cdn_regions"]) > 1 && strpos($domain["stream_cdn_regions"], strtoupper($country_code)) !== false;
$use_cf_cdn = array_key_exists("cf_cdn_regions", $domain) && $domain["cf_cdn_regions"] != null && strlen($domain["cf_cdn_regions"]) > 1 && strpos($domain["cf_cdn_regions"], strtoupper($country_code)) !== false;

if($use_cf_cdn){
    $cf_dns_list = json_decode(apcu_fetch(strtolower($domain["ip"])), true);
    if($cf_dns_list === null || count($cf_dns_list) == 0){
        $use_cf_cdn = false;
    } else {
        $random_dns = $cf_dns_list[array_rand($cf_dns_list)];
    }
}

if($use_cf_cdn) {
    $url = "http://" . $random_dns["record"] . "." . $random_dns["domain_name"] . ":" . $random_dns["port"] . $_SERVER['REQUEST_URI'];        
} else if($use_stream_cdn) {
    $url = "http://".$country_code."-" . $subDNS . "." . $masterDNS . ":" . $domain["port"] . $_SERVER['REQUEST_URI']; 
} else{
    $url = "http://" . $domain["ip"] . ":" . $domain["port"] . $_SERVER['REQUEST_URI'];
}

// Redirect to the appropriate URL
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *'); 
if( isset( $_SERVER['SERVER_ADDR'] ) )
  header('special_header: '.$_SERVER['SERVER_ADDR']); 

$url = utf8_decode($url);
header("Location: $url", true, 302);

?>
