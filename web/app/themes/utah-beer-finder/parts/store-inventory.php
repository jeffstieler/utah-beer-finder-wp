<?php

$store_post_id = get_the_ID();
$store_number  = dabc_get_store_number( $store_post_id );
$store_beers   = dabc_get_store_beers( $store_post_id );

?>
<table>
	<thead>
		<tr>
			<th>Quantity</th>
			<th>Beer</th>
			<th>Overall Rating</th>
			<th>Style Rating</th>
			<th>ABV</th>
		</tr>
	</thead>
<?php while ( $store_beers->have_posts() ) : ?>
	<?php $store_beers->the_post(); ?>
	<?php if ( $quantity = p2p_get_meta( get_post()->p2p_id, 'quantity', true ) ) : ?>
	<tr>
		<td><?php echo $quantity; ?></td>
		<td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
		<td><?php dabc_the_overall_rating(); ?></td>
		<td><?php dabc_the_style_rating(); ?></td>
		<td><?php dabc_the_abv(); ?></td>
	</tr>
	<?php endif; ?>
<?php endwhile; ?>
</table>