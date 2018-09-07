<?php

class mbusParser{
    
    private $packet;
    private $difs;
    private $vifs;
    public function __construct() {
        
       $this->packet = '2f2f027531052c0d0269e113115502f4a50200426cbf1302fd744313046d372eb1140f0102d82f2f2f2f2f2f2f2f2f2f';
       //$this->packet = '2f2f0075fd052c0905347016115505ec100600426cbf1302fd74dc12046d372eb1140f0140d82f2f2f2f2f2f2f2f2f2f';
       
       $this->difs = $this->getDif();
       $this->vifs = $this->getVif();
        
    }
    
    public function parseMbus($packet,$header,$debug){
       
       if($packet) $this->packet = $packet;
       if($debug){
            //echo $this->packet;
            echo '<br />';
            $split = str_split($this->packet, 2);

            echo 'DIF: '.$split[2].' - Name: '.$this->difs[$split[2]]['name'].' Length : '.$this->difs[$split[2]]['length'].'<br />'; 
            echo 'VIF: '.$split[3].' - '.$this->vifs[$split[3]].' <br />';
            echo 'VALUE: '.$split[4].' '.$split[5].' '.$split[6].' '.$split[7].' <br />';
            echo 'Decode attampt: '.$split[7].''.$split[6].''.$split[5].''.$split[4].' <br />';

            echo '<br /><br />';

            echo 'DIF: '.$split[8].' - Name: '.$this->difs[$split[8]]['name'].' Length : '.$this->difs[$split[8]]['length'].'<br />'; 
            echo 'VIF: '.$split[9].' - '.$this->vifs[$split[9]].' <br />';
            echo 'VALUE: '.$split[10].' '.$split[11].' '.$split[12].' '.$split[13].' <br />';

            echo '<br /><br />';

            echo 'DIF: '.$split[14].' - Name: '.$this->difs[$split[14]]['name'].' Length : '.$this->difs[$split[14]]['length'].'<br />'; 
            echo 'VIF: '.$split[15].' - '.(($this->vifs[$split[15]])?$this->vifs[$split[15]]:'VIF-Extension table').' <br />';
            echo 'VIFE: '.$split[16].' - <br />';
            echo 'VALUE: '.$split[17].' '.$split[18].' '.$split[19].' '.$split[20].'<br />';

            echo '<br /><br />';

            echo 'DIF: '.$split[21].' - Name: '.$this->difs[$split[21]]['name'].' Length : '.$this->difs[$split[21]]['length'].'<br />'; 
            echo 'VIF: '.$split[22].' - '.(($this->vifs[$split[15]])?$this->vifs[$split[15]]:'VIF-Extension table').' <br />';
            echo 'VIFE: '.$split[23].' - <br />';
            echo 'VALUE: '.$split[24].' '.$split[25].' '.$split[26].' '.$split[27].'<br />';


            /*
            var_dump($split[2].' '.$split[3].' '.$split[4].' '.$split[5].' '.$split[6].' '.$split[7]);
            echo '<br />DR2: <br />';
            var_dump($split[8].' '.$split[9].' '.$split[10].' '.$split[11].' '.$split[12].' '.$split[13]);
            echo '<br />DR3: <br />';
            var_dump($split[14].' '.$split[15].' '.$split[16].' '.$split[17].' '.$split[18]);
            echo'<br />';
            echo'<br />';
            foreach($split as $k=>$v){

                echo $v.' ';//.' - '.hex2bin($v);

            }
             * 
             */
            //var_dump(str_split($this->packet, 2));

            echo '<Br />----------------------------<br />';

       }   

            
       return 'Temp decoded data: Under Construction.';
        
        
    }
    
