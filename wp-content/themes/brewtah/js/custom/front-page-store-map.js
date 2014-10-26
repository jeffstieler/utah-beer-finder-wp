(function(){

	var map, mapOptions, mapDiv;

	function initialize() {

		mapOptions = {
			center: new google.maps.LatLng( 40.7347809539, -111.916627902 ),
			zoom: 10
		};

		mapDiv = document.getElementById( 'store-map' );

		map = new google.maps.Map( mapDiv, mapOptions );

		if ( ! _.isUndefined( storeLocations ) ) {

			_.each( storeLocations, function( store, i ) {

				var storeLat = parseFloat( store.lat );
				var storeLng = parseFloat( store.lng );
				var position = new google.maps.LatLng( storeLat, storeLng );

				var marker = new google.maps.Marker( {
					position: position,
					title: store.name,
					map: map
				} );

			} );

		}

	}

	if ( 'undefined' !== typeof( google ) ) {

		google.maps.event.addDomListener(window, 'load', initialize);

	}

})();