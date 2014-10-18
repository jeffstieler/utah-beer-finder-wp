<?php get_header(); ?>
<div class="row">
<!-- Row for main content area -->
	<div class="small-12 large-8 columns" role="main">

	<?php if ( have_posts() ) : ?>
		<div class="row">
			<?php paginate_alphabetic_links( get_post_type() ); ?>
		</div>

		<?php /* Start the Loop */ ?>
		<?php while ( have_posts() ) : the_post(); ?>

			<?php if ( DABC_Beer_Post_Type::POST_TYPE === get_post_type() ) : ?>

				<?php get_template_part( 'parts/beer-info' ); ?>

			<?php else : ?>

				<?php get_template_part( 'content', get_post_format() ); ?>

			<?php endif; ?>

		<?php endwhile; ?>

		<?php else : ?>
			<?php get_template_part( 'content', 'none' ); ?>

	<?php endif; // end have_posts() check ?>

	<?php /* Display navigation to next/previous pages when applicable */ ?>
	<?php if ( function_exists('brewtah_pagination') ) { brewtah_pagination(); } else if ( is_paged() ) { ?>
		<nav id="post-nav">
			<div class="post-previous"><?php next_posts_link( __( '&larr; Older posts', 'brewtah' ) ); ?></div>
			<div class="post-next"><?php previous_posts_link( __( 'Newer posts &rarr;', 'brewtah' ) ); ?></div>
		</nav>
	<?php } ?>

	</div>
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>