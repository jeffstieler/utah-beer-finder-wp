<?php get_header(); ?>
<div class="row">
	<div class="small-12 large-8 columns" role="main">

	<?php do_action('brewtah_before_content'); ?>

	<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class() ?> id="post-<?php the_ID(); ?>">
			<header>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<span class="phone">
					<a href="<?php dabc_the_store_tel_link(); ?>"><?php dabc_the_store_phone_number(); ?></a>
				</span>
				<span class="address">
					<?php dabc_the_store_address(); ?>
				</span>

			</header>
			<?php do_action('brewtah_post_before_entry_content'); ?>
			<div class="entry-content">

			<?php if ( has_post_thumbnail() ): ?>
				<div class="row">
					<div class="column">
						<?php the_post_thumbnail('', array('class' => 'th')); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php the_content(); ?>
			<?php get_template_part( 'parts/store-inventory' ); ?>
			</div>
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
