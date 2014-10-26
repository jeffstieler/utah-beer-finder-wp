<?php get_header(); ?>

<header id="homepage-hero" role="banner">
	<div class="row">
		<div class="small-12 medium-4 columns">
			<h1><a href="<?php bloginfo('url'); ?>" title="<?php bloginfo('name'); ?>"><?php bloginfo('name'); ?></a></h1>
			<h4 class="subheader"><?php bloginfo('description'); ?></h4>
			<a role="button" class="download large button hide-for-small" href="https://github.com/olefredrik/brewtah">Download brewtah</a>
		</div>

		<div class="medium-8 columns show-for-medium-up">
			<div id="store-map"></div>
		</div>

	</div>

</header>

<div class="row">
	<?php dynamic_sidebar( 'homepage-widgets' ); ?>
</div>

<?php get_footer(); ?>
