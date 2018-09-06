<?php

class api{
    
    private $meters;
    
    public function __construct() {
        
        $this->meters = array();
        
        
    }
    
    public function getdata($return){
        
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, "https://bitbakery.ch/payload/api.php?action=getAdress"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch);
        $output = json_decode($output);
        foreach($output as $k=>$v){
            
            if(!isset($this->meters[$k])) $this->meters[$k] = array();
            $this->meters[$k]['address'] = $v;
            
            
        }
        
        curl_close($ch);

        
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, "https://bitbakery.ch/payload/api.php?action=getKeys"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch);
        $output = json_decode($output);
        foreach($output as $k=>$v){
            
            if(!isset($this->meters[$k])){
                $this->meters[$k] = array();
                $this->meters[$k]['telegram'] = array();
            } 
            $this->meters[$k]['telegram']['key']= $v;
      
        }
        
        //var_dump( $this->meters);
        curl_close($ch);
        
        
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, "https://bitbakery.ch/payload/api.php?action=getTelegrams"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch);
        $output = json_decode($output);
        foreach($output as $k=>$v){
            
            if(!isset($this->meters[$k])){
                $this->meters[$k] = array();
                $this->meters[$k]['telegram'] = array();
            }
            $this->meters[$v->serial]['telegram']['encoded']= $v->raw;
            $this->meters[$v->serial]['telegram']['timestamp']= $v->timestamp;
            
            //append data to $this->meters with primary key, the serial number of the meter
            
        }
        
        curl_close($ch);
        if(!$return) echo json_encode($this->meters);
        
        //run decoder object and get decoded data
        
        //run parser with decoded data
        
    }
    
    public function getDecodedData(){
        $this->decoder = new decoder();
        $this->getdata(true);
        foreach($this->meters as $k=>$v){
            
            $decodedTelegram = $this->decoder->getDecodedData($v['telegram']['key'],$v['telegram']['encoded']);
            $this->meters[$k]['telegram']['decoded']= $decodedTelegram;
            
        }
        
        var_dump($this->meters);
        
    }
    
    
    
}