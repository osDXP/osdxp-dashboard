<?php
/**
 * Dashboard actions.
 *
 * @package osdxp-dashboard
 */

?>
 <div id="dxp-actions">

	<h2 class="title"><?php esc_html_e('Create Functionality', 'osdxp-dashboard'); ?></h2>
	<div class="row large">
		<?php if (current_user_can('create_sites')) : ?>
			<a href="<?php echo network_admin_url('site-new.php');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-home"></div>
							<span><?php esc_html_e('Sites', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Create New Site', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('New Site', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if (current_user_can('create_users')) : ?>
			<a href="<?php echo network_admin_url('user-new.php');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-users"></div>
							<span><?php esc_html_e('Users', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Create New User', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('Create User', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if (current_user_can('install_plugins')) : ?>
			<a href="<?php echo network_admin_url('plugin-install.php');  // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-generic"></div>
							<span><?php esc_html_e('Modules', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Add New Module', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('New Module', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>
	</div>

	<h2 class="title"><?php esc_html_e('Manage Functionality', 'osdxp-dashboard'); ?></h2>
	<div class="row">
		<?php if (current_user_can('create_users')) : ?>
			<a href="<?php echo network_admin_url('users.php');  // phpcs:ignore?>" class="col">
		 		<div class="postbox">
		 			<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-users"></div>
							<span><?php esc_html_e('Users', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Users', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
		 		</div>
		 	</a>
		<?php endif; ?>

		<?php if (current_user_can('create_sites')) : ?>
			<a href="<?php echo network_admin_url('sites.php');  // phpcs:ignore?>" class="col">
		 		<div class="postbox">
		 			<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-home"></div>
							<span><?php esc_html_e('Sites', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Sites', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
		 		</div>
		 	</a>
		<?php endif; ?>

		<?php if (current_user_can('install_plugins')) : ?>
			<a href="<?php echo network_admin_url('plugins.php');  // phpcs:ignore?>" class="col">
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
		<?php endif; ?>

		<?php
			if( is_plugin_active( 'multilingualpress/multilingualpress.php' ) )
			{
			    $language_manager = (array)get_network_option( 0, 'multilingualpress_modules', [] );
			    if( ! empty( $language_manager ) && ! empty( $language_manager['language-manager'] ) )
			    {
				    ?>
                        <a href="/wp-admin/network/admin.php?page=language-manager" class="col">
                            <div class="postbox">
                                <div>
                                    <div class="group">
                                        <div class="dashicons-before dashicons-admin-settings"></div>
                                        <span><?php esc_html_e( 'Settings', 'osdxp-dashboard' ); ?></span>
                                        <p><?php esc_html_e( 'Manage Languages', 'osdxp-dashboard' ); ?></p>
                                    </div>
                                </div>
                            </div>
                        </a>
				    <?php
			    }
			}
        ?>
	</div>
 </div>
