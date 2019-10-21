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
			<a href="<?php echo admin_url('users.php'); ?>" class="col">
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
			<a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="col">
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

		<?php if (current_user_can('install_plugins')) : ?>
			<a href="<?php echo admin_url('admin.php?page=dxp-modules-installed'); ?>" class="col">
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
	</div>

	<h2 class="title"><?php esc_html_e('Create Functionality', 'osdxp-dashboard'); ?></h2>
	<div class="row">
		<?php if (current_user_can('create_users')) : ?>
			<a href="<?php echo admin_url('user-new.php'); ?>" class="col">
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
			<a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="col">
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

		<?php if (current_user_can('install_plugins')) : ?>
			<a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="col">
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

		<?php /* TO DO:
			<a href="#" class="col">
		 		<div class="postbox">
		 			<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-site-alt3"></div>
							<span><?php esc_html_e('Settings', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Add New Language', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
		 		</div>
		 	</a>
		*/ ?>

		<?php if (current_user_can('edit_theme_options')) : ?>
			<a href="<?php echo admin_url('nav-menus.php?action=edit&menu=0'); ?>" class="col">
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
	</div>

 </div>
