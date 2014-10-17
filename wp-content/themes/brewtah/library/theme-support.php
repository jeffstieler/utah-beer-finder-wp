<?php
function brewtah_theme_support() {
    // Add language support
    load_theme_textdomain('brewtah', get_template_directory() . '/languages');

    // Add menu support
    add_theme_support('menus');

    // Add post thumbnail support: http://codex.wordpress.org/Post_Thumbnails
    add_theme_support('post-thumbnails');

	set_post_thumbnail_size(150, 150, false);

    // rss thingy
    add_theme_support('automatic-feed-links');

    // Add post formarts support: http://codex.wordpress.org/Post_Formats
    add_theme_support('post-formats', array('aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat'));

	// Add image sizes
	add_image_size( 'beer-single-image', 250, 250, true );

	add_image_size( 'beer-sidebar-image', 50, 50, true );

}

add_action('after_setup_theme', 'brewtah_theme_support');
?>