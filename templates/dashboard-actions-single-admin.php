<?php

/**
 * Dashboard actions.
 *
 * @package osdxp-dashboard
 */

?>
 <div id="dxp-actions">
	<h2 class="title"><?php esc_html_e('Manage Functionality', 'osdxp-dashboard'); ?></h2>
	<div class="row large">
		<a href="#cf-assistant-popup" class="col">
			<div class="postbox">
				<div>
					<div class="group">
						<div class="dashicons-before dashicons-businesswoman"></div>
						<span><?php esc_html_e('AI Site Assistant', 'osdxp-dashboard'); ?></span>
						<p><?php esc_html_e('I\'m here to help!', 'osdxp-dashboard'); ?></p>
					</div>
				</div>
			</div>
		</a>
		<a href="<?php echo admin_url('admin.php?page=dxp-modules-installed');  // phpcs:ignore?>" class="col">
			<div class="postbox">
				<div>
					<div class="group">
						<div class="dashicons-before dashicons-admin-generic"></div>
						<span><?php esc_html_e('Modules', 'osdxp-dashboard'); ?></span>
						<p><?php esc_html_e('Manage Modules', 'osdxp-dashboard'); ?></p>
					</div>
				</div>
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
					<div>
						<div class="group">
							<div class="dashicons-before <?php echo esc_attr($custom_module['icon']); ?>"></div>
							<span><?php echo esc_html($custom_module['title']); ?></span>
							<p><?php echo esc_html($custom_module['subtitle']); ?></p>
						</div>
					</div>
				</div>
			</a>
			<?php
		}
		?>
	</div>

	<h2 class="title"><?php esc_html_e('Analytics', 'osdxp-dashboard'); ?></h2>
	<div class="row">
		<a href="<?php echo admin_url('user-new.php');  // phpcs:ignore?>" class="col">
			<div class="postbox">
				<div>
					<div class="group">
						<div class="dashicons-before dashicons-admin-users"></div>
						<span><?php esc_html_e('Users', 'osdxp-dashboard'); ?></span>
						<p><?php esc_html_e('Create New User', 'osdxp-dashboard'); ?></p>
					</div>
				</div>
			</div>
		</a>
	</div>

 </div>
