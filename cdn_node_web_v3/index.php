<?php
$ipv4 = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
$is_ipv4 = ( $ipv4 == $_SERVER['REMOTE_ADDR'] );

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
$use_stream_cdn = 1;
$use_cf_cdn = 0;
if($use_cf_cdn) {
    $url = "http://" . $random_dns["record"].".".$random_dns["domain_name"] . $_SERVER['REQUEST_URI'];        
} else($use_stream_cdn) {
    $url = "http://".$country_code."-" . $subDNS . "." . $masterDNS . $_SERVER['REQUEST_URI']; //http://xx-jwalt-1.treelive.ink/index2.php
} else{
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
