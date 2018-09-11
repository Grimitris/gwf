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
        $this->data = false;
    }
    
    /*
     * Decode the AES-128 data
     */
    public function decodeData($inputKey,$inputData){

        //Some entries don't have all the data available. Skip those.
        if($inputKey!=null || $inputData!=null){
            
            $this->key      = $inputKey;
            $this->data     = $inputData;
            $this->hexData  = str_split($this->data, 2);
            $packetLengthPlusVerification = $this->hexData[0]+2; //Get the packet length. +2 for the decoding confirmation bits.
            $this->iv       = $this->generateInitVector($packetLengthPlusVerification);

            //get the decoded part 2nd part of the package which is equal to the packet length on the first byte
            $datat = implode('',array_slice($this->hexData, -$packetLengthPlusVerification, $packetLengthPlusVerification, true));
            //get the header which is the first part of the telegram minus the packet length from the end.
            $header = implode('',array_slice($this->hexData, 0, -$packetLengthPlusVerification, true));
            //perform the decode
            $res =  bin2hex(openssl_decrypt(hex2bin($datat), $this->method, hex2bin($this->key), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, hex2bin($this->iv)));


            
            return array('header'=>$header,'data'=>$res);
            
        }
        
    }

    /*
     * Find the available cipher methods
     */
    private function getMethod($num){
        
        $ciphers = openssl_get_cipher_methods();
        return $ciphers[$num];
    
    }
    
    /*
     * Get the Initialization Vector (IV) from the telegram 
     * M Field + A Field + 8 bytes Acces No
     */
    private function generateInitVector($length){
        //if($length == 48){
            //Security Profile B IV
            $Mfield = $this->hexData[2].$this->hexData[3]; //M-Field (bits 2-4)
            $Afield = $this->hexData[11].$this->hexData[12].$this->hexData[13].$this->hexData[14].$this->hexData[17].$this->hexData[18];  //A-Field
            $accesnum = str_repeat($this->hexData[19],8); //get access number with CRC missing . 8 bits of that
//            echo '<Br />VI<br />';
//            var_dump($Mfield.$Afield.$accesnum);
            return $Mfield.$Afield.$accesnum;
        /*
        }else{
            //Security Profile A IV
            $Mfield = $this->hexData[2].$this->hexData[3]; //M-Field (bits 2-4)
            $Afield = $this->hexData[4].$this->hexData[5].$this->hexData[6].$this->hexData[7].$this->hexData[8].$this->hexData[9];  //A-Field (bits 4-8)
            $accesnum = str_repeat($this->hexData[11],8); //get access number with CRC missing . 8 bits of that
//            
            return $Mfield.$Afield.$accesnum;
            
        }*/
        
    }
    
}
