<section>
<?php

$beer_post_id = get_the_ID();

if ( $inventory = dabc_get_inventory( $beer_post_id ) ) :

	$store_numbers = array_keys( $inventory );

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
			<?php $stores = dabc_query_stores_by_number( $store_numbers ); ?>
			<?php while ( $stores->have_posts() ) : ?>
				<?php $stores->the_post(); ?>
				<?php $store_post_id = get_the_ID(); ?>
				<?php $store_number  = dabc_get_store_number( $store_post_id ); ?>
			<tr>
				<td>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</td>
				<td>
					<?php dabc_the_store_address( $store_post_id ); ?>
				</td>
				<td><?php dabc_the_quantity_for_store( $store_number, $beer_post_id ); ?></td>
			</tr>
			<?php endwhile; ?>
		</tbody>
	</table>
<?php endif; ?>
</section>