<?php
/*
 * Decide whethere it's an API call or return interface 
 */
include 'api/wrapper.php';
$wrapper = new wrapper();

if(isset($_REQUEST)){
   
    if($_REQUEST['call']){
        $wrapper->run($_REQUEST['call']);
    }else{
        echo'Something went wrong.';
    }
    
}else{
    
    echo 'run template here';
    
}