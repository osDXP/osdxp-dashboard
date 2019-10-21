<?php
/**
 * File containing dashboard functionality.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
add_action('wp_dashboard_setup', __NAMESPACE__ . '\\add_dxp_news_dashboard_widget');
add_action('wp_dashboard_setup', __NAMESPACE__ . '\\remove_wp_events_and_news_dashboard_widget');
add_action('wp_network_dashboard_setup', __NAMESPACE__ . '\\add_dxp_news_dashboard_widget');
add_action('wp_network_dashboard_setup', __NAMESPACE__ . '\\remove_wp_events_and_news_dashboard_widget');

add_action('wp_dashboard_setup', __NAMESPACE__ . '\\add_dxp_dashboard_actions_widget');
add_action('wp_network_dashboard_setup', __NAMESPACE__ . '\\add_dxp_dashboard_actions_widget');
// phpcs:enable

/**
 * Method to add the DXP Actions Dashboard widget.
 *
 * @return void
 */
function add_dxp_dashboard_actions_widget()
{
	if (is_dxp_dashboard()) {
		wp_add_dashboard_widget(
			'dxp_actions',
			esc_html__('DXP Actions', 'osdxp-dashboard'),
			__NAMESPACE__ . '\\dxp_dashboard_actions'
		);
	}
}

/**
 * Method to add the DXP News Dashboard widget.
 *
 * @return void
 */
function add_dxp_news_dashboard_widget()
{
	if (is_dxp_dashboard()) {
		wp_add_dashboard_widget(
			'dxp_news',
			esc_html__('DXP News', 'osdxp-dashboard'),
			__NAMESPACE__ . '\\dxp_news_dashboard_widget'
		);
	}
}

/**
 * Method to add dxp dashboard actions.
 *
 * @return void
 */
function dxp_dashboard_actions() {
	if (is_network_admin() && is_super_admin()) {
		require_once(OSDXP_DASHBOARD_DIR . '/templates/dashboard-actions-network-admin.php');
	}

	elseif (is_multisite() && is_super_admin()) {
		require_once(OSDXP_DASHBOARD_DIR . '/templates/dashboard-actions-multisite-admin.php');
	}

	elseif (!is_multisite() && current_user_can('administrator')) {
		require_once(OSDXP_DASHBOARD_DIR . '/templates/dashboard-actions-single-admin.php');
	}

	elseif (!current_user_can('administrator') && current_user_can('editor')) {
		require_once(OSDXP_DASHBOARD_DIR . '/templates/dashboard-actions-editor.php');
	}
}

/**
 * Method to render the DXP News dashboard widget.
 *
 * @return void
 */
function dxp_news_dashboard_widget()
{
	$news_items = get_dxp_news_items();

	if ($news_items) {
		echo '<ul>';

		foreach ($news_items as $item) :
			$title = sprintf(
				// Translators: %s the date when the item was posted.
				esc_html__('Posted %s', 'osdxp-dashboard'),
				$item->get_date('j F Y | g:i a')
			);
			?>
			<li>
				<a href="<?php echo esc_url($item->get_permalink()); ?>"
					title="<?php echo esc_html($title); ?>">
					<?php echo esc_html($item->get_title()); ?>
				</a>
			</li>
			<?php
		endforeach;

		echo '</ul>';
	} else {
		esc_html_e('There are no DXP news at the moment.', 'osdxp-dashboard');
	}
}

/**
 * Method to get DXP News items.
 *
 * @return array|bool|null
 */
function get_dxp_news_items()
{
	// Get RSS Feed(s)
	require_once ABSPATH . WPINC . '/feed.php';

	// Get a SimplePie feed object from the specified feed source.
	$rss = fetch_feed(OSDXP_DASHBOARD_NEWS_RSS_URL);

	if (is_wp_error($rss)) {
		return false;
	}

	// Figure out how many total items there are, but limit it to the max items count.
	$max_items_count = $rss->get_item_quantity(OSDXP_DASHBOARD_NEWS_RSS_MAX_ITEMS_COUNT);

	// Build an array of all the items, starting with element 0 (first element).
	return $rss->get_items(0, $max_items_count);
}

/**
 * Method to remove the WP Events and News dashboard widget.
 *
 * @return void
 */
function remove_wp_events_and_news_dashboard_widget()
{
	if (is_dxp_dashboard()) {
		global $wp_meta_boxes;

		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
		unset($wp_meta_boxes['dashboard-network']['side']['core']['dashboard_primary']);
	}
}
