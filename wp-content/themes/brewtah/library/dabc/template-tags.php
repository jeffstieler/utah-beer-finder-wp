<?php

function dabc_get_overall_rating( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$ratebeer = new Ratebeer_Sync();

	return $ratebeer->get_overall_rating( $post_id );

}

function dabc_the_overall_rating( $post_id = null ) {

	echo dabc_get_overall_rating( $post_id );

}

function dabc_get_style_rating( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$ratebeer = new Ratebeer_Sync();

	return $ratebeer->get_style_rating( $post_id );

}

function dabc_the_style_rating( $post_id = null ) {

	echo dabc_get_style_rating( $post_id );

}

function dabc_get_abv( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->beers->get_abv( $post_id );

}

function dabc_the_abv( $post_id = null ) {

	echo dabc_get_abv( $post_id );

}

function dabc_get_calories( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$ratebeer = new Ratebeer_Sync();

	return $ratebeer->get_calories( $post_id );

}

function dabc_the_calories( $post_id = null ) {

	echo dabc_get_calories( $post_id );

}

function dabc_query_stores_by_number( $store_number ) {

	$dabc = new DABC();

	return $dabc->stores->query_stores_by_number( $store_number );

}

function dabc_get_store_number( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->stores->get_store_number( $post_id );

}

function dabc_get_store_address( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->stores->get_store_address( $post_id );

}

function dabc_the_store_address( $post_id = null ) {

	echo dabc_get_store_address( $post_id );

}

function dabc_get_store_phone_number( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->stores->get_store_phone_number( $post_id );

}

function dabc_the_store_phone_number( $post_id = null ) {

	echo dabc_get_store_phone_number( $post_id );

}

function dabc_get_store_tel_link( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->stores->get_store_tel_link( $post_id );

}

function dabc_the_store_tel_link( $post_id = null ) {

	echo dabc_get_store_tel_link( $post_id );

}

function dabc_get_inventory( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->beers->get_inventory( $post_id );

}

function dabc_get_inventory_last_updated( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->beers->get_inventory_last_updated( $post_id );

}

function dabc_the_inventory_last_updated( $post_id = null ) {

	echo dabc_get_inventory_last_updated( $post_id );

}

function dabc_get_quantity_for_store( $store_number, $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->beers->get_quantity_for_store( $post_id, $store_number );

}

function dabc_the_quantity_for_store( $store_number, $post_id = null ) {

	$store_quantity = dabc_get_quantity_for_store( $store_number, $post_id );

	echo $store_quantity ?: '-';

}

function dabc_get_store_beers( $store_post_id ) {

	$dabc = new DABC();

	return $dabc->get_store_beers( $store_post_id );

}