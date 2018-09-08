<?php

class mbusParser{
    
    private $packet;
    private $difs;
    private $vifs;
    private $header;
    private $hopper;
    private $curpos;
    
    public function __construct() {
        
       $this->packet = '2f2f027531052c0d0269e113115502f4a50200426cbf1302fd744313046d372eb1140f0102d82f2f2f2f2f2f2f2f2f2f';
       //$this->packet = '2f2f0075fd052c0905347016115505ec100600426cbf1302fd74dc12046d372eb1140f0140d82f2f2f2f2f2f2f2f2f2f';
       
       $this->difs = $this->getDif();
       $this->vifs = $this->getVif();
       $this->deviceType = $this->getDeviceType();
       $this->curpos = 2;

        
    }
    
    public function parseMbus($packet,$header,$debug){
       
       if($packet) $this->packet = $packet;
       if($header) $this->header = $header;
       if($debug){
            
           
            $headersplit = str_split($this->header,2);
            $this->hopper = str_split($this->packet, 2);
            
           
            $curdif = $this->calculateDif($this->hopper[$this->curpos]);
            while($curdif['status'] == 'OK'){
                
                $difHops = $curdif['hops']; //get data size
                $difType = $curdif['type']; //get data type
                
                $vifData = $this->calculateVif();
                $this->curpos++;
                
                $thisVif = $vifData['vif']; //get vif
                
                $this->parseData($difhops,$difType,$thisVif);
                
                
                $curdif = $this->calculateDif($this->hopper[$this->curpos]);
            }
            
            
            




            echo '<Br />----------------------------<br />';

       }   

            
       return 'Temp decoded data Under Construction.';
        
    }
    
    private function parseData($difhops,$difType,$thisVif){
        
        $toParse = [];
        
        for($i = 0; $i <= $difhops; $i++){
            
            $toParse.push($this->hopper[$this->curpos]);
            $this->curpos++;
            
        }
        
        if($thisVif == 'Date type G' || $thisVif == 'DateTime type F'){
            
            return $this->decodeDate($toParse);
            
        }else{
            
            switch($difType){
            
            
            
            
            }
            
        }
        
        
    }
    
    private function decodeDate($data){
        
        foreach($data as $k=>$v){

            $convdata.=$this->convertToBin( implode('',$v) );

        }
        $convdata=strrev($convdata);
        
        if(count($data) == 2){
            
            
            
            
            
        }else{
            
            
            
        }
        
        
    }

    
    private function calculateDif($dif){
        
        if($dif == '2f') return array('status'=>'end');
        
        $converted  = $this->convertToBin($dif);
                
        switch(substr($converted,-4,4)){
            case '0000':
                $hop = 0;
                $type='noData';
                break;
            case '0001':
                $hop = 1;
                $type='IntBin';
                break;
            case '0010':
                $hop = 2;
                $type='IntBin';
                break;
            case '0011':
                $hop = 3;
                $type='IntBin';
                break;
            case '0100':
                $hop = 4;
                $type='IntBin';
                break;
            case '0101':
                $hop = 4;
                $type='real';
                break;
            case '0110':
                $hop = 6;
                $type='IntBin';
                break;
            case '0111':
                $hop = 8;
                $type='IntBin';
                break;
            case '1000':
                $hop = 0;
                $type='noData';
                break;
            case '1001':
                $hop = 1;
                $type='bcd';
                break;
            case '1010':
                $hop = 2;
                $type='bcd';
                break;
            case '1011':
                $hop = 3;
                $type='bcd';
                break;
            case '1100':
                $hop = 4;
                $type='bcd';
                break;
            case '1101':
                $hop = -1;
                $type='varlength';
                break;
            case '1110':
                $hop = 6;
                $type='bcd';
                break;
            case '1111':
                $hop = -2;
                $type='special';
                break;
            
        }
        $this->curpos++;
        return array('hops'=>$hop,'type'=>$type,'status'=>'OK');
        
        
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
        
        
        
        
    }
    
