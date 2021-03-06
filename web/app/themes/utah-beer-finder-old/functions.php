<?php

// Various clean up functions
require_once('library/cleanup.php');

// Required for Foundation to work properly
require_once('library/foundation.php');

// Register all navigation menus
require_once('library/navigation.php');

// Add menu walker
require_once('library/menu-walker.php');

// Create widget areas in sidebar and footer
require_once('library/widget-areas.php');

// Return entry meta information for posts
require_once('library/entry-meta.php');

// Enqueue scripts
require_once('library/enqueue-scripts.php');

// Add theme support
require_once('library/theme-support.php');

/**
 * Include the Composer dependencies
 */
require dirname( __FILE__ ) . '/plugins/objects-to-objects/objects-to-objects.php';
require dirname( __FILE__ ) . '/plugins/post-selection-ui/post-selection-ui.php';
require dirname( __FILE__ ) . '/plugins/titan-framework/titan-framework.php';
require dirname( __FILE__ ) . '/plugins/jigsaw/jigsaw.php';

// Add DABC functionality
require_once( 'library/dabc/dabc.php' );
