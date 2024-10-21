<?php

function fetchAndCacheBlockData() {
    global $options; // Access the global options variable
    $data = file_get_contents("http://blocking.middlewaresv.xyz/api/blockedip/all", false, $options);
    if ($data !== false) {
        $block_data = json_decode($data, true);
        
        // Prepare a sorted array for binary search
        $sorted_data = [];
        foreach ($block_data as $block) {
            $sorted_data[] = [
                'start' => $block['startip'],
                'end' => $block['endip'],
                'isBlocked' => $block['isBlocked'],
                'countryCode' => $block['countryCode']
            ];
        }
        
        // Sort by start IP
        usort($sorted_data, function($a, $b) {
            return $a['start'] - $b['start'];
        });
        
        apcu_store('block_data', $sorted_data);
    }
}


function fetchAndCachePortugalBackData() {
    global $options; // Access the global options variable
    $data = file_get_contents("http://slave.host-palace.net/portual_cdn_api", false, $options);
    if ($data !== false) {
        $portugal_back_data = json_decode($data, true);
        foreach ($portugal_back_data as $d) {
            apcu_store(strtolower($d['domain']) , intval($d['disable']) );
        }
    }
    apcu_store('portugal_back_read_flag', 1);
}




if( isset( $_REQUEST['domain'] ) ){
  $domain = strtolower( $_REQUEST['domain'] );
  echo apcu_fetch($domain);
}
else if( isset( $_REQUEST['memory'] ) ){
  try {
      fetchAndCachePortugalBackData();
  } catch (Exception $e) {
      
  }
  echo "domain reset";
}
else{
     
  // Check if block data is cached
  try {
      apcu_clear_cache();
      fetchAndCacheBlockData();
      fetchAndCachePortugalBackData();
  } catch (Exception $e) {
      
  }
  echo "memory cleared";
}
?>
