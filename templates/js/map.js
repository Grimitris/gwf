/* 
 * Telegrams GWF map loading functions
 * Access token: pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw
 */

var mapContainer;
var markers = [];
mapboxgl.accessToken = 'pk.eyJ1IjoiZGltcGFwIiwiYSI6ImNqbHJwZXpoaDA3NjMzcHFyNnlmY3lnc2YifQ.iwXWCA1zRNqmvsQpJkbsnw';
var markerlocations = [];

var map = {
    
    loadMap : function(){
        
        mapContainer = new mapboxgl.Map({
            container: 'mapArea', // container id
            style: 'mapbox://styles/mapbox/streets-v9', // stylesheet location
            center: [8.2472,47.0547496], // starting position [lng, lat]
            zoom: 9 // starting zoom
        });
        this.showBuildings();
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
                    //add to stored locations for routing
                    markerlocations.push(feature.center);
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
        
    }
    
    
    
}