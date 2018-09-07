/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){
    
    
    
    $.getScript("js/map.js").done(function( script, textStatus ) {
          map.loadMap();
          calls.getData();
    }).fail(function( jqxhr, settings, exception ) {
          console.log( "Triggered ajaxError handler." );
    });
    
    
    
});

var calls = {
    
    getData : function(){
        $.ajax({
            url: "http://kaagar.com/gwf/?call=decodedJsonData",
            dataType: 'json',
            type:'POST',
            crossDomain: true
        }).done(function(data) {
            
            $.each(data,function(key,value){
                //console.log(value[0].key); 
                //console.log(value.telegram.length);
                if(value.address){
                    map.addmarker(value.telegram.key,value.address,value.telegram.parsed);
                }
                
                
            });
            
        });
        
    }
    
}
