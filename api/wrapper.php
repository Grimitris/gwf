<?php
/*
 * Initialize classes, run accordingly
 */
include 'class.decode.php';
include 'class.mbusparser.php';
include 'class.api.php';

class wrapper{
    
    private $api;
    
    public function __construct() {

         $this->api = new api();
    }
    
    //handle calls requested
    public function run($call){
      
       switch($call){
           case 'decodedJsonData':
               echo json_encode($this->api->getDecodedData());
               die();
           default: header('Location: http://kaagar.com/gwf/templates/index.html'); //show UI if something is wrong
           
       }
        
        
    }
    

}