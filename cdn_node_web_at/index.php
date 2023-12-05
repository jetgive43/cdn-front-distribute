<?php
$options = stream_context_create(array('http'=>
    array(
    'timeout' => 1 // 1 seconds
    )
));

$hash = ip2long(   $_SERVER['REMOTE_ADDR'] );
$block_value = 0;

// xxx.com -> origin.at.xxx.com
// xxx.com -> block.at.xxx.com
// xxx.com -> 2342343.at.xxx.com

try {
    $block_value  = intval(file_get_contents("http://blocking.middlewaresv.xyz/api/blockedip/check?ip=".$hash , false , $options )) ;
    if( false === $block_value ) 
        $block_value = 0;
}
catch(Exception $e) {
    $block_value = 0;
}


$port = $_SERVER['SERVER_PORT'] ? ':'.$_SERVER['SERVER_PORT'] : '';

if( $block_value == 2 ) // whitelist
        $url = "http://".$hash.".at.".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];
else if( $block_value == 1 )  // block
        $url = "http://block.at.".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];
else    $url = "http://origin.at.".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];

header("HTTP/1.1 301 Moved Permanently");
header("Location: $url");

?>
