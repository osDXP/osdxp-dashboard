<?php

/**
 * File containing the notification hiding logic.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
if (! is_admin()) {
	return;
}
// phpcs:enable

// Output grouping notification and load required js
function notifications()
{

	if (is_network_admin()) {
		$action = 'network_admin_notices';
	} elseif (is_user_admin()) {
		$action = 'user_admin_notices';
	} else {
		$action = 'admin_notices';
	}
	add_action($action, function () {

		ob_start();
	}, (int) ( PHP_INT_MAX + 1 ));

	add_action('all_admin_notices', function () {

		$contents = trim(ob_get_clean());
		if ('' === $contents) {
			return;
		}

		$notice_id = 'osdxp-notifications';

		$drawer_id = 'osdxp-drawer';
		?>
		<div id="<?php echo esc_attr($notice_id); ?>" class="notice hide-if-js">
			<p>
				<?php esc_html_e('Administrative Notices have been minimized.', 'osdxp-dashboard'); ?>
				<button class="button"><?php esc_html_e('Reveal', 'osdxp-dashboard'); ?></button>
			</p>
		</div>
		<div id="<?php echo esc_attr($drawer_id); ?>" class="hide-if-js">
			<?php echo $contents; // phpcs:ignore?>
		</div>
		<?php
		wp_enqueue_script(
			'osdxp-notifications',
			OSDXP_DASHBOARD_URL . 'build/notifications.js',
			[ 'jquery' ],
			filemtime(OSDXP_DASHBOARD_DIR . 'build/notifications.js')
		);

		/**
		 * Filters the minimum number of admin notices required to take action.
		 *
		 * @param int $threshold Required minimum number of admin notices.
		 */
		$threshold = (int) apply_filters('osdxp_notifications_threshold', 1);
		wp_localize_script('osdxp-notifications', 'osdxpNotificationsSettings', [
			'selectorDrawer' => "#{$drawer_id}",
			'selectorNotice'  => "#{$notice_id}",
			'threshold'      => max(1, $threshold),
		]);
	}, PHP_INT_MAX);
}
// phpcs:disable
add_action('plugins_loaded', __NAMESPACE__ . '\\notifications');
// phpcs:enable