    private function getDif(){
        
        /*
         *  Len     Code Meaning                Code Meaning
            --
            0       0000 No data                1000 Selection for Readout
            8       0001 8 Bit Integer/Binary   1001 2 digit BCD
            16      0010 16 Bit Integer/Binary  1010 4 digit BCD
            24      0011 24 Bit Integer/Binary  1011 6 digit BCD
            32      0100 32 Bit Integer/Binary  1100 8 digit BCD
            32/N    0101 32 Bit Real            1101 variable length
            48      0110 48 Bit Integer/Binary  1110 12 digit BCD
            64      0111 64 Bit Integer/Binary  1111 Special Functions
         
         */
        
        return array(
             '00'=>array('length'=>0,'name'=>'No data')
            ,'01'=>array('length'=>8,'name'=>'8 Bit Integer/Binary')
            ,'02'=>array('length'=>16,'name'=>'16 Bit Integer/Binary')
            ,'03'=>array('length'=>24,'name'=>'24 Bit Integer/Binary')
            ,'04'=>array('length'=>32,'name'=>'32 Bit Integer/Binary')
            ,'05'=>array('length'=>'32/N','name'=>'32 Bit Real')
            ,'06'=>array('length'=>48,'name'=>'48 Bit Integer/Binary')
            ,'07'=>array('length'=>64,'name'=>'64 Bit Integer/Binary')
            ,'08'=>array('length'=>0,'name'=>'Selection for Readout')
            ,'09'=>array('length'=>8,'name'=>'2 digit BCD')
            ,'0A'=>array('length'=>16,'name'=>'4 digit BCD')
            ,'0B'=>array('length'=>24,'name'=>'6 digit BCD')
            ,'0C'=>array('length'=>32,'name'=>'8 digit BCD')
            ,'0D'=>array('length'=>'32/N','name'=>'variable length')
            ,'0E'=>array('length'=>48,'name'=>'12 digit BCD')
            ,'0F'=>array('length'=>64,'name'=>'Special Functions')
        );
        
        
    }
    private function getVif(){
        
        return array(
            '00'=>'Energy mWh'
            ,'01'=>'Energy 10<sup>-2</sup> Wh'
            ,'02'=>'Energy 10<sup>-1</sup> Wh'
            ,'03'=>'Energy Wh'
            ,'04'=>'Energy 10<sup>1</sup> Wh'
            ,'05'=>'Energy 10<sup>2</sup> Wh'
            ,'06'=>'Energy kWh'
            ,'07'=>'Energy 10<sup>4</sup> Wh' 
            ,'08'=>'Energy J'
            ,'09'=>'Energy 10<sup>1</sup> J'
            ,'0A'=>'Energy 10<sup>2</sup> J'
            ,'0B'=>'Energy kJ'
            ,'0C'=>'Energy 10<sup>4</sup> J'
            ,'0D'=>'Energy 10<sup>5</sup> J'
            ,'0E'=>'Energy MJ'
            ,'0F'=>'Energy 10<sup>7</sup> J'
            ,'10'=>'Volume cm<sup>3</sup>'
            ,'11'=>'Volume 10<sup>-5</sup> m<sup>3</sup>'
            ,'12'=>'Volume 10<sup>-4</sup> m<sup>3</sup>'
            ,'13'=>'Volume l'
            ,'14'=>'Volume 10<sup>-2</sup> m<sup>3</sup>'
            ,'15'=>'Volume 10<sup>-1</sup> m<sup>3</sup>'
            ,'16'=>'Volume m<sup>3</sup>'
            ,'17'=>'Volume 10<sup>1</sup> m<sup>3</sup>'
            ,'18'=>'Mass g'
            ,'19'=>'Mass 10<sup>-2</sup> kg'
            ,'1A'=>'Mass 10<sup>-1</sup> kg'
            ,'1B'=>'Mass kg'
            ,'1C'=>'Mass 10<sup>1</sup> kg'
            ,'1D'=>'Mass 10<sup>2</sup> kg'
            ,'1E'=>'Mass t'
            ,'1F'=>'Mass 10<sup>4</sup> kg'
            ,'20'=>'On time seconds'
            ,'21'=>'On time minutes'
            ,'22'=>'On time hours'
            ,'23'=>'On time days'
            ,'24'=>'Operating time seconds'
            ,'25'=>'Operating time minutes'
            ,'26'=>'Operating time hours'
            ,'27'=>'Operating time days'
            ,'28'=>'Power mW'
            ,'29'=>'Power 10<sup>-2</sup> W'
            ,'2A'=>'Power 10<sup>-1</sup> W'
            ,'2B'=>'Power W'
            ,'2C'=>'Power 10<sup>1</sup> W'
            ,'2D'=>'Power 10<sup>2</sup> W'
            ,'2E'=>'Power kW'
            ,'2F'=>'Power 10<sup>4</sup> W'
            ,'30'=>'Power J/h'
            ,'31'=>'Power 10<sup>1</sup> J/h'
            ,'32'=>'Power 10<sup>2</sup> J/h'
            ,'33'=>'Power kJ/h'
            ,'34'=>'Power 10<sup>4</sup> J/h'
            ,'35'=>'Power 10<sup>5</sup> J/h'
            ,'36'=>'Power MJ/h'
            ,'37'=>'Power 10<sup>7</sup> J/h'
            ,'38'=>'Volume flow cm<sup>3</sup>/h'
            ,'39'=>'Volume flow 10<sup>-5</sup> m<sup>3</sup>/h'
            ,'3A'=>'Volume flow 10<sup>-4</sup> m<sup>3</sup>/h'
            ,'3B'=>'Volume flow l/h'
            ,'3C'=>'Volume flow 10<sup>-2</sup> m<sup>3</sup>/h'
            ,'3D'=>'Volume flow 10<sup>-1</sup> m<sup>3</sup>/h'
            ,'3E'=>'Volume flow m<sup>3</sup>/h'
            ,'3F'=>'Volume flow 10<sup>1</sup> m<sup>3</sup>/h'
            ,'40'=>'Volume flow ext. 10<sup>-7</sup> m<sup>3</sup>/min'
            ,'41'=>'Volume flow ext. cm<sup>3</sup>/min'
            ,'42'=>'Volume flow ext. 10<sup>-5</sup> m<sup>3</sup>/min'
            ,'43'=>'Volume flow ext. 10<sup>-4</sup> m<sup>3</sup>/min'
            ,'44'=>'Volume flow ext. l/min'
            ,'45'=>'Volume flow ext. 10<sup>-2</sup> m<sup>3</sup>/min'
            ,'46'=>'Volume flow ext. 10<sup>-1</sup> m<sup>3</sup>/min'
            ,'47'=>'Volume flow ext. m<sup>3</sup>/min'
            ,'48'=>'Volume flow ext. mm<sup>3</sup>/s'
            ,'49'=>'Volume flow ext. 10<sup>-8</sup> m<sup>3</sup>/s'
            ,'4A'=>'Volume flow ext. 10<sup>-7</sup> m<sup>3</sup>/s'
            ,'4B'=>'Volume flow ext. cm<sup>3</sup>/s'
            ,'4C'=>'Volume flow ext. 10<sup>-5</sup> m<sup>3</sup>/s'
            ,'4D'=>'Volume flow ext. 10<sup>-4</sup> m<sup>3</sup>/s'
            ,'4E'=>'Volume flow ext. l/s'
            ,'4F'=>'Volume flow ext. 10<sup>-2</sup> m<sup>3</sup>/s'
            ,'50'=>'Mass g/h'
            ,'51'=>'Mass 10<sup>-2</sup> kg/h'
            ,'52'=>'Mass 10<sup>-1</sup> kg/h'
            ,'53'=>'Mass kg/h'
            ,'54'=>'Mass 10<sup>1</sup> kg/h'
            ,'55'=>'Mass 10<sup>2</sup> kg/h'
            ,'56'=>'Mass t/h'
            ,'57'=>'Mass 10<sup>4</sup> kg/h'
            ,'58'=>'Flow temperature 10<sup>-3</sup> °C'
            ,'59'=>'Flow temperature 10<sup>-2</sup> °C'
            ,'5A'=>'Flow temperature 10<sup>-1</sup> °C'
            ,'5B'=>'Flow temperature °C'
            ,'5C'=>'Return temperature 10<sup>-3</sup> °C'
            ,'5D'=>'Return temperature 10<sup>-2</sup> °C'
            ,'5E'=>'Return temperature 10<sup>-1</sup> °C'
            ,'5F'=>'Return temperature °C'
            ,'60'=>'Temperature difference mK'
            ,'61'=>'Temperature difference 10<sup>-2</sup> K'
            ,'62'=>'Temperature difference 10<sup>-1</sup> K'
            ,'63'=>'Temperature difference K'
            ,'64'=>'External temperature 10<sup>-3</sup> °C'
            ,'65'=>'External temperature 10<sup>-2</sup> °C'
            ,'66'=>'External temperature 10<sup>-1</sup> °C'
            ,'67'=>'External temperature °C'
            ,'68'=>'Pressure mbar'
            ,'69'=>'Pressure 10<sup>-2</sup> bar'
            ,'6A'=>'Pressure 10⁻1 bar'
            ,'6B'=>'Pressure bar'
            ,'6C'=>'Date type G'
            ,'6E'=>'Units for H.C.A.'
            ,'6F'=>'Reserved'
            ,'70'=>'Averaging duration seconds'
            ,'71'=>'Averaging duration minutes'
            ,'72'=>'Averaging duration hours'
            ,'73'=>'Averaging duration days'
            ,'74'=>'Actuality duration seconds'
            ,'75'=>'Actuality duration minutes'
            ,'76'=>'Actuality duration hours'
            ,'77'=>'Actuality duration days'
            ,'78'=>'Fabrication no'
            ,'79'=>'Enhanced identification'
            ,'80'=>'Address'
            ,'7C'=>'VIF in following string (length in first byte)'
            ,'7E'=>'Any VIF'
            ,'7F'=>'Manufacturer specific'
        );
        
    }
    
}