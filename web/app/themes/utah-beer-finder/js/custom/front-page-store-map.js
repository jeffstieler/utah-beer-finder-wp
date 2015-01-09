(function(){

	var map, mapOptions, mapDiv, markers, infoWindow;
	
	var infoWindowTemplate = _.template(
		'<div id="store-info-window">' +
			'<div>' +
				'<a href="<%= permalink %>">' +
					'<%= image %>' +
				'</a>' +
			'</div>' +
			'<div class="store-details">' +
				'<h4><a href="<%= permalink %>"><%= name %></a></h4>' +
				'<a href="<%= telLink %>"><%= phoneNumber %></a>' +
				'<p><%= hours %></p>' +
			'</div>' +
		'</div>'
	);

	function initialize() {

		mapOptions = {
			center: new google.maps.LatLng( 40.7347809539, -111.916627902 ),
			zoom: 10
		};

		mapDiv = document.getElementById( 'store-map' );

		map = new google.maps.Map( mapDiv, mapOptions );

		infoWindow = new google.maps.InfoWindow( {
			maxWidth: 400
		} );

		if ( ! _.isUndefined( storeLocations ) ) {

			markers = _.map( storeLocations, function( store, idx ) {

				var storeLat = parseFloat( store.latitude );
				var storeLng = parseFloat( store.longitude );
				var position = new google.maps.LatLng( storeLat, storeLng );

				var marker = new google.maps.Marker( {
					position: position,
					title: store.name,
					map: map
				} );

				google.maps.event.addListener( marker, 'click', function() {
					openStoreInfo( idx );
				} );

				return marker;

			} );

		}

	}

	function openStoreInfo( markerIdx ) {

		infoWindow.close();
		infoWindow.setContent( infoWindowTemplate( storeLocations[markerIdx] ) );
		infoWindow.open( map, markers[markerIdx] );

	}

	if ( 'undefined' !== typeof( google ) ) {

		google.maps.event.addDomListener(window, 'load', initialize);

	}

})();