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
		<?php if (current_user_can('publish_pages')) : ?>
			<a href="<?php echo admin_url('post-new.php?post_type=page'); // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-page"></div>
							<span><?php esc_html_e('Pages', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Create New Page', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('New Page', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if (current_user_can('publish_posts')) : ?>
			<a href="<?php echo admin_url('post-new.php'); // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-text-page"></div>
							<span><?php esc_html_e('Articles', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Create New Article', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary"><?php esc_html_e('New Article', 'osdxp-dashboard'); ?></div>
					</div>
				</div>
			</a>
		<?php endif; ?>

		<?php if ((current_user_can('publish_pages') || current_user_can('publish_posts')) && class_exists('CFConditionalContent')) : // phpcs:ignore?>
			<a href="<?php echo admin_url('post-new.php?post_type=cf_cc_condition'); // phpcs:ignore?>" class="col">
				<div class="postbox">
					<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-post"></div>
							<span><?php esc_html_e('Conditional Content', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Add New Condition', 'osdxp-dashboard'); ?></p>
						</div>
						<div class="button button-primary">
							<?php esc_html_e('New Condition', 'osdxp-dashboard'); ?>
						</div>
					</div>
				</div>
			</a>
		<?php endif; ?>
	</div>

	<h2 class="title"><?php esc_html_e('Manage Functionality', 'osdxp-dashboard'); ?></h2>
	<div class="row">
		<?php if (current_user_can('edit_pages')) : ?>
			<a href="<?php echo admin_url('edit.php?post_type=page');  // phpcs:ignore?>" class="col">
		 		<div class="postbox">
		 			<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-page"></div>
							<span><?php esc_html_e('Pages', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Pages', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
		 		</div>
		 	</a>
		<?php endif; ?>

		<?php if (current_user_can('edit_posts')) : ?>
			<a href="<?php echo admin_url('edit.php');  // phpcs:ignore?>" class="col">
		 		<div class="postbox">
		 			<div>
						<div class="group">
							<div class="dashicons-before dashicons-text-page"></div>
							<span><?php esc_html_e('Articles', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Articles', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
		 		</div>
		 	</a>
		<?php endif; ?>

		<?php if ((current_user_can('publish_pages') || current_user_can('publish_posts')) && class_exists('CFConditionalContent')) :  // phpcs:ignore?>
			<a href="<?php echo admin_url('admin.php?page=cf-conditional-content-settings');  // phpcs:ignore?>" class="col">
		 		<div class="postbox">
		 			<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-post"></div>
							<span><?php esc_html_e('Conditional Content', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Conditions', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
		 		</div>
		 	</a>
		<?php endif; ?>

		<?php if (current_user_can('upload_files')) : ?>
			<a href="<?php echo admin_url('upload.php');  // phpcs:ignore?>" class="col">
		 		<div class="postbox">
		 			<div>
						<div class="group">
							<div class="dashicons-before dashicons-admin-media"></div>
							<span><?php esc_html_e('Media', 'osdxp-dashboard'); ?></span>
							<p><?php esc_html_e('Manage Media', 'osdxp-dashboard'); ?></p>
						</div>
					</div>
		 		</div>
		 	</a>
		<?php endif; ?>
	</div>
 </div>
