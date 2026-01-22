<?php
function fetchAndCachePortugalBackData() {
    // Clear all user cache entries in APCu
    global $options; 
    //save the stream back data as json structure in apcu
    $data = file_get_contents('https://slave.host-palace.net/portual_cdn_api', false, $options);
    $cf_data = file_get_contents('https://slave.host-palace.net/get_cf_dns_list', false, $options);
    
    if ($data !== false) {
        $portugal_back_data = json_decode($data, true);
        foreach ($portugal_back_data as $d) {
            apcu_delete(strtolower($d['domain']));
            apcu_store(strtolower($d['domain']) , json_encode($d));
        }
    }
    if ($cf_data !== false) {
        $cf_dns_list = json_decode($cf_data, true);
        $cf_dns_data_grouped = [];
        foreach ($cf_dns_list as $d) {
            if(count($d) > 2){
                $cf_dns_data_grouped[$d['backnode']][] = $d;
            }else if(!key_exists($d['backnode'], $cf_dns_data_grouped)){
                $cf_dns_data_grouped[$d['backnode']] = [];
            }
        }
        foreach ($cf_dns_data_grouped as $backnode => $group) {
            apcu_delete($backnode);
            apcu_store($backnode , json_encode($group));
        }
    }

}

//get domain data
if( isset( $_REQUEST['domain'] ) ){
  $domain = strtolower( $_REQUEST['domain'] );
  echo apcu_fetch($domain);
}else{
  try {
    fetchAndCachePortugalBackData();
  } catch (Exception $e) {
    echo $e->getMessage();
  }
  echo "domain reset, memory cleared";
}

?>
