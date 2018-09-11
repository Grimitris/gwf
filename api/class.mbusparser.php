<?php

class mbusParser{
    
    private $packet;
    private $vifs;
    private $header;
    private $hopper;
    private $curpos;
    
    /*
     * Initialize some values
     */
    public function __construct() {
        
       $this->vifs = $this->getVif(); //store available vifs
       $this->deviceType = $this->getDeviceType(); //store device types
       $this->curpos = 2; //ignore the decoding verification
       $this->packet = false;
        $this->header = false;
    }
    
    /*
     * Mbus byte traversal method
     * Order: DIF (get length), VIF (get type), VIFE (if it exists), Data (according to DIF length)
     * 
     */
    public function parseMbus($packet,$header,$debug){
       
       if($packet) $this->packet = $packet;
       if($header) $this->header = $header;
           
        //var_dump($this->header);
        $headersplit = str_split($this->header,2);
        $this->hopper = str_split($this->packet, 2);
        
//        var_dump($headersplit);
//        echo '<br />';
//        var_dump($this->hopper);
//        
//        echo '<br />---<br />';
        //Initialize return data
        $toreturn = [];
        $toreturn['data'] = [];
        
        //var_dump($headersplit);
        
        $toreturn['devType'] = $this->deviceType[$headersplit[18]];
        $toreturn['meterID'] = $headersplit[14].$headersplit[13].$headersplit[12].$headersplit[11];


        //Start by receiving the first DIF
        $curdif = $this->calculateDif($this->hopper[$this->curpos]);

        //Traverse the telegram while there are still data
        $i = 0;
        while($curdif['status'] == 'OK'){ //move the array until it's out of data
            
            $difHops = (int)$curdif['hops']; //get data size
            $difType = $curdif['type']; //get data type
            
            //Find the DIF
            $vifData = $this->calculateVif();
            $this->curpos++;

            $thisVif = $vifData['vif']; //get vif
            $thisVifPost = $vifData['postfix']; //get vif
            $thisVifMulti = $vifData['multiplier']; //get vif
            
            //Parse the data and include it to the return data
            $parsed = $this->parseData($difHops,$difType,$thisVif,$thisVifMulti,$thisVifPost);
            if($difType=='special') $thisVif = 'Special Functions'; //Just for it to be pretty on output
            if($toreturn['data'][$thisVif]){
                $i++;
                $thisVif=$thisVif.'_'.$i;
            }
            
            $toreturn['data'][$thisVif] = $parsed;
           

            $curdif = $this->calculateDif($this->hopper[$this->curpos]); //get the next DIF to start over


        }
        
        return $toreturn;
        
        
    }
    
    /*
     * Data Parsing. Depending on type, different parsing approach is needed
     */
    private function parseData($difhops,$difType,$thisVif,$thisVifMulti,$thisVifPost){
        
        $toParse = [];
        
        for($i = 0; $i <$difhops; $i++){
            
            array_push($toParse,$this->hopper[$this->curpos]);
            
            $this->curpos++;
            
        }
         
        if($thisVif == 'Date' || $thisVif == 'DateTime'){
            
            return $this->decodeDate($toParse);
            
        }else{
            
            switch($difType){
                case 'bcd': //easy mode
                    $calc = (float)implode('',array_reverse($toParse))*((isset($thisVifMulti) && $thisVifMulti)?(float)$thisVifMulti:1);
                    return (string)$calc.$thisVifPost; //attach data description if available
                    break;
                case 'IntBin': //same as BCD, but data has to be converted to decimal
                    $calc = (float)hexdec(implode('',array_reverse($toParse)))*((isset($thisVifMulti) && $thisVifMulti)?(float)$thisVifMulti:1);
                    return (string)$calc.$thisVifPost; //attach data description if available
                    break;
                case 'varlength':
                    $calc = hexdec(implode('',array_reverse($toParse)));
                    return (string)$calc.$thisVifPost; //attach data description if available
                    break;
                case 'special':
                    //manufacturer special
                    //Construct the custom manufacturer part as mentioned in the notes
                    $thisData = $this->hopper[$this->curpos];
                    $toret = [];
                    while($thisData != '2f'){
                        
                        array_push($toret,array('byte'=>$this->curpos+24,'data'=>$thisData));
                        $this->curpos++;
                        $thisData = $this->hopper[$this->curpos];
                    
                    }
                    return $toret;
                    
                    break;
                case 'nodata':
                    return array('result'=>'No Data');
                    break;
                default:
                    return array('result'=>'Error parsing data.');
                    
            
            
            }
            
        }
        
        
    }
    
