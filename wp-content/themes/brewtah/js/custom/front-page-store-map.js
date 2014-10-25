(function(){
	
	var map, mapOptions, mapDiv;
	
	function initialize() {

		mapOptions = {
			center: new google.maps.LatLng(40.7347809539, -111.916627902),
			zoom: 8
		};
		
		mapDiv = document.getElementById('store-map');

		map = new google.maps.Map(mapDiv, mapOptions);

	}
	  
	if ( 'undefined' !== typeof( google ) ) {

		google.maps.event.addDomListener(window, 'load', initialize);

	}
	
})();