    private function convertToBin($hex){
        
        $num = strlen($hex)*4;
        
        return str_pad(base_convert('0x'.$hex,16,2),$num,0,STR_PAD_LEFT);
        
    }
    
    private function getDif(){
          
        return array(
             '00'=>array('length'=>0,'name'=>'No data')
            ,'01'=>array('length'=>8,'name'=>'8 Bit Integer/Binary')
            ,'02'=>array('length'=>16,'name'=>'16 Bit Integer/Binary')
            ,'03'=>array('length'=>24,'name'=>'24 Bit Integer/Binary')
            ,'04'=>array('length'=>32,'name'=>'32 Bit Integer/Binary')
            ,'05'=>array('length'=>32,'name'=>'32 Bit Real Var Length')
            ,'06'=>array('length'=>48,'name'=>'48 Bit Integer/Binary')
            ,'07'=>array('length'=>64,'name'=>'64 Bit Integer/Binary')
            ,'08'=>array('length'=>0,'name'=>'Selection for Readout')
            ,'09'=>array('length'=>8,'name'=>'2 digit BCd')
            ,'0a'=>array('length'=>16,'name'=>'4 digit BCd')
            ,'0b'=>array('length'=>24,'name'=>'6 digit BCd')
            ,'0c'=>array('length'=>32,'name'=>'8 digit BCd')
            ,'0d'=>array('length'=>32,'name'=>'variable length')
            ,'0e'=>array('length'=>48,'name'=>'12 digit BCD')
            ,'0f'=>array('length'=>64,'name'=>'Special Functions')
        );
        
        
    }    
    
    private function calculateVif($vif){
        
        if($this->hopper[$this->curpos] == 'fd' || $this->hopper[$this->curpos] == 'fb'){
            $this->curpos++;
            return array('vif'=>$this->vifs[$this->hopper[$this->curpos]]['vife1']);
        }else{
            return array('vif'=>$this->vifs[$this->hopper[$this->curpos]]['vif']);
        }
        
    }

 
    
