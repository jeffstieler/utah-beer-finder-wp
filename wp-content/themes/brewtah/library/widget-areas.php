<?php

function ubf_sidebar_widgets() {
  register_sidebar(array(
      'id' => 'sidebar-widgets',
      'name' => __('Sidebar widgets', 'utah-beer-finder'),
      'description' => __('Drag widgets to this sidebar container.', 'utah-beer-finder'),
      'before_widget' => '<article id="%1$s" class="row widget %2$s"><div class="small-12 columns">',
      'after_widget' => '</div></article>',
      'before_title' => '<h6>',
      'after_title' => '</h6>'
  ));

  register_sidebar(array(
      'id' => 'footer-widgets',
      'name' => __('Footer widgets', 'utah-beer-finder'),
      'description' => __('Drag widgets to this footer container', 'utah-beer-finder'),
      'before_widget' => '<article id="%1$s" class="large-4 columns widget %2$s">',
      'after_widget' => '</article>',
      'before_title' => '<h6>',
      'after_title' => '</h6>'
  ));

  register_sidebar(array(
      'id' => 'homepage-widgets',
      'name' => __('Homepage widgets', 'utah-beer-finder'),
      'description' => __('Drag widgets to this footer container', 'utah-beer-finder'),
      'before_widget' => '<div id="%1$s" class="medium-4 columns widget %2$s">',
      'after_widget' => '</div>',
      'before_title' => '<h6>',
      'after_title' => '</h6>'
  ));
}

add_action( 'widgets_init', 'ubf_sidebar_widgets' );

?>