<?php get_header(); ?>
<div class="row">
	<div class="small-12 large-8 columns" role="main">

	<?php do_action('ubf_before_content'); ?>

	<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class() ?> id="post-<?php the_ID(); ?>">
			<header>
				<?php get_template_part( 'parts/store-info' ); ?>
			</header>
			<?php do_action('ubf_post_before_entry_content'); ?>
			<div class="entry-content">
			<?php get_template_part( 'parts/store-inventory' ); ?>
			</div>
			<?php do_action('ubf_post_before_comments'); ?>
			<?php comments_template(); ?>
			<?php do_action('ubf_post_after_comments'); ?>
		</article>
	<?php endwhile;?>

	<?php do_action('ubf_after_content'); ?>

	</div>
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
