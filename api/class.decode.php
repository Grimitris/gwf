<?php
/*
 * AES 128 decoder class. inputs key and data, generates Initialization Vector and returns decoded result
 */
class decoder{
    
    private $key;
    private $data;
    private $iv;
    private $method;
    private $hexData;
    
    public function __construct() {
        
        $this->method   = $this->getMethod(0); //store Cipher method from library
    }
    
    public function getDecodedData($inputKey,$inputData){
                
        if($inputKey!=null || $inputData!=null){
            
            $this->key      = $inputKey;
            $this->data     = $inputData;
            $this->hexData  = str_split($this->data, 2);
            $this->iv       = $this->generateInitVector(); //M Field + A Field + 8 bytes Acces No
            $packetLengthPlusVerification = $this->hexData[0]+2;
            $datat = implode('',array_slice($this->hexData, -$packetLengthPlusVerification, $packetLengthPlusVerification, true));
            $header = implode('',array_slice($this->hexData, 0, -$packetLengthPlusVerification, true));
            $res =  bin2hex(openssl_decrypt(hex2bin($datat), $this->method, hex2bin($this->key), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, hex2bin($this->iv)));
            
            //var_dump(array('header'=>$header,'data'=>$res));
            return array('header'=>$header,'data'=>$res);
            
        }
        
    }
    public function decode(){
        
            $this->key      ='11627177330679ABBBDD341BEFF243F7';
            $this->data     = '4644e61e11102223100e7212112324e61e3c03000030058431bf8dc5630561faddd0644ef029144116e9891f80d4760b4e32f8ea3f12f8e20b8ff212db30b50c0587f505e5d10c';
            $this->hexData  = str_split($this->data, 2);
            $this->iv       = $this->generateInitVector(); //M Field + A Field + 8 bytes Acces No
            
            //get the last 46 bytes of data (plus two which are the verification bytes)
            echo 'This is a test scenario. Run with inputs for return real data<br />';
            $packetLengthPlusVerification = $this->hexData[0]+2;
            $datat  = implode('',array_slice($this->hexData, -$packetLengthPlusVerification, $packetLengthPlusVerification, true));
            
            
            echo 'data: '.$this->data.'<br />';
            echo 'No header data: '.$datat.'<br />';
            echo 'Key: '.$this->key.'<br />';
            //echo 'Method: '.$this->method.'<br />';
            echo 'Method Cipher IV length: '.openssl_cipher_iv_length($this->method).'<br />';
            echo 'IV: '.$this->iv.'<br />';
            echo 'decoding done: <br/>';

            echo $this->method.':<br />';

            $output = openssl_decrypt(hex2bin($datat), $this->method, hex2bin($this->key), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, hex2bin($this->iv));
            var_dump(bin2hex($output));
            
        
    }
    
    private function getMethod($num){
        
        $ciphers = openssl_get_cipher_methods();
        return $ciphers[$num];
    
    }
    
    private function generateInitVector(){

        $Mfield = $this->hexData[2].$this->hexData[3]; //M-Field (bits 2-4)
        $Afield = $this->hexData[4].$this->hexData[5].$this->hexData[6].$this->hexData[7].$this->hexData[8].$this->hexData[9];  //A-Field (bits 4-8)
        $accesnum = str_repeat($this->hexData[12],8); //get access number with CRC missing (bit 12). 8 bits of that
        
        return $Mfield.$Afield.$accesnum;
        
    }
    
}
