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
    
    public function getDecodedData($debug){
        $this->decoder = new decoder();
        
        $this->getdata(true);
        foreach($this->meters as $k=>$v){
            $this->parser = new mbusParser();
            if(substr($v['telegram']['encoded'],46,4)!='2f2f'){ //check if telegram is already decoded
                $decodedTelegram = $this->decoder->getDecodedData($v['telegram']['key'],$v['telegram']['encoded']);
            }else{
                $decodedTelegram['data'] = substr($v['telegram']['encoded'],46);
            }
            
            if($decodedTelegram['data'] && $decodedTelegram['data']!=null){
                $this->meters[$k]['telegram']['decoded'] = $decodedTelegram['data'];
                $this->meters[$k]['telegram']['header'] = $decodedTelegram['header'];
                if($debug){
                     //debugging stuff. will remove
                    echo 'Key: '.$v['telegram']['key'].'<br />';
                    echo 'Encoded: '.$v['telegram']['encoded'].'<br />';
                    echo 'Header: '.implode(' ',str_split($decodedTelegram['header'], 2)).'<br />';
                    echo 'Decoded: '.implode(' ',str_split($decodedTelegram['data'], 2)).'<br />...<br />';
                    
                }
               
                $parsedData = $this->parser->parseMbus($decodedTelegram['data'],$decodedTelegram['header'],($debug)?true:false);
                $this->meters[$k]['telegram']['parsed']= $parsedData;
                
            }
            
            
        }
        
        return $this->meters;
        
    }
    
    public function getParsedData(){
        
        $this->parser = new mbusParser();
        foreach($this->meters as $k=>$v){
            
            $parsedData = $this->parser($v['telegram']['decoded']);
            $this->meters[$k]['telegram']['parsed']= $parsedData;
            
        }
        
    }
    
    
    
}