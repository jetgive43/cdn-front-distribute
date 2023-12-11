<?php
$options = stream_context_create(array('http'=>
    array(
    'timeout' => 1 // 1 seconds
    )
));
$hash = ip2long(   $_SERVER['REMOTE_ADDR'] );
$block_value = 0;

try {
    $res  = intval(file_get_contents("http://blocking.middlewaresv.xyz/api/blockedip/check_ext?ip=".$hash , false , $options )) ;
    $R=explode( ":" , $res );
    $block_state =  $res[0];
    $country_code =  $res[1];
    $token =  $res[2];
    if( false === $block_value ){
        $block_state = 0;
        $country_code = "DE";
        $token = 11111;
    }
}
catch(Exception $e) {
    $block_state = 0;
    $country_code = "DE";
    $token = 11111;
}

$port = $_SERVER['SERVER_PORT'] ? ':'.$_SERVER['SERVER_PORT'] : '';

if( $block_state == 2 ){ // whitelist
        $url = "http://".$country_code."-".$token.".".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];
}
else if( $block_state == 1 )  // block
        $url = "http://block.".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];
else    $url = "http://origin.".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];

header("HTTP/1.1 301 Moved Permanently");
header("Location: $url");

?>
