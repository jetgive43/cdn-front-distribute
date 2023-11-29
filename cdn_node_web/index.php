<?php


$hash = ip2long(   $_SERVER['REMOTE_ADDR'] );
$block_value  = intval(file_get_contents("http://blocking.middlewaresv.xyz/index.php?ip=".$hash,true));
// use apc_ memory function to save the ip_range result
$port = $_SERVER['SERVER_PORT'] ? ':'.$_SERVER['SERVER_PORT'] : '';
if( $block_value == 2 )
        $url = "http://".$hash.".".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];
else    $url = "http://origin.".$_SERVER["SERVER_NAME"].$port.$_SERVER['REQUEST_URI'];

header("HTTP/1.1 301 Moved Permanently");
header("Location: $url");

?>
