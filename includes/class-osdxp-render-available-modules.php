<?php
/**
 * This class provides functionality to obtain and display the openDXP
 * available modules.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

class OSDXPAvailableModules
{
	protected function remoteGetFailedNotice($message)
	{
		 $message = esc_attr($message);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
	}


	/**
	 * Fetch the remote JSON data from the license server
	 *
	 * @param  string $url URL to the JSON data for the modules
	 *
	 * @return string      JSON data that contains the avaialble modules
	 */
	protected function getModulesRemoteModulesData(string $url = OSDXP_DASHBOARD_AVAILABLE_MODULES_LIST_URL)
	{
		// account for local-only modules
		if (false === $url) {
			return false;
		}

		$response = wp_remote_get($url);
		if (is_wp_error($response)) {
			add_action('admin_notices', function () use ($response) {
				printf(
					'<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>',
					esc_attr('Failed to retrieve available modules: ' . $response->get_error_message())
				);
			});
			return false;
		}
		$data = wp_remote_retrieve_body($response);
		// check to make sure $response is valid JSON data
		$validate = $this->validateModulesJSON($data);
		if (!$validate) {
			// Not valid JSON
			return false;
		}

		return  $data;
	}

	/**
	 * Set the WP Transient with the available modules JSON
	 *
	 * @param string $json          JSON data to be saved in the transient
	 * @param string $transientName name of the transient
	 * @param int    $expireTime    number of seconds to expiration
	 *
	 * @return boolean  Success of the storing of the transient
	 */
	protected function setModulesDataTransient(string $json)
	{
		$result = set_transient(
			OSDXP_DASHBOARD_AVAILABLE_MODULES_TRANSIENT,
			$json,
			OSDXP_DASHBOARD_AVAILABLE_MODULES_TRANSIENT_EXPIRE
		);

		return $result;
	}

	/**
	 * Transform the JSON data of available modules
	 *
	 * Convert JSON data to a PHP array
	 * @todo: is this somehting that needs functionaluty for? "Wherever possible, t
	 * he word WordPress is removed and replaced with DXP
	 * in the module name"
	 *
	 * @access public
	 *
	 * @param  string $json JSON string to be transformed
	 *
	 * @return array $data array that contains the transformed data
	 */
	public function transformData(string $json)
	{
		$data = json_decode($json, true);
		$partner_data = [];
		$partner_data = apply_filters('osdxp_get_available_modules', $partner_data);
		foreach ($partner_data as $key => $partner) {
			$data['data'][$key] = $partner;
		}
		return array_change_key_case_recursive($data['data']);
	}

	/**
	 * Wrapper method to get the JSON data for available modules.
	 * 1st attempt to get data from transient
	 * 2nd attempt to get data from remote
	 * 3rd and final, get data from a static file. This file should be the most
	 *      up to date of the listing if possible. If should be a rare case that
	 *      the file would ever get used
	 *
	 * @return array the JSON data of available modules transformed to an array
	 */
	public function getModulesData()
	{
		$jsonData = get_transient(OSDXP_DASHBOARD_AVAILABLE_MODULES_TRANSIENT);
		//Transient doesn't exist, let's go fetch the info and reset the transient
		if (!$jsonData) {
			$jsonData = $this->getModulesRemoteModulesData();

			// if for some reason, the remote call fails, fallback to default data file.
			if (!$jsonData) {
				$jsonData  = file_get_contents(OSDXP_DASHBOARD_DIR . '/default-available-modules.json');
			}

			// Set this data to the transient after either retreiving remotely or default
			$this->setModulesDataTransient($jsonData);
		}

		return $jsonData;
	}

	/**
	 * Make sure the the data contained in the JSON matches the expected values
	 *
	 * @param  string $json [description]
	 *
	 * @return boolean       true if valid false if not
	 */
	protected function validateModulesJSON(string $json)
	{
		$results = json_decode($json, true);

		// If it is not valid JOSN, return false
		if (!$results) {
			// Not valid JSON
			return false;
		}
		if (!isset($results['data'])) {
			// No proper data
			return false;
		}
		$results = $results['data'];
		// This needs to match the properties in the JSON file
		$expectedModuleKeys = [
			'logo',
			'name',
			'description',
			'url',
			'author',
			'price',
			'before-price-text',
			'after-price-text'
		];
		$ksort_expected_module_keys = ksort($expectedModuleKeys);
		// Check if the proper properties exist in the data.
		foreach ($results as $moduleData) {
			$ksort_module_data_keys = array_keys($moduleData);
			$ksort_module_data_keys = ksort($ksort_module_data_keys);
			if (!($ksort_expected_module_keys == $ksort_module_data_keys)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Outupt 'page' for the available modules. Template is in a separate file
	 *
	 * @return void
	 */
	public function showPage()
	{
		$jsonData = $this->getModulesData();
		$data =  $this->transformData($jsonData);
		include OSDXP_DASHBOARD_DIR . '/templates/modules-list-available.php';
	}
}
