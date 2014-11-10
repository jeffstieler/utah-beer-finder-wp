<div class="row store-info">
	<?php if ( is_single() ) : ?>
	<div class="small-12 large-4 columns">
		<?php the_post_thumbnail( 'store-image', array( 'class' => 'th' ) ); ?>
	</div>
	<div class="small-12 large-8 columns">
	<?php else: ?>
	<div class="small-12 large-2 columns">
		<a href="<?php the_permalink(); ?>">
			<?php the_post_thumbnail( 'archive-image', array( 'class' => 'th' ) ); ?>
		</a>
	</div>
	<div class="small-12 large-10 columns">
	<?php endif; ?>
		<div class="row">
			<div class="large-12 columns">
				<?php if ( is_single() ) : ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php else : ?>
				<h5 class="entry-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h5>
				<?php endif; ?>
				<span class="phone">
					<a href="<?php dabc_the_store_tel_link(); ?>"><?php dabc_the_store_phone_number(); ?></a>
				</span>
				<span class="address">
					<?php dabc_the_store_address(); ?>
				</span>
				<?php the_content(); ?>
			</div>
		</div>
	</div>
</div>