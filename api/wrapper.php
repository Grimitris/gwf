<?php
/*
 * Initialize classes, run accordingly
 */
include 'class.decode.php';
include 'class.mbusparser.php';
include 'class.api.php';

class wrapper{
    
    protected $decoder;
    
    public function __construct() {
         $this->decoder = new decoder();
         $this->parser = new mbusParser();
    }
    
    public function run($call){
      
       switch($call){
           case 'decode':
               $this->decoder->decode();
               die();
           case 'parse':
               $this->parser->parseMbus();
               die();
           default: return 'Dorothy is lost.';
           
       }
        
        
    }
    
}