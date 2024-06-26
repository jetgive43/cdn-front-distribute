<?php
$options = stream_context_create(array('http'=>
    array(
    'timeout' => 1 // 1 seconds
    )
));
$hash = ip2long(   $_SERVER['REMOTE_ADDR'] );
$block_value = 0;

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
        $url = "https://front-".$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI'];
else if( $block_value == 1 )  // block
        $url = "https://block-".$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI'];
else    $url = "http://origi-".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];

// header("HTTP/1.1 301 Moved Permanently");
header("Location: $url",true, 302);

?>
