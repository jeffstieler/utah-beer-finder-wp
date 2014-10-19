<section>
<?php
$inventory = get_post_meta(get_the_ID(), 'dabc-store-inventory', true);

if ( $inventory['inventory'] ) :

	$store_numbers = array_keys( $inventory['inventory'] );

?>
	<h3>Store Availability</h3>
	<p>Last Updated: <?php echo $inventory['last_updated']; ?></p>
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
				<td><?php echo $inventory['inventory'][$store_number]; ?></td>
			</tr>
			<?php endwhile; ?>
		</tbody>
	</table>
<?php endif; ?>
</section>