    private function getVif(){
        
            return array(
                '00'=>array('vif'=>'Energy mWh')
                ,'01'=>array('vif'=>'Energy 10<sup>-2</sup> Wh')
                ,'02'=>array('vif'=>'Energy 10<sup>-1</sup> Wh')
                ,'03'=>array('vif'=>'Energy Wh')
                ,'04'=>array('vif'=>'Energy 10<sup>1</sup> Wh')
                ,'05'=>array('vif'=>'Energy 10<sup>2</sup> Wh')
                ,'06'=>array('vif'=>'Energy kWh')
                ,'07'=>array('vif'=>'Energy 10<sup>4</sup> Wh')
                ,'08'=>array('vif'=>'Energy J')
                ,'09'=>array('vif'=>'Energy 10<sup>1</sup> J')
                ,'0a'=>array('vif'=>'Energy 10<sup>2</sup> J')
                ,'0b'=>array('vif'=>'Energy kJ')
                ,'0c'=>array('vif'=>'Energy 10<sup>4</sup> J')
                ,'0d'=>array('vif'=>'Energy 10<sup>5</sup> J')
                ,'0e'=>array('vif'=>'Energy MJ')
                ,'0f'=>array('vif'=>'Energy 10<sup>7</sup> J')
                ,'10'=>array('vif'=>'Volume cm<sup>3</sup>')
                ,'11'=>array('vif'=>'Volume 10<sup>-5</sup> m<sup>3</sup>')
                ,'12'=>array('vif'=>'Volume 10<sup>-4</sup> m<sup>3</sup>')
                ,'13'=>array('vif'=>'Volume l')
                ,'14'=>array('vif'=>'Volume 10<sup>-2</sup> m<sup>3</sup>')
                ,'15'=>array('vif'=>'Volume 10<sup>-1</sup> m<sup>3</sup>')
                ,'16'=>array('vif'=>'Volume m<sup>3</sup>')
                ,'17'=>array('vif'=>'Volume 10<sup>1</sup> m<sup>3</sup>')
                ,'18'=>array('vif'=>'Mass g')
                ,'19'=>array('vif'=>'Mass 10<sup>-2</sup> kg')
                ,'1a'=>array('vif'=>'Mass 10<sup>-1</sup> kg')
                ,'1b'=>array('vif'=>'Mass kg')
                ,'1c'=>array('vif'=>'Mass 10<sup>1</sup> kg')
                ,'1d'=>array('vif'=>'Mass 10<sup>2</sup> kg')
                ,'1e'=>array('vif'=>'Mass t')
                ,'1f'=>array('vif'=>'Mass 10<sup>4</sup> kg')
                ,'20'=>array('vif'=>'On time seconds')
                ,'21'=>array('vif'=>'On time minutes')
                ,'22'=>array('vif'=>'On time hours')
                ,'23'=>array('vif'=>'On time days')
                ,'24'=>array('vif'=>'Operating time seconds')
                ,'25'=>array('vif'=>'Operating time minutes')
                ,'26'=>array('vif'=>'Operating time hours')
                ,'27'=>array('vif'=>'Operating time days')
                ,'28'=>array('vif'=>'Power mW')
                ,'29'=>array('vif'=>'Power 10<sup>-2</sup> W')
                ,'2a'=>array('vif'=>'Power 10<sup>-1</sup> W')
                ,'2b'=>array('vif'=>'Power W')
                ,'2c'=>array('vif'=>'Power 10<sup>1</sup> W')
                ,'2d'=>array('vif'=>'Power 10<sup>2</sup> W')
                ,'2e'=>array('vif'=>'Power kW')
                ,'2f'=>array('vif'=>'Power 10<sup>4</sup> W')
                ,'30'=>array('vif'=>'Power J/h')
                ,'31'=>array('vif'=>'Power 10<sup>1</sup> J/h')
                ,'32'=>array('vif'=>'Power 10<sup>2</sup> J/h')
                ,'33'=>array('vif'=>'Power kJ/h')
                ,'34'=>array('vif'=>'Power 10<sup>4</sup> J/h')
                ,'35'=>array('vif'=>'Power 10<sup>5</sup> J/h')
                ,'36'=>array('vif'=>'Power MJ/h')
                ,'37'=>array('vif'=>'Power 10<sup>7</sup> J/h')
                ,'38'=>array('vif'=>'Volume flow cm<sup>3</sup>/h')
                ,'39'=>array('vif'=>'Volume flow 10<sup>-5</sup> m<sup>3</sup>/h')
                ,'3a'=>array('vif'=>'Volume flow 10<sup>-4</sup> m<sup>3</sup>/h')
                ,'3b'=>array('vif'=>'Volume flow l/h')
                ,'3c'=>array('vif'=>'Volume flow 10<sup>-2</sup> m<sup>3</sup>/h')
                ,'3d'=>array('vif'=>'Volume flow 10<sup>-1</sup> m<sup>3</sup>/h')
                ,'3e'=>array('vif'=>'Volume flow m<sup>3</sup>/h')
                ,'3f'=>array('vif'=>'Volume flow 10<sup>1</sup> m<sup>3</sup>/h')
                ,'40'=>array('vif'=>'Volume flow ext. 10<sup>-7</sup> m<sup>3</sup>/min')
                ,'41'=>array('vif'=>'Volume flow ext. cm<sup>3</sup>/min')
                ,'42'=>array('vif'=>'Volume flow ext. 10<sup>-5</sup> m<sup>3</sup>/min')
                ,'43'=>array('vif'=>'Volume flow ext. 10<sup>-4</sup> m<sup>3</sup>/min')
                ,'44'=>array('vif'=>'Volume flow ext. l/min')
                ,'45'=>array('vif'=>'Volume flow ext. 10<sup>-2</sup> m<sup>3</sup>/min')
                ,'46'=>array('vif'=>'Volume flow ext. 10<sup>-1</sup> m<sup>3</sup>/min')
                ,'47'=>array('vif'=>'Volume flow ext. m<sup>3</sup>/min')
                ,'48'=>array('vif'=>'Volume flow ext. mm<sup>3</sup>/s')
                ,'49'=>array('vif'=>'Volume flow ext. 10<sup>-8</sup> m<sup>3</sup>/s')
                ,'4a'=>array('vif'=>'Volume flow ext. 10<sup>-7</sup> m<sup>3</sup>/s')
                ,'4b'=>array('vif'=>'Volume flow ext. cm<sup>3</sup>/s')
                ,'4c'=>array('vif'=>'Volume flow ext. 10<sup>-5</sup> m<sup>3</sup>/s')
                ,'4d'=>array('vif'=>'Volume flow ext. 10<sup>-4</sup> m<sup>3</sup>/s')
                ,'4e'=>array('vif'=>'Volume flow ext. l/s')
                ,'4f'=>array('vif'=>'Volume flow ext. 10<sup>-2</sup> m<sup>3</sup>/s')
                ,'50'=>array('vif'=>'Mass g/h')
                ,'51'=>array('vif'=>'Mass 10<sup>-2</sup> kg/h')
                ,'52'=>array('vif'=>'Mass 10<sup>-1</sup> kg/h')
                ,'53'=>array('vif'=>'Mass kg/h')
                ,'54'=>array('vif'=>'Mass 10<sup>1</sup> kg/h')
                ,'55'=>array('vif'=>'Mass 10<sup>2</sup> kg/h')
                ,'56'=>array('vif'=>'Mass t/h')
                ,'57'=>array('vif'=>'Mass 10<sup>4</sup> kg/h')
                ,'58'=>array('vif'=>'Flow temperature 10<sup>-3</sup> °c')
                ,'59'=>array('vif'=>'Flow temperature 10<sup>-2</sup> °c')
                ,'5a'=>array('vif'=>'Flow temperature 10<sup>-1</sup> °c')
                ,'5b'=>array('vif'=>'Flow temperature °c')
                ,'5c'=>array('vif'=>'Return temperature 10<sup>-3</sup> °c')
                ,'5d'=>array('vif'=>'Return temperature 10<sup>-2</sup> °c')
                ,'5e'=>array('vif'=>'Return temperature 10<sup>-1</sup> °c')
                ,'5f'=>array('vif'=>'Return temperature °c')
                ,'60'=>array('vif'=>'Temperature difference mK')
                ,'61'=>array('vif'=>'Temperature difference 10<sup>-2</sup> K')
                ,'62'=>array('vif'=>'Temperature difference 10<sup>-1</sup> K')
                ,'63'=>array('vif'=>'Temperature difference K')
                ,'64'=>array('vif'=>'External temperature 10<sup>-3</sup> °c')
                ,'65'=>array('vif'=>'External temperature 10<sup>-2</sup> °c')
                ,'66'=>array('vif'=>'External temperature 10<sup>-1</sup> °c')
                ,'67'=>array('vif'=>'External temperature °c')
                ,'68'=>array('vif'=>'Pressure mbar')
                ,'69'=>array('vif'=>'Pressure 10<sup>-2</sup> bar')
                ,'6a'=>array('vif'=>'Pressure 10⁻1 bar')
                ,'6b'=>array('vif'=>'Pressure bar')
                ,'6c'=>array('vif'=>'Date type G')
                ,'6d'=>array('vif'=>'DateTime type F')
                ,'6e'=>array('vif'=>'Units for H.C.A.')
                ,'6f'=>array('vif'=>'Reserved')
                ,'70'=>array('vif'=>'Averaging duration seconds')
                ,'71'=>array('vif'=>'Averaging duration minutes')
                ,'72'=>array('vif'=>'Averaging duration hours')
                ,'73'=>array('vif'=>'Averaging duration days')
                ,'74'=>array('vif'=>'Actuality duration seconds','vife1'=>'Remaining Battery life time (days)')
                ,'75'=>array('vif'=>'Actuality duration minutes')
                ,'76'=>array('vif'=>'Actuality duration hours')
                ,'77'=>array('vif'=>'Actuality duration days')
                ,'78'=>array('vif'=>'Fabrication no')
                ,'79'=>array('vif'=>'Enhanced identification')
                ,'80'=>array('vif'=>'Address')
                ,'7c'=>array('vif'=>'VIF in following string (length in first byte)')
                ,'7e'=>array('vif'=>'Any VIf')
                ,'7f'=>array('vif'=>'Manufacturer specific')
            );
        
        
    }
         
    
    
