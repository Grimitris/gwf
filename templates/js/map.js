/* 
 * Telegrams GWF map loading functions
 * Access token: pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw
 * Author: Dimitris Papadopoulos
 */

var mapContainer;
var markers = [];
mapboxgl.accessToken = 'pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw';
var dropoffs = turf.featureCollection([]);
var nothing = turf.featureCollection([]);
var pointHopper = {};
var startLocation = [8.2989119,47.0384419]; 
var endLocation = [8.2989119,47.0384419];
var lastQueryTime = 0;
var lastAtRestaurant = 0;
var keepTrack = [];
var currentSchedule = [];
var currentRoute = null;

var calls = {
    
    getData : function(){
        $.ajax({
            url: "http://kaagar.com/gwf/?call=decodedJsonData",
            dataType: 'json',
            type:'POST',
            crossDomain: true
        }).done(function(data) {
            mapContainer.on('load', function() {
                $.each(data,function(key,value){
                    if(value.address){
                        map.addmarker(value.telegram.key,value.address,value.telegram.parsed);
                    }
                });
                map.updateDropoffs(dropoffs);
                
            });
            
        });
        
    }
    
};



var map = {
    
    loadMap : function(){
        
        mapContainer = new mapboxgl.Map({
            container: 'mapArea', // container id
            style: 'mapbox://styles/mapbox/streets-v9', // stylesheet location
            center: [8.2472,47.0547496], // starting position [lng, lat]
            zoom: 9 // starting zoom
        });
        
        //make the map all fancy and 3D building-y
        this.showBuildings();
        
        //create the dropoff layer to add navigation
        this.addDropoffs();
        
        //add the routing layer to show route
        this.addrouteLayer();
        
        
        
    },
    
    addmarker : function(markerkey,location,data){
        
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
            
                    //add to dropoffs
                    map.newDropoff(mapContainer.unproject(feature.center));
                    
                    //add marker with styled label
                    markers[markerkey] = new mapboxgl.Marker()
                        .setLngLat(feature.center)
                        .setPopup(new mapboxgl.Popup({ offset: 25 }) // add popups
                        .setHTML('<h3>' + markerkey + '</h3><p>' + data + '</p>'))
                        .addTo(mapContainer);
                }
            });
        
    },
    
    showBuildings : function(){
        
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
    
    addDropoffs : function(){
        
        mapContainer.on('load', function() {
            mapContainer.addLayer({
                id: 'dropoffs-symbol',
                type: 'symbol',
                source: {
                  data: dropoffs,
                  type: 'geojson'
                },
                layout: {
                  'icon-allow-overlap': true,
                  'icon-ignore-placement': true
                  //'icon-image': 'marker-15'
                }
            });
        });
        
    },
    
    newDropoff : function(coords) {
        // Store the clicked point as a new GeoJSON feature with
        // two properties: `orderTime` and `key`
        var pt = turf.point(
          [coords.lng, coords.lat],
          {
            orderTime: Date.now(),
            key: Math.random()
          }
        );
        dropoffs.features.push(pt);
        pointHopper[pt.properties.key] = pt;
    },

    updateDropoffs : function(geojson) {
        console.log(geojson);
        mapContainer.getSource('dropoffs-symbol').setData(geojson);
    },
    
    addrouteLayer : function(){
        mapContainer.on('load', function() {
            mapContainer.addSource('route', {
              type: 'geojson',
              data: nothing
            });

            mapContainer.addLayer({
              id: 'routeline-active',
              type: 'line',
              source: 'route',
              layout: {
                'line-join': 'round',
                'line-cap': 'round'
              },
              paint: {
                'line-color': '#3887be',
                'line-width': {
                  base: 1,
                  stops: [[12, 3], [22, 12]]
                }
              }
            }, 'waterway-label');
        });
    },
    
    assembleQueryURL : function() {

        // Store the location of the truck in a variable called coordinates
        var coordinates = [startLocation];
        var distributions = [];
        keepTrack = [startLocation];
        
        // Create an array of GeoJSON feature collections for each point
        var restJobs = map.objectToArray(pointHopper);
       
        if (restJobs.length > 0) {

          
          var needToPickUp = restJobs.filter(function(d, i) {
            return d.properties.orderTime > lastAtRestaurant;
          }).length > 0;

         
          if (needToPickUp) {
            var restaurantIndex = coordinates.length;
            // Add the restaurant as a coordinate
            coordinates.push(endLocation);
            // push the restaurant itself into the array
            keepTrack.push(pointHopper.warehouse);
          }
          var ia = 0;
          restJobs.forEach(function(d, i) {
            if(ia > 9)return;
            // Add dropoff to list
            keepTrack.push(d);
            coordinates.push(d.geometry.coordinates);
            //console.log(d.geometry.coordinates);
            // if order not yet picked up, add a reroute
            if (needToPickUp && d.properties.orderTime > lastAtRestaurant) {
              //distributions.push(restaurantIndex + ',' + (coordinates.length - 1));
            }
            ia++;
          });
        }
        
        // Set the profile to `driving`
        // Coordinates will include the current location of the truck,
        return 'https://api.mapbox.com/optimized-trips/v1/mapbox/driving/' + coordinates.join(';') + '?distributions=' + distributions.join(';') + '&overview=full&steps=true&geometries=geojson&source=first&access_token=' + mapboxgl.accessToken;
    },

    objectToArray : function(obj) {
        
        //console.log(Object.keys(obj));

        var routeGeoJSON = Object.keys(obj).map(function(key){
         
          return obj[key];
        
        });
        
        return routeGeoJSON;
    },
    
    requestOptimizedroute : function(){
        
        var urlh = map.assembleQueryURL();
        
        $.ajax({
            method: 'GET',
            url: urlh
          }).done(function(data) {
            // Create a GeoJSON feature collection
            //console.log(data);
            var routeGeoJSON = turf.featureCollection([turf.feature(data.trips[0].geometry)]);

            // If there is no route provided, reset
            if (!data.trips[0]) {
              routeGeoJSON = nothing;
            } else {
              // Update the `route` source by getting the route source
              // and setting the data equal to routeGeoJSON
              mapContainer.getSource('route')
                .setData(routeGeoJSON);
            }        
            
        });
    }
};

