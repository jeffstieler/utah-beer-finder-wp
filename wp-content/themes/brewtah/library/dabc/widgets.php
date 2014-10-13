<?php

/**
 * Top Rated Beers widget
 */
class DABC_Top_Rated_Beers_Widget extends WP_Widget {

	const CACHE_KEY = 'widget_top_beers';

	public function __construct() {

		$widget_ops = array(
			'classname' => 'widget_top_beers',
			'description' => 'Beers sorted by rating DESC'
		);

		parent::__construct( 'top-beers', 'Top Beers', $widget_ops );

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );

	}

	public function widget( $args, $instance ) {

		$cache = array();

		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( self::CACHE_KEY, 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : 'Top Rated Beers';

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;

		$r = new WP_Query( array(
			'posts_per_page'      => $number,
			'no_found_rows'       => true,
			'post_type'           => DABC_Beer_Post_Type::POST_TYPE,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'meta_key'            => DABC_Beer_Post_Type::TITAN_NAMESPACE . '_' . DABC_Beer_Post_Type::RATEBEER_OVERALL_SCORE,
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC'
		) );

		if ( $r->have_posts() ) :
?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget']; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		if ( ! $this->is_preview() ) {

			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( self::CACHE_KEY, $cache, 'widget' );

		} else {

			ob_end_flush();

		}

	}

	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];

		$this->flush_widget_cache();

		return $instance;

	}

	public function flush_widget_cache() {

		wp_cache_delete( self::CACHE_KEY, 'widget' );

	}

	public function form( $instance ) {

		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;

		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
		<?php

	}

}

register_widget( 'DABC_Top_Rated_Beers_Widget' );