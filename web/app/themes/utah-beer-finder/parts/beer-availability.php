<section>
<?php

$beer_post_id = get_the_ID();

$stores = new WP_Query( array(
	'connected_type'  => 'dabc_store_beers',
	'connected_items' => $beer_post_id,
	'posts_per_page'  => -1
) );

if ( $stores->have_posts() ) :

?>
	<h3>Store Availability</h3>
	<p>Last Updated: <?php dabc_the_inventory_last_updated(); ?></p>
	<table>
		<thead>
			<tr>
				<th>Store</th>
				<th>Address</th>
				<th>Quantity</th>
			</tr>
		</thead>
		<tbody>
			<?php while ( $stores->have_posts() ) : ?>
				<?php $stores->the_post(); ?>
				<?php $store_post_id = get_the_ID(); ?>
			<tr>
				<td>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</td>
				<td>
					<?php dabc_the_store_address( $store_post_id ); ?>
				</td>
				<td><?php echo p2p_get_meta( get_post()->p2p_id, 'quantity', true ); ?></td>
			</tr>
			<?php endwhile; ?>
		</tbody>
	</table>
<?php endif; ?>
</section>