    private function getDeviceType(){
        
        
        return array(
            '00'=>'Other',
            '01'=>'Oil',
            '02'=>'Electricity',
            '03'=>'Gas',
            '04'=>'Head',
            '05'=>'Steam ',
            '06'=>'Warm water (30-90 °C)',
            '07'=>'Water ',
            '08'=>'Heat cost allocator ',
            '09'=>'Compressed air ',
            '0a'=>'Cooling load meter (Volume measured at return temperature=>outlet)',
            '0b'=>'Cooling load meter (Volume measured at flow temperature=>inlet)',
            '0c'=>'Heat (Volume measured at flow temperature=>inlet)',
            '0d'=>'Heat / Cooling load meter',
            '0e'=>'Bus / System component',
            '0f'=>'Unknown medium',
            '10'=>'Reserved for consumption meter',
            '11'=>'Reserved for consumption meter',
            '12'=>'Reserved for consumption meter',
            '13'=>'Reserved for consumption meter',
            '14'=>'Calorific value',
            '15'=>'Hot water (≥ 90 °C)',
            '16'=>'Cold water',
            '17'=>'Dual register (hot/cold) water meter',
            '18'=>'Pressure',
            '19'=>'A/D Converter',
            '1a'=>'Smoke detector',
            '1b'=>'Room sensor (eg temperature or humidity)',
            '1c'=>'Gas detector',
            '1d'=>'Reserved for sensors',
            '1f'=>'Reserved for sensors',
            '20'=>'Breaker (electricity)',
            '21'=>'Valve (gas or water)',
            '22'=>'Reserved for switching devices',
            '23'=>'Reserved for switching devices',
            '24'=>'Reserved for switching devices',
            '25'=>'Customer unit (display device)',
            '26'=>'Reserved for customer units',
            '27'=>'Reserved for customer units',
            '28'=>'Waste water',
            '29'=>'Garbage',
            '2a'=>'Reserved for Carbon dioxide',
            '2b'=>'Reserved for environmental meter',
            '2c'=>'Reserved for environmental meter',
            '2d'=>'Reserved for environmental meter',
            '2e'=>'Reserved for environmental meter',
            '2f'=>'Reserved for environmental meter',
            '30'=>'Reserved for system devices',
            '31'=>'Reserved for communication controller',
            '32'=>'Reserved for unidirectional repeater',
            '33'=>'Reserved for bidirectional repeater',
            '34'=>'Reserved for system devices',
            '35'=>'Reserved for system devices',
            '36'=>'Radio converter (system side)',
            '37'=>'Radio converter (meter side)',
            '38'=>'Reserved for system devices',
            '39'=>'Reserved for system devices',
            '3a'=>'Reserved for system devices',
            '3b'=>'Reserved for system devices',
            '3c'=>'Reserved for system devices',
            '3d'=>'Reserved for system devices',
            '3e'=>'Reserved for system devices',
            '3f'=>'Reserved for system devices'
        );
        
        
    }
}