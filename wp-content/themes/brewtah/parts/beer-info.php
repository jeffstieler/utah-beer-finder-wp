<div class="row beer-info">
	<div class="small-12 large-3 columns">
		<?php $thumbnail_size = is_single() ? 'beer-single-image' : 'thumbnail'; ?>
		<?php if ( is_search() ) : ?>
		<a href="<?php the_permalink(); ?>">
		<?php endif; ?>
		<?php the_post_thumbnail( $thumbnail_size, array( 'class' => 'th' ) ); ?>
		<?php if ( is_search() ) : ?>
		</a>
		<?php endif; ?>
	</div>
	<div class="small-12 large-9 columns">
		<div class="row">
			<div class="large-12 columns">
				<?php if ( is_single() ) : ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php else : ?>
				<a href="<?php the_permalink(); ?>">
					<h3 class="entry-title"><?php the_title(); ?></h3>
				</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="row">
			<div class="large-3 small-3 columns">
				<span>94</span>
				<p>Overall Score</p>
			</div>
			<div class="large-3 small-3 columns">
				<span>98</span>
				<p>Style Score</p>
			</div>
			<div class="large-3 small-3 columns">
				<span>94</span>
				<p>ABV</p>
			</div>
			<div class="large-3 small-3 columns">
				<span>94</span>
				<p>Calories</p>
			</div>
		</div>
	</div>
</div>