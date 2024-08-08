<?php
    ini_set('default_charset','UTF-8');
    
    //database configuration
    $host       = "cpanel44";
    $user       = "opcmpt";
    $pass       = "RX0]SAs9d9s:m1";
    $database   = "opcmpt_wp624";

    $connect = new mysqli($host, $user, $pass, $database);

    if (!$connect) {
        die ("connection failed: " . mysqli_connect_error());
    } else {
        $connect->set_charset('utf8');
    }
	
	$GLOBALS['config'] = $connect;


    $ENABLE_RTL_MODE = 'false';

?>