<?php

function dabc_get_overall_rating( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->beers->get_overall_rating( $post_id );

}

function dabc_the_overall_rating( $post_id = null ) {

	echo dabc_get_overall_rating( $post_id );

}

function dabc_get_style_rating( $post_id = null ) {

	$post_id = $post_id ?: get_the_ID();

	$dabc = new DABC();

	return $dabc->beers->get_style_rating( $post_id );

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

	$dabc = new DABC();

	return $dabc->beers->get_calories( $post_id );

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