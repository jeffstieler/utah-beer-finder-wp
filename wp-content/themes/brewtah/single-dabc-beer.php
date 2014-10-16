<?php get_header(); ?>
<div class="row">
	<div class="small-12 large-8 columns" role="main">

	<?php do_action('brewtah_before_content'); ?>

	<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class() ?> id="post-<?php the_ID(); ?>">

			<?php do_action('brewtah_post_before_entry_content'); ?>
			<div class="entry-content">

				<div class="row beer-info">
					<div class="small-12 large-3 columns">
						<?php the_post_thumbnail('', array('class' => 'th')); ?>
					</div>
					<div class="small-12 large-9 columns">
						<div class="row">
							<div class="large-12 columns">
								<h1 class="entry-title"><?php the_title(); ?></h1>
							</div>
						</div>
						<div class="row">
							<div class="large-3 columns">
								<span>94</span>
								<p>Overall Score</p>
							</div>
							<div class="large-3 columns">
								<span>98</span>
								<p>Style Score</p>
							</div>
							<div class="large-3 columns">
								<span>94</span>
								<p>ABV</p>
							</div>
							<div class="large-3 columns">
								<span>94</span>
								<p>Calories</p>
							</div>
						</div>
					</div>
				</div>
				
				<div class="row beer-description">
					<?php the_content(); ?>
				</div>

			</div>
			<footer>
				<?php wp_link_pages(array('before' => '<nav id="page-nav"><p>' . __('Pages:', 'brewtah'), 'after' => '</p></nav>' )); ?>
				<p><?php the_tags(); ?></p>
			</footer>
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
