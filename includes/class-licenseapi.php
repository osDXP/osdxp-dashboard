<?php

/**
 * API integration with license server.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

use WP_Error;

class LicenseAPI
{
	/**
	 * The User-Agent string that will be passed with API requests.
	 *
	 * @var string
	 */
	private const USER_AGENT = 'OSDXP-DASHBOARD';

	/**
	 * The user's API key.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * A roll-up of any and all API errors that have occurred.
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Plugin slug.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * Prevent the object from being cloned.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Construct a new instance of the LicenseAPI.
	 *
	 * @access private
	 *
	 * @param string $plugin_slug Plugin slug.
	 */
	public function __construct($plugin_slug)
	{
		$this->plugin_slug = $plugin_slug;
	}

	/**
	 * Prevent the object from being deserialized.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function __wakeup()
	{
	}

	/**
	 * Build an API request URI.
	 *
	 * @access protected
	 *
	 * @param string $path Optional. The API endpoint. Default is '/'.
	 * @param array $args Optional. Query string arguments for the URI. Default is empty.
	 *
	 * @return string The URI for the API request.
	 */
	protected function buildURI($path = '/', array $args = [])
	{
		// Ensure the $path has a leading slash.
		if ('/' !== substr($path, 0, 1)) {
			$path = '/' . $path;
		}

		return add_query_arg($args, OSDXP_DASHBOARD_API_URL . $path);
	}

	/**
	 * Clear the current value for $this->api_key.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function clearAPIKey()
	{
		$this->api_key = null;
	}

	/**
	 * Given a URI and arguments, generate a cache key for use with CF's internal caching system.
	 *
	 * @param string $uri The API URI, with any query string arguments.
	 * @param array $args Optional. An array of HTTP arguments used in the request. Default is empty.
	 *
	 * @return string A cache key.
	 */
	public static function generateCacheKey($uri, $args = [])
	{
		return 'osdxp_dashboard_' . substr(md5($uri . wp_json_encode($args)), 0, 12);
	}

	/**
	 * Retrieve an *uncached* response from the /account endpoint.
	 *
	 * @access public
	 *
	 * @return array|WP_Error An array of all account attributes or a WP_Error object on error.
	 */
	public function getAccount()
	{
		$response = $this->sendRequest('GET', '/account');

		return $response;
	}

	/**
	 * Retrieve the API key.
	 *
	 * @access public
	 *
	 * @return string The API key.
	 */
	public function getAPIKey()
	{
		if ($this->api_key) {
			return $this->api_key;
		}

		$this->api_key = get_license_key($this->plugin_slug);

		return $this->api_key;
	}

	/**
	 * Retrieve any API errors that have occurred.
	 *
	 * @access public
	 *
	 * @return array An array of WP_Error objects.
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Method to initialize the update checker.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function initUpdateChecker()
	{
		/**
		 * Filters the plugin file.
		 *
		 * @param null|string $plugin_slug Plugin slug.
		 */
		$plugin_file = apply_filters('osdxp_dashboard_plugin_file_' . $this->plugin_slug, null);

		/**
		 * Filters the plugin update metadata URL.
		 *
		 * @param null|string $plugin_slug Plugin slug.
		 */
		$metadata_url = apply_filters(
			'osdxp_dashboard_plugin_update_metadata_url_' . $this->plugin_slug,
			OSDXP_DASHBOARD_UPDATE_API_URL
		);

		// Skip this plugin if no plugin file was specified or if no license key was found.
		if (!$plugin_file || null === $this->getAPIKey()) {
			return;
		}
		// Initialize checker.
		$updater = new OsdxpModuleUpdateChecker(
			$metadata_url,
			$plugin_file,
			$this->plugin_slug
		);

		$updater->addHttpRequestArgFilter([$this, 'updateCheckerHttpRequestArgFilter']);

		$updater->addResultFilter([$this, 'updateCheckerResultFilter']);
	}

	/**
	 * Send a request to the license server.
	 *
	 * @access protected
	 *
	 * @param string $method The HTTP method.
	 * @param string $path The API request path.
	 * @param array $query Optional. Query string arguments. Default is empty.
	 * @param array $args Optional. Additional HTTP arguments. For a full list of options,
	 *                    see wp_remote_request().
	 * @param int $cache Optional. The number of seconds for which the result should be cached.
	 *                   Default is 0 seconds (no caching).
	 *
	 * @return array|WP_Error The HTTP response body or a WP_Error object if something went wrong.
	 */
	protected function sendRequest($method, $path, $query = [], $args = [], $cache = 0)
	{
		$api_key = $this->getAPIKey();

		if (empty($api_key)) {
			return new WP_Error(
				'osdxp-dashboard-no-api-key',
				esc_html__('No API key has been set, unable to make request.', 'osdxp-dashboard')
			);
		}

		$uri = $this->buildURI($path, $query);
		$args = wp_parse_args(
			$args,
			[
				'timeout'    => 30,
				'user-agent' => self::USER_AGENT,
				'headers'    => [
					'Authorization'    => 'Bearer ' . $api_key,
					'Method'           => $method,
					'Slug'             => $this->plugin_slug,
					'X-Forwarded-Host' => site_url(),
				],
			]
		);

		$cache_key = self::generateCacheKey($uri, $args);
		$cached = get_transient($cache_key);

		// Return the cached version, if we have it.
		if ($cache && $cached) {
			return $cached;
		}

		$response = wp_remote_request($uri, $args);

		if (is_wp_error($response)) {
			return $response;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if ('fail' === $body['status']) {
			return new WP_Error(
				'osdxp-dashboard-api',
				/* Translators: %1$s is the first error message from the API response. */
				sprintf(
					esc_html__('The license API request failed: %1$s', 'osdxp-dashboard'),
					current((array)$body['data'])
				),
				$body['data']
			);
		}

		// Cache the result.
		if ($cache) {
			set_transient($cache_key, $body['data'], $cache);
		}

		return $body['data'];
	}

	/**
	 * Explicitly set the API key.
	 *
	 * @access public
	 *
	 * @param string $key The API key to use.
	 *
	 * @return void
	 */
	public function setAPIKey($key)
	{
		$this->api_key = $key;
	}

	/**
	 * Callback method for the \OSDXP_Dashboard\OsdxpModuleUpdateChecker::addHttpRequestArgFilter() method.
	 *
	 * @access public
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function updateCheckerHttpRequestArgFilter($options)
	{
		$api_key = $this->getAPIKey();

		if (!is_array($options)) {
			$options = [];
		}

		if (empty($options['headers']) || ! is_array($options['headers'])) {
			$options['headers'] = [];
		}

		$options['headers'] = array_merge(
			$options['headers'],
			[
				'Authorization'    => $api_key,
				'Slug'             => $this->plugin_slug,
				'X-Forwarded-Host' => site_url(),
			]
		);

		return $options;
	}

	/**
	 * Callback method for the \OSDXP_Dashboard\OsdxpModuleUpdateChecker::addResultFilter().
	 * method.
	 *
	 * @access public
	 *
	 * @param mixed $plugin_info Plugin info.
	 * @param array $result Result.
	 *
	 * @return mixed
	 */
	public function updateCheckerResultFilter($plugin_info, $result)
	{
		$code = empty($result['response']['code']) ? 0 : (int)$result['response']['code'];

		if (!$code || $code < 200 || $code > 299) {
			$body = empty($result['body']) ? null : json_decode($result['body']);

			if (!empty($body->authorization)) {
				set_site_transient(get_license_error_transient_name($this->plugin_slug), $body->authorization);
			}
		} else {
			delete_site_transient(get_license_error_transient_name($this->plugin_slug));
		}

		return $plugin_info;
	}
}
