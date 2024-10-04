<?php
if( isset( $_REQUEST['check'] ) && isset( $_REQUEST['domain'] ) ){
  $domain = strtolower( $_REQUEST['domain'] );
  echo apcu_fetch($domain);
}
else {
   apcu_clear_cache();
   echo "memory cleared";
}
?>