    /*
     * Date Decoding.
     */
    private function decodeDate($data){
        
        foreach($data as $k=>$v){
            
            $convdata.=strrev($this->convertToBin( $v ));

        }
        
        //CP16 date
        if(count($data) == 2){
            $day = bindec(strrev(substr($convdata,0,5)));
            $month = bindec(strrev(substr($convdata,8,4)));
            $year = bindec(strrev(substr($convdata,12,4)).strrev(substr($convdata,5,3)));
            
            return $day.'.'.$month.'.'.$year;
            
        //CP32 datetime
        }else{
            
            $min = bindec(strrev(substr($convdata,0,6)));
            $hour = bindec(strrev(substr($convdata,8,5)));
            $day = bindec(strrev(substr($convdata,16,5)));
            $month = bindec(strrev(substr($convdata,24,4)));
            $year2 = bindec(strrev(substr($convdata,13,2)));
            $year1 = bindec(strrev(substr($convdata,28,4)).strrev(substr($convdata,21,3)));
            
            $year = 1900+$year2*100+$year1;
            
            return $day.'-'.$month.'-'.$year.' '.$hour.':'.$min;
        }
        
        
    }

    /*
     * return Dif type, description and byte length
     */
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
      
        
    }
    
    private function convertToBin($hex){
        
        $num = strlen($hex)*4;
        
        return str_pad(base_convert('0x'.$hex,16,2),$num,0,STR_PAD_LEFT);
        
    }
    
    /*
     * Get the VIF and move the position
     */
    private function calculateVif($vif){
        
        if($this->hopper[$this->curpos] == 'fd' || $this->hopper[$this->curpos] == 'fb'){
            $this->curpos++;
            return array('vif'=>$this->vifs[$this->hopper[$this->curpos]]['vife1'],'postfix'=>$this->vifs[$this->hopper[$this->curpos]]['postfix'],'multiplier'=>$this->vifs[$this->hopper[$this->curpos]]['multiplier']);
        }else{
            return array('vif'=>$this->vifs[$this->hopper[$this->curpos]]['vif'],'postfix'=>$this->vifs[$this->hopper[$this->curpos]]['postfix'],'multiplier'=>$this->vifs[$this->hopper[$this->curpos]]['multiplier']);
        }
        
    }

    /*
     * VIF storage. With added some details for the known tested values.
     * This has to be edited a bit for perfect support of all types of VIFS
     */
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
                ,'10'=>array('vif'=>'Volume')
                ,'11'=>array('vif'=>'Volume','multiplier'=>0.00001,'postfix'=>'m<sup>3</sup>')
                ,'12'=>array('vif'=>'Volume','multiplier'=>0.0001,'postfix'=>'m<sup>3</sup>')
                ,'13'=>array('vif'=>'Volume','multiplier'=>0.001,'postfix'=>'m<sup>3</sup>') //10^-3
                ,'14'=>array('vif'=>'Volume','multiplier'=>0.01,'postfix'=>'m<sup>3</sup>')
                ,'15'=>array('vif'=>'Volume','multiplier'=>0.1,'postfix'=>'m<sup>3</sup>')
                ,'16'=>array('vif'=>'Volume')
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
                ,'6c'=>array('vif'=>'Date')
                ,'6d'=>array('vif'=>'DateTime')
                ,'6e'=>array('vif'=>'Units for H.C.A.')
                ,'6f'=>array('vif'=>'Reserved')
                ,'70'=>array('vif'=>'Averaging duration seconds')
                ,'71'=>array('vif'=>'Averaging duration minutes')
                ,'72'=>array('vif'=>'Averaging duration hours')
                ,'73'=>array('vif'=>'Averaging duration days')
                ,'74'=>array('vif'=>'Actuality duration seconds','vife1'=>'Remaining Battery (days)')
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
         
    
    /*
     * Device types storage.
     */
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