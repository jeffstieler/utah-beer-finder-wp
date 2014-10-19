<?php get_header(); ?>
<div class="row">
	<div class="small-12 large-8 columns" role="main">

	<?php do_action('brewtah_before_content'); ?>

	<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class() ?> id="post-<?php the_ID(); ?>">

			<?php do_action('brewtah_post_before_entry_content'); ?>
			<div class="entry-content">

				<?php get_template_part( 'parts/beer-info' ); ?>

				<div class="row beer-description">
					<?php the_content(); ?>
				</div>

			</div>
			<?php get_template_part( 'parts/beer-availability' ); ?>
			<?php do_action('brewtah_post_before_comments'); ?>
			<?php comments_template(); ?>
			<?php do_action('brewtah_post_after_comments'); ?>
		</article>
	<?php endwhile;?>

	<?php do_action('brewtah_after_content'); ?>

	</div>
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
