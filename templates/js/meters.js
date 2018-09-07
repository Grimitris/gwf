/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function(){
    
    calls.getData();
    
});

var calls = {
    
    getData : function(){
        $.ajax({
            url: "http://kaagar.com/gwf/?call=decodedJsonData",
            dataType: 'json',
            type:'POST',
            crossDomain: true
        }).done(function(data) {
            
            console.log(data);
            
        });
        
    }
    
}