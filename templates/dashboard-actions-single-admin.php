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
		<?php if (current_user_can('create_users')) : ?>
			<a href="<?php echo admin_url('users.php');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-users"></div>
							<span><?php esc_html_e('Users', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Users', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('View Users', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if (current_user_can('edit_pages')) : ?>
			<a href="<?php echo admin_url('edit.php?post_type=page');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-page"></div>
							<span><?php esc_html_e('Pages', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Pages', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('View Pages', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if (current_user_can('activate_plugins')) : ?>
			<a href="<?php echo admin_url('admin.php?page=dxp-modules-installed');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-generic"></div>
							<span><?php esc_html_e('Modules', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Modules', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('View Modules', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>

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
						<div class="button button-primary">
							<?php echo esc_html($custom_module['button_text']); ?>
						</div>
					</div>
				</div>
			</a>
			<?php
		}
		?>
	</div>

	<h2 class="title"><?php esc_html_e('Create Functionality', 'osdxp-dashboard'); ?></h2>
	<div class="row">
		<?php if (current_user_can('create_users')) : ?>
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
		<?php endif; ?>

		<?php if (current_user_can('publish_pages')) : ?>
			<a href="<?php echo admin_url('post-new.php?post_type=page');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-page"></div>
							<span><?php esc_html_e('Pages', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Create New Page', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if (current_user_can('install_plugins') && !is_multisite()) : ?>
			<a href="<?php echo self_admin_url('?page=dxp-modules-installed');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-generic"></div>
							<span><?php esc_html_e('Modules', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Add New Module', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if (current_user_can('edit_theme_options')) : ?>
			<a href="<?php echo admin_url('nav-menus.php?action=edit&menu=0');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-menu-alt"></div>
							<span><?php esc_html_e('Menus', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Create New Menu', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php
		$custom_create_functionality_modules = [];
		$custom_create_functionality_modules = apply_filters(
			'osdxp_dashboard_single_create_functionality',
			$custom_create_functionality_modules
		);

		foreach ($custom_create_functionality_modules as $custom_module) {
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

 </div>
