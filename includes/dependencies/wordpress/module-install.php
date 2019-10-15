<?php
/**
 * Module Install Administration API
 *
 * @package osdxp-dashboard
 */


function plugins_api($action, $args = array())
{
	// include an unmodified $wp_version
	include(ABSPATH . WPINC . '/version.php');

	if (is_array($args)) {
		$args = (object) $args;
	}

	if ('query_plugins' == $action) {
		if (! isset($args->per_page)) {
			$args->per_page = 24;
		}
	}

	if (! isset($args->locale)) {
		$args->locale = get_user_locale();
	}

	if (! isset($args->wp_version)) {
		$args->wp_version = substr($wp_version, 0, 3); // X.y
	}

	/**
	 * Filters the WordPress.org Plugin Installation API arguments.
	 *
	 * Important: An object MUST be returned to this filter.
	 *
	 * @since 2.7.0
	 *
	 * @param object $args   Plugin API arguments.
	 * @param string $action The type of information being requested from the Plugin Installation API.
	 */
	$args = apply_filters('plugins_api_args', $args, $action);

	/**
	 * Filters the response for the current WordPress.org Plugin Installation API request.
	 *
	 * Passing a non-false value will effectively short-circuit the WordPress.org API request.
	 *
	 * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
	 * If `$action` is 'hot_tags' or 'hot_categories', an array should be passed.
	 *
	 * @since 2.7.0
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Installation API.
	 * @param object             $args   Plugin API arguments.
	 */
	$res = apply_filters('plugins_api', false, $action, $args);

	if (false === $res) {
		$url = 'http://api.wordpress.org/plugins/info/1.2/';
		$url = add_query_arg(
			array(
				'action'  => $action,
				'request' => $args,
			),
			$url
		);

		$http_url = $url;
		if ($ssl = wp_http_supports(array( 'ssl' ))) {
			$url = set_url_scheme($url, 'https');
		}

		$http_args = array(
			'timeout'    => 15,
			'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url('/'),
		);
		$request   = wp_remote_get($url, $http_args);

		if ($ssl && is_wp_error($request)) {
			trigger_error(
				sprintf(
					/* translators: %s: support forums URL */
					__('An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.'),
					__('https://wordpress.org/support/')
				) . ' ' . __('(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)'),
				headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
			);
			$request = wp_remote_get($http_url, $http_args);
		}

		if (is_wp_error($request)) {
			$res = new WP_Error(
				'plugins_api_failed',
				sprintf(
					/* translators: %s: support forums URL */
					__('An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.'),
					__('https://wordpress.org/support/')
				),
				$request->get_error_message()
			);
		} else {
			$res = json_decode(wp_remote_retrieve_body($request), true);
			if (is_array($res)) {
				// Object casting is required in order to match the info/1.0 format.
				$res = (object) $res;
			} elseif (null === $res) {
				$res = new WP_Error(
					'plugins_api_failed',
					sprintf(
						/* translators: %s: support forums URL */
						__('An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.'),
						__('https://wordpress.org/support/')
					),
					wp_remote_retrieve_body($request)
				);
			}

			if (isset($res->error)) {
				$res = new WP_Error('plugins_api_failed', $res->error);
			}
		}
	} elseif (! is_wp_error($res)) {
		$res->external = true;
	}

	/**
	 * Filters the Plugin Installation API response results.
	 *
	 * @since 2.7.0
	 *
	 * @param object|WP_Error $res    Response object or WP_Error.
	 * @param string          $action The type of information being requested from the Plugin Installation API.
	 * @param object          $args   Plugin API arguments.
	 */
	return apply_filters('plugins_api_result', $res, $action, $args);
}

/**
 * Determine the status we can perform on a plugin.
 *
 * @since 3.0.0
 *
 * @param  array|object $api  Data about the plugin retrieved from the API.
 * @param  bool         $loop Optional. Disable further loops. Default false.
 * @return array {
 *     Plugin installation status data.
 *
 *     @type string $status  Status of a plugin.
 *     Could be one of 'install', 'update_available', 'latest_installed' or 'newer_installed'.
 *     @type string $url     Plugin installation URL.
 *     @type string $version The most recent version of the plugin.
 *     @type string $file    Plugin filename relative to the plugins directory.
 * }
 */
function install_plugin_install_status($api, $loop = false)
{
	// This function is called recursively, $loop prevents further loops.
	if (is_array($api)) {
		$api = (object) $api;
	}

	// Default to a "new" plugin
	$status      = 'install';
	$url         = false;
	$update_file = false;
	$version     = '';

	/*
	 * Check to see if this plugin is known to be installed,
	 * and has an update awaiting it.
	 */
	$update_plugins = get_site_transient('update_plugins');
	if (isset($update_plugins->response)) {
		foreach ((array) $update_plugins->response as $file => $plugin) {
			if ($plugin->slug === $api->slug) {
				$status      = 'update_available';
				$update_file = $file;
				$version     = $plugin->new_version;
				if (current_user_can('update_plugins')) {
					$url = wp_nonce_url(
						self_admin_url(
							'admin.php?page=dxp-modules-update&action=upgrade-module&module=' . $update_file
						),
						'upgrade-module_' . $update_file
					);
				}
				break;
			}
		}
	}
	if (isset($_GET['from'])) {
		$url .= '&amp;from=' . urlencode(wp_unslash($_GET['from']));
	}

	$file = $update_file;
	return compact('status', 'url', 'version', 'file');
}
