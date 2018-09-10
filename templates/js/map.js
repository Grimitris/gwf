/* 
 * Telegrams GWF map loading functions and dom manipulation
 * Access token: pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw
 * Author: Dimitris Papadopoulos
 */

var mapContainer;
var markers = [];
mapboxgl.accessToken = 'pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw';
var pointHopper = [];
var newHopper = [];


var calls = {
    
    //curl local API for complete data
    getData : function(){
        $.ajax({
            url: "http://kaagar.com/gwf/?call=decodedJsonData",
            dataType: 'json',
            type:'POST',
            crossDomain: true
        }).done(function(data) {
            $('.sidebar.navbar-nav').empty();
            var countItems = 0;
            var errors = 0;
            //wait for map to load to start adding elements to it
            mapContainer.on('load', function() {
                $.each(data,function(key,value){
                    if(value.address){ //if there's on address, it can't be displayed on the map
                        map.addmarker(value.address,value.telegram); //add the marker with the info box
                        if(value.telegram.parsed){
                            countItems++;
                            console.log(value.telegram.parsed);
                        } //count total successful attempts
                        else{ errors++; } //count failed attempts
                    }
                });
                //update the UI with above data.
                $('#deviceCounter').html('<strong>'+countItems+'</strong> decoded devices total');
                $('#deviceErrors').html('<strong>'+errors+'</strong> devices with missing data or decoding errors');
                $('#successInfo,#errorInfo,#routingInfo').slideDown(1200);
               
            });
            
        });
        
    }
    
};



var map = {
    
    loadMap : function(){ //Load the map with mapbox
        
        mapContainer = new mapboxgl.Map({
            container: 'mapArea', // container id
            style: 'mapbox://styles/mapbox/streets-v9', // stylesheet location
            center: [8.2472,47.0547496], // starting position [lng, lat]
            zoom: 9 // starting zoom
        });
        
        //make the map all fancy and 3D building-y
        this.showBuildings();
        
    },
    
    addmarker : function(location,data){ //add marker with styled label
        console.log(data);
        //if point has no data, return
        if(!data.parsed)
            return false;
        
        var mapboxClient = mapboxSdk({ accessToken: mapboxgl.accessToken });
        //geocode location
        mapboxClient.geocoding.forwardGeocode({
            query: location,
            autocomplete: false,
            limit: 1
        }).send()
            .then(function (response) {
                if (response && response.body && response.body.features && response.body.features.length) {
                    
                    var feature = response.body.features[0];
                    
                    //build data table
                    
                    var appendData = '<ul>';
                    $.each(data.parsed.data,function(key,value){
                        appendData+='<li>';
                        if(key == 'Special Functions'){
                            
                            $.each(value,function(fk,fv){
                                appendData+='byte'+fv.byte+'=0x'+fv.data+' ';
                            });
                            
                        }else{
                            appendData+=key+':'+value;
                        }
                        
                        appendData+='</li>';
                    });
                    newHopper.push([feature.center[0],feature.center[1]]);
                    appendData+='<li>Geocoded: '+feature.center[0]+','+feature.center[1]+'</li>';
                    appendData+='<ul>';
                    
                    
                    //add to dropoffs
                    //map.newDropoff(mapContainer.unproject(feature.center));
                    
                    $('.sidebar.navbar-nav').append(
                    
                        '<li class="nav-item dropdown">'+
                        '  <a class="nav-link dropdown-toggle devDetailsListItem" href="#" id="'+data.parsed.meterID+'" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">'+
                        '    <i class="fas fa-fw fa-tachometer-alt"></i>'+
                        //'    <span>'+data.parsed.devType+'</span>'+
                        '    <span>'+data.parsed.devType+' '+data.parsed.meterID+ '</span>'+
                        '  </a>'+
                        '  <div class="dropdown-menu" aria-labelledby="pagesDropdown" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(5px, 56px, 0px);">'+
                        appendData+
                        '  </div>'+
                        '</li>'

                    );

                    //add marker with styled label
                    markers['m_'+data.parsed.meterID] = new mapboxgl.Marker()
                        .setLngLat(feature.center)
                        .setPopup(new mapboxgl.Popup({ offset: 25 }) // add popups
                        .setHTML('<h3>'+data.parsed.devType+' '+data.parsed.meterID + '</h3><p>' + appendData + '</p>'))
                        //.setHTML('<h3>'+data.parsed.devType+'</h3><p>' + appendData + '</p>'))
                        .addTo(mapContainer);
                        
                }
            });
        
    },
    
    showBuildings : function(){ //Use 3D buildings plugin to show 3D buildings on zoom
        
        mapContainer.on('load', function() {
            // Insert the layer beneath any symbol layer.
            var layers = mapContainer.getStyle().layers;

            var labelLayerId;
            for (var i = 0; i < layers.length; i++) {
                if (layers[i].type === 'symbol' && layers[i].layout['text-field']) {
                    labelLayerId = layers[i].id;
                    break;
                }
            }

            mapContainer.addLayer({
                'id': '3d-buildings',
                'source': 'composite',
                'source-layer': 'building',
                'filter': ['==', 'extrude', 'true'],
                'type': 'fill-extrusion',
                'minzoom': 15,
                'paint': {
                    'fill-extrusion-color': '#aaa',

                    // use an 'interpolate' expression to add a smooth transition effect to the
                    // buildings as the user zooms in
                    'fill-extrusion-height': [
                        "interpolate", ["linear"], ["zoom"],
                        15, 0,
                        15.05, ["get", "height"]
                    ],
                    'fill-extrusion-base': [
                        "interpolate", ["linear"], ["zoom"],
                        15, 0,
                        15.05, ["get", "min_height"]
                    ],
                    'fill-extrusion-opacity': .6
                }
            }, labelLayerId);
        });
        
    },
    
    newDropoff : function(coords) {
        // Store the clicked point as a new GeoJSON feature with
        pointHopper.push(coords);
    },
    
    assembleQueryURL : function() { //generate the directions service URI for mapbox

        // Store the location of the truck in a variable called coordinates
        var coordinates = [];
       
        if (newHopper.length > 0) {

          var ia = 0;
          var lastlat = false;
          var lastlng = false;
          
          newHopper.forEach(function(d, i) {
            
            
            if(ia > 23)return;
            var distanceFromLast = map.calcCrow(lastlat, lastlng, d[0],d[1])
            
            
            if(!lastlat || !lastlng) {
                
                coordinates.push([d[0],d[1]]);
                ia++;
                lastlat = d[0];
                lastlng = d[1];
                
            }
            //else{
            else if(distanceFromLast > 0.01){ //check if the previous added point is far enough to constitute need for directions
                console.log('in',distanceFromLast);
                
                coordinates.push([d[0],d[1]]);
                ia++;
                lastlat = d[0];
                lastlng = d[1];
                
            }
            
          });
          
        }
        
        // Set the profile to `driving`
        return 'https://api.mapbox.com/directions/v5/mapbox/driving/' + coordinates.join(';') + '?geometries=geojson&access_token=' + mapboxgl.accessToken;
    },

    
    
    requestOptimizedroute : function(){ //call the mapbox directions service and display the results on map
        
        var urlh = map.assembleQueryURL(); //get the URI
        
        //call for the data
        $.ajax({
            method: 'GET',
            url: urlh
          }).done(function(data) {
              
            //return false;
            var route = data.routes[0].geometry;
            
            //add a polyline layer to the map with the returned data from the directions service
            mapContainer.addLayer({
                id: 'routeline-active',
                type: 'line',
                source: {
                    type: 'geojson',
                    data: {
                        type: 'Feature',
                        geometry: route
                    }
                },
                layout: {
                      "line-join": "round",
                      "line-cap": "round"
                  },
                paint: {
                    'line-width':{ 
                        base :1,
                        stops: [[12,3],[22,12]]
                    },
                    'line-color': '#3887be',
                    
                }
            },'waterway-label');
            
            //add arrows with direction so that the user can understand the polyline better. Same input data as the polyline
            mapContainer.addLayer({
                id: 'routearrows',
                type: 'symbol',
                 source: {
                    type: 'geojson',
                    data: {
                        type: 'Feature',
                        geometry: route
                    }
                },
                layout: {
                  'symbol-placement': 'line',
                  'text-field': '▶',
                  'text-size': {
                    base: 1,
                    stops: [[12, 24], [22, 60]]
                  },
                  'symbol-spacing': {
                    base: 1,
                    stops: [[12, 30], [22, 160]]
                  },
                  'text-keep-upright': false
                },
                paint: {
                  'text-color': '#3887be',
                  'text-halo-color': 'hsl(55, 11%, 96%)',
                  'text-halo-width': 3
                }
              }, 'waterway-label');
            
       
        });
        
    },
    
    //This function takes in latitude and longitude of two location and returns the distance between them as the crow flies (in km)
    calcCrow : function(lat1, lon1, lat2, lon2){
        var R = 6371; // km
        var dLat = map.toRad(lat2-lat1);
        var dLon = map.toRad(lon2-lon1);
        var lat1 = map.toRad(lat1);
        var lat2 = map.toRad(lat2);

        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
          Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2); 
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        var d = R * c;
        return d;
      },

      // Converts numeric degrees to radians
      toRad : function(Value) 
      {
          return Value * Math.PI / 180;
      }
      
  }; //map Object end


//Document initialization actions for UI
$(document).ready(function(){
      
    map.loadMap();
    calls.getData();
    $('body').on( "click", "#getRoutes,#routing", function() {
       map.requestOptimizedroute();
       if($(this).attr('id')=='routing'){
           $('#routing').slideUp(1000);
       }
    });
s    
});
