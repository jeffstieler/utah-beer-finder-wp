<?php get_header(); ?>
<div class="row">
	<div class="small-12 large-8 columns" role="main">

		<?php do_action('ubf_before_content'); ?>

		<h2><?php _e('Search Results for', 'utah-beer-finder'); ?> "<?php echo get_search_query(); ?>"</h2>

	<?php if ( have_posts() ) : ?>

		<?php while ( have_posts() ) : the_post(); ?>

			<?php if ( DABC_Beer_Post_Type::POST_TYPE === get_post_type() ) : ?>

				<?php get_template_part( 'parts/beer-info' ); ?>

			<?php else : ?>

				<?php get_template_part( 'content', get_post_format() ); ?>

			<?php endif; ?>

		<?php endwhile; ?>

		<?php else : ?>
			<?php get_template_part( 'content', 'none' ); ?>

	<?php endif;?>

	<?php do_action('ubf_before_pagination'); ?>

	<?php if ( function_exists('ubf_pagination') ) { ubf_pagination(); } else if ( is_paged() ) { ?>

		<nav id="post-nav">
			<div class="post-previous"><?php next_posts_link( __( '&larr; Older posts', 'utah-beer-finder' ) ); ?></div>
			<div class="post-next"><?php previous_posts_link( __( 'Newer posts &rarr;', 'utah-beer-finder' ) ); ?></div>
		</nav>
	<?php } ?>

	<?php do_action('ubf_after_content'); ?>

	</div>
	<?php get_sidebar(); ?>

<?php get_footer(); ?>
