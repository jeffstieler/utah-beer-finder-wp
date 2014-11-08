<div class="row beer-info">
	<div class="small-12 large-2 columns">
		<?php if ( ! is_single() ) : ?>
		<a href="<?php the_permalink(); ?>">
		<?php endif; ?>
		<?php the_post_thumbnail( 'beer-image', array( 'class' => 'th' ) ); ?>
		<?php if ( ! is_single() ) : ?>
		</a>
		<?php endif; ?>
	</div>
	<div class="small-12 large-10 columns">
		<div class="row">
			<div class="large-12 columns">
				<?php if ( is_single() ) : ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php else : ?>
				<h5 class="entry-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h5>
				<?php endif; ?>
				<h6><?php the_terms( get_the_ID(), 'beer-brewery' ); ?></h6>
				<h6><?php the_terms( get_the_ID(), 'beer-style' ); ?></h6>
			</div>
		</div>
		<div class="row">
			<div class="large-3 small-3 columns">
				<span><?php dabc_the_overall_rating(); ?> / 100</span>
				<p>RB Overall</p>
			</div>
			<div class="large-3 small-3 columns">
				<span><?php dabc_the_style_rating(); ?> / 100</span>
				<p>RB Style</p>
			</div>
			<div class="large-3 small-3 columns">
				<span><?php dabc_the_untappd_rating_score(); ?> / 5.0</span>
				<p>Untappd</p>
			</div>
			<div class="large-3 small-3 columns">
				<span><?php dabc_the_abv(); ?></span>
				<p>ABV</p>
			</div>
		</div>
	</div>
</div>