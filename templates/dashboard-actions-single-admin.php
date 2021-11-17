<?php

/**
 * Dashboard actions.
 *
 * @package osdxp-dashboard
 */

$campaign = get_permalink(get_page_by_path('event-promo', OBJECT, 'campaigns'));
$campaign .= '?fl_builder';
?>
 <div id="dxp-actions">
	<h2 class="title"><?php esc_html_e('Quick Actions', 'osdxp-dashboard'); ?></h2>
	<div class="row quick-actions">
		<a href="#" class="col">
			<div class="quickbox">
				<div class="group group-left">
					<span><?php esc_html_e('Create new product', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('Product Landing Page: eCommerce', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="group group-right">
					<div class="dashicons-before dashicons-cart"></div>
				</div>
			</div>
		</a>
		<a href="#" class="col">
			<div class="quickbox quickbox-alt">
				<div class="group group-left">
					<span><?php esc_html_e('Create new event promotion', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('Event Promotion Landing Page', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="group group-right">
					<div class="dashicons-before dashicons-calendar"></div>
				</div>
			</div>
		</a>
		<a href="#" class="col">
			<div class="quickbox">
				<div class="group group-left">
					<span><?php esc_html_e('Create new webinar', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('Webinar: Including Sign-ups', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="group group-right">
					<div class="dashicons-before dashicons-tickets-alt"></div>
				</div>
			</div>
		</a>
		<a href="<?php echo esc_url($campaign); ?>" class="col">
			<div class="quickbox">
				<div class="group group-left">
					<span><?php esc_html_e('Create new landing page', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('Campaign Landing Page', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="group group-right">
					<div class="dashicons-before dashicons-text-page"></div>
				</div>
			</div>
		</a>
	</div>

	<h2 class="title"><?php esc_html_e('Manage Functionality', 'osdxp-dashboard'); ?></h2>
	<div class="row large">
		<a href="#cf-assistant-popup" class="col">
			<div class="postbox">
				<div class="group">
					<span><?php esc_html_e('AI Site Assistant', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('I\'m here to help!', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="dashicons-before dashicons-businesswoman"></div>
			</div>
		</a>
		<a href="<?php echo admin_url('admin.php?page=ab-testing');  // phpcs:ignore?>" class="col">
			<div class="postbox">
				<div class="group">
					<span><?php esc_html_e('A/B Testing', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('Manage A/B Tests', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="dashicons-before dashicons-chart-area"></div>
			</div>
		</a>

		<?php
		$custom_manage_functionality_modules = [];
		$custom_manage_functionality_modules = apply_filters(
			'osdxp_dashboard_single_manage_functionality',
			$custom_manage_functionality_modules
		);

		foreach ($custom_manage_functionality_modules as $custom_module) {
			?>
			<a href="<?php echo esc_url($custom_module['link']); ?>" class="col">
				<div class="postbox">
					<div class="group">
						<span><?php echo esc_html($custom_module['title']); ?></span>
						<p><?php echo esc_html($custom_module['subtitle']); ?></p>
					</div>
					<div class="dashicons-before <?php echo esc_attr($custom_module['icon']); ?>"></div>
				</div>
			</a>
			<?php
		}
		?>
	</div>

	<h2 class="title"><?php esc_html_e('Analytics', 'osdxp-dashboard'); ?></h2>
	<div class="row large analytics">
		<a href="#" class="col">
			<div class="postbox">
				<div class="group">
					<span><?php esc_html_e('Sessions', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('31,190', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="percent-change">
					<p class="percent uptick">↑ 5%</p>
					<p class="percent-subtitle">Vs. previous 30 days</p>
				</div>
			</div>
		</a>
		<a href="#" class="col">
			<div class="postbox">
				<div class="group">
					<span><?php esc_html_e('White Paper Downloads', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('715', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="percent-change">
					<p class="percent downtick">↓ 2%</p>
					<p class="percent-subtitle">Vs. previous 30 days</p>
				</div>
			</div>
		</a>
		<a href="#" class="col">
			<div class="postbox">
				<div class="group">
					<span><?php esc_html_e('Avg. Contract Value', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('$430,000', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="percent-change">
					<p class="percent uptick">↑ 4%</p>
					<p class="percent-subtitle">Vs. previous 30 days</p>
				</div>
			</div>
		</a>
		<a href="#" class="col">
			<div class="postbox">
				<div class="group">
					<span><?php esc_html_e('Avg. Sales Cycle Length', 'osdxp-dashboard'); ?></span>
					<p><?php esc_html_e('22.2 days', 'osdxp-dashboard'); ?></p>
				</div>
				<div class="percent-change">
					<p class="percent downtick">↓ 5%</p>
					<p class="percent-subtitle">Vs. previous 30 days</p>
				</div>
			</div>
		</a>
	</div>

 </div>
