<?php
/*
 * get the request data and decide what to do with it. Run interface if something isn't there. 
 */
include 'api/wrapper.php'; //require the wrapper
$wrapper = new wrapper(); //init wrapper

if(isset($_REQUEST)){
   
    if($_REQUEST['call']){
        $wrapper->run($_REQUEST['call']);
    }else{
        header('Location: http://kaagar.com/gwf/templates/index.html');
    }
    
}else{
    
    header('Location: http://kaagar.com/gwf/templates/index.html');
    
}