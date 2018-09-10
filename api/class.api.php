<?php

class api{
    
    private $meters;
    
    public function __construct() {
        
        $this->meters = array();
        
    }
    
    /*
     * CURL for the data. Store to global array for usage on decoding and parsing.
     */
    public function getdata($return){
        
        //get the device addresses
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

        //get all the keys for the devices
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
        
        curl_close($ch);
        
        //get telegrams and join with the previous data
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
        }
        
        curl_close($ch); //kill curl session
        
        if(!$return) echo json_encode($this->meters); //for debugging
        
        
    }
    
    /*
     * Pass the received data by the decoder on decoder.decodeData
     */
    public function getDecodedData($debug){
        $this->decoder = new decoder();
        
        $this->getdata(true);
        foreach($this->meters as $k=>$v){
            $this->parser = new mbusParser();
            if(substr($v['telegram']['encoded'],46,4)!='2f2f'){ //check if telegram is already decoded
                $decodedTelegram = $this->decoder->decodeData($v['telegram']['key'],$v['telegram']['encoded']);
            }else{
                $decodedTelegram['data'] = substr($v['telegram']['encoded'],46);
            }
            
            if($decodedTelegram['data'] && $decodedTelegram['data']!=null){
                
                //Send decoded data over to the data parser
                $parsedData = $this->parser->parseMbus($decodedTelegram['data'],$decodedTelegram['header'],($debug)?true:false);
                $this->meters[$k]['telegram']['parsed']= $parsedData;
                
                //destroy unwanted data after use
                unset($this->meters[$k]['telegram']['key']);
                unset($this->meters[$k]['telegram']['encoded']);
                
            }
            
            
        }
        
        //return all data
        return $this->meters;
        
    }
    
    /*
     * Store the parsed data to the global meters variable
     */
    public function getParsedData(){
        
        $this->parser = new mbusParser();
        foreach($this->meters as $k=>$v){
            
            $parsedData = $this->parser($v['telegram']['decoded']);
            $this->meters[$k]['telegram']['parsed']= $parsedData;
            
        }
        
    }
    
    
    
}