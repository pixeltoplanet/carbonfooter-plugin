<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="carbonfooter-dashboard-widget">
	<div class="carbonfooter-dashboard-stats">
		<div class="carbonfooter-dashboard-stat">
			<span class="stat-label"><?php echo esc_html__( 'Average Emissions', 'carbonfooter' ); ?></span>
			<span class="stat-value"><?php echo esc_html( number_format( $average_emissions, 2 ) ); ?>g CO2</span>
		</div>

		<div class="carbonfooter-dashboard-stat">
			<span class="stat-label"><?php echo esc_html__( 'Pages Measured', 'carbonfooter' ); ?></span>
			<span class="stat-value"><?php echo esc_html( $total_measured ); ?></span>
		</div>

		<?php if ( $is_green_host ) : ?>
			<div class="carbonfooter-dashboard-stat">
				<span class="stat-label"><?php echo esc_html__( 'Green Hosting', 'carbonfooter' ); ?></span>
				<span class="stat-value green">✓</span>
			</div>
		<?php else : ?>
			<div class="carbonfooter-dashboard-stat">
				<span class="stat-label"><?php echo esc_html__( 'Non-Green Hosting', 'carbonfooter' ); ?></span>
				<span class="stat-value red">✗</span>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $high_emission_pages ) : ?>
		<div class="carbonfooter-dashboard-pages">
			<h4><?php echo esc_html__( 'Top 10 Highest Emission Pages', 'carbonfooter' ); ?></h4>
			<ul>
				<?php foreach ( $high_emission_pages as $page_item ) : ?>
					<li>
						<a href="<?php echo esc_url( get_edit_post_link( $page_item->ID ) ); ?>">
							<?php echo esc_html( $page_item->post_title ); ?>
						</a>
						<span class="emissions"><?php echo esc_html( number_format( $page_item->emissions, 2 ) ); ?>g CO2</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<p class="carbonfooter-dashboard-links">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=carbonfooter-settings' ) ); ?>" class="button button-small">
			<?php echo esc_html__( 'View Details', 'carbonfooter' ); ?>
		</a>
	</p>
</div>