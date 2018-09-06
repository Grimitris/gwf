<?php
/*
 * Initialize classes, run accordingly
 */
include 'class.decode.php';
include 'class.mbusparser.php';
include 'class.api.php';

class wrapper{
    
    private $decoder;
    private $parser;
    private $api;
    
    public function __construct() {
         $this->decoder = new decoder();
         $this->parser = new mbusParser();
         $this->api = new api();
    }
    
    public function run($call){
      
       switch($call){
           case 'decode':
               $this->decoder->decode();
               die();
           case 'parse':
               $this->parser->parseMbus();
               die();
           case 'getdata':
               $this->api->getdata();
               die();
           case 'getdecodeddata':
               $this->api->getDecodedData();
               die();
           default: die('Dorothy is lost.');
           
       }
        
        
    }
    
}