$(document).ready(function(){
      
    map.loadMap();
    calls.getData();
    $('body').on( "click", "#getRoutes", function() {
       map.requestOptimizedroute();
    });

});

/*
 * https://api.mapbox.com/optimized-trips/v1/mapbox/driving/8.2989119,47.0384419;8.2989119,47.0384419;6.695703551636683,47.24420591937948;6.695695186917732,47.24420327073392;6.695696039735537,47.244203262344655;6.695703551636683,47.24420591937948;6.695696642613257,47.244203278192174;6.695703551636683,47.24420591937948;6.695703551636683,47.24420591937948;6.695703551636683,47.24420591937948;6.695696407781128,47.24420406691132;6.695703551636683,47.24420591937948?overview=full&steps=true&geometries=geojson&source=first&access_token=pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw
 https://api.mapbox.com/optimized-trips/v1/mapbox/driving/8.2989119,47.0384419;8.2989119,47.0384419;6.695703551636683,47.24420591937948;6.695695186917732,47.24420327073392;6.695696039735537,47.244203262344655;6.695703551636683,47.24420591937948;6.695696642613257,47.244203278192174;6.695703551636683,47.24420591937948;6.695703551636683,47.24420591937948;6.695703551636683,47.24420591937948;6.695696407781128,47.24420406691132;6.695703551636683,47.24420591937948?distributions=&overview=full&steps=true&geometries=geojson&source=first&access_token=pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw
 *
 *https://api.mapbox.com/optimized-trips/v1/mapbox/driving/8.2989119,47.0384419;8.2989119,47.0384419;7.19626812685155,47.24420591937948;7.19625976213257,47.24420327073392;7.196260614950404,47.244203262344655;7.19626812685155,47.24420591937948;7.196261217828152,47.244203278192174;7.19626812685155,47.24420591937948;7.19626812685155,47.24420591937948;7.19626812685155,47.24420591937948;7.1962609829959945,47.24420406691132;7.19626812685155,47.24420591937948?distributions=&overview=full&steps=true&geometries=geojson&source=first&access_token=pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw
 **/