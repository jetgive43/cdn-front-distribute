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

 // Binary search function

function binarySearch($data, $ip) {

    $low = 0;

    $high = count($data) - 1;

    while ($low <= $high) {

        $mid = (int)(($low + $high) / 2);

        if ($ip < $data[$mid]['start']) {

            $high = $mid - 1;

        } elseif ($ip > $data[$mid]['end']) {

            $low = $mid + 1;

        } else {

            return [

                'blockStatus' => $data[$mid]['isBlocked'],

                'countryCode' => ($data[$mid]['countryCode'] === null || $data[$mid]['countryCode'] === "") ? "xx" : $data[$mid]['countryCode']

            ];

        }

    }

    return [

        'blockStatus' => 2, // Not found, meaning not blocked

        'countryCode' => "xx" // No country code for unmatched IP

    ];

}

function binarySearchNormal(array $arr, $target) {

    $low = 0;

    $high = count($arr) - 1;

    while ($low <= $high) {

        $mid = floor(($low + $high) / 2);

        if ($arr[$mid] == $target) {

            return $mid; // Target found, return index

        } elseif ($arr[$mid] < $target) {

            $low = $mid + 1; // Search in the right half

        } else {

            $high = $mid - 1; // Search in the left half

        }

    }

    return false; // Target not found

}

function fetchAndCachePortugalBackData() {
    global $options; 
    //save the stream back data as json structure in apcu
    $data = file_get_contents('https://dev.host-palace.net/portual_cdn_api', false, $options);
    if ($data !== false) {
        $portugal_back_data = json_decode($data, true);
        foreach ($portugal_back_data as $d) {
            apcu_delete(strtolower($d['domain']));
            apcu_store(strtolower($d['domain']) , json_encode($d));
        }
    }
    apcu_store('portugal_back_read_flag', 1);
     // save the cf-cdn dns as json structure in apcu
    $cf_data = file_get_contents('https://dev.host-palace.net/get_cf_dns_list', false, $options);
    if ($cf_data !== false) {
        $cf_dns_list = json_decode($cf_data, true);
        // save the cf dns data with the backnode as key and groupby the backnode
        $cf_dns_data_grouped = [];
        foreach ($cf_dns_list as $d) {
            $cf_dns_data_grouped[$d['backnode']][] = $d;
        }
        foreach ($cf_dns_data_grouped as $backnode => $group) {
            apcu_store($backnode , json_encode($group));
        }
    }

}

// Blocked List per Country for each Domain

function fetchBlockedDomainWithCountry() {

    global $options; // Access the global options variable

    $data = file_get_contents("http://cdneye.middlewaresv.xyz/api/eye_grid", false, $options);

    if ($data !== false) {

        $blocked_dns_with_country = json_decode($data, true);

        foreach ($blocked_dns_with_country as $d) {

            apcu_store( $d , 1 , 300 );  // 300s expire

        }

    }

}

//get blackhole ips
function getBlackholeDomains() {
    $blackhole_domains = file_get_contents("https://dev.host-palace.net/stream_cdn/get_domain_by_type?dns_type=block_dns", false, $options);
    if ($blackhole_domains !== false) {
        apcu_store('blackhole_domains', $blackhole_domains); // 10 minutes
    }
    echo "blackhole domains fetched! <br>";
}


//get domain data
if( isset( $_REQUEST['domain'] ) ){

  $domain = strtolower( $_REQUEST['domain'] );

  echo apcu_fetch($domain);

}
// reset memory
else if( isset( $_REQUEST['memory'] ) ){

  try {

      fetchBlockedDomainWithCountry();

      fetchAndCachePortugalBackData();
      
      getBlackholeDomains();

      $block_data = apcu_fetch('block_data');

      if( !$block_data ){

          fetchAndCacheBlockData();

      } 

  } catch (Exception $e) {

  }

  echo "domain reset";

}

// ip check
else if( isset( $_REQUEST['ip'] ) ){
    $hash = ip2long($_REQUEST['ip']);

    

    $searchResult = binarySearch(apcu_fetch('block_data'), $hash);

    echo "<br>IP Check on Given Domain ====<br>";

    print_r( $searchResult );

    if( isset( $_REQUEST['check_dns'] ) ){ // IP check on given Domain

        $hash = $_REQUEST['check_dns']."_".$searchResult['countryCode'];

        echo $hash." ".apcu_fetch($hash);

    }

     

}
else{

  // Check if block data is cached

  try {

      fetchBlockedDomainWithCountry();

      fetchAndCacheBlockData();

      fetchAndCachePortugalBackData();

      getBlackholeDomains();

  } catch (Exception $e) {
  }

  echo "memory cleared";
}

?>
