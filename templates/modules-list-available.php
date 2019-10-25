<?php
namespace OSDXP_Dashboard;

if (isset($_GET["refresh"]) && $_GET['refresh']) { // phpcs:ignore
	delete_transient(OSDXP_DASHBOARD_AVAILABLE_MODULES_TRANSIENT);
    $jsonData = $this->getModulesData();
    $data =  $this->transformData($jsonData);
}
?>

<h1 class="available-modules-header">
	<?php esc_html_e('Available Modules', 'osdxp-dashboard');?>
	<a href="<?php echo network_admin_url('admin.php?page=dxp-modules&refresh=1'); // phpcs:ignore?>" class="button-secondary check-available-modules">
		<?php esc_html_e('Check for new modules', 'osdxp-dashboard');?>
	</a>
</h1>

<p><?php esc_html_e('Modules extend and expand the functionality of Open Source DXP', 'osdxp-dashboard');?></p>

<div class="am-grid-container">
	<?php
	if (!empty($data)) {
		foreach ($data as $key => $module_info) {?>
			<div class="am-col">
				<div class="am-grid-col-container">
					<div class="am-info am-grid-col-container">
						<div>
							<?php
							$valid_logo = !empty($module_info['logo']);
							$valid_logo = $valid_logo ? filter_var($module_info['logo'], FILTER_VALIDATE_URL) : false;
							$valid_logo = $valid_logo ? pathinfo($valid_logo, PATHINFO_EXTENSION) : false;
							$extensions = [
								'png',
								'svg',
								'jpeg',
								'jpg',
							];
							?>
							<?php if (in_array($valid_logo, $extensions)) : ?>
								<img src="<?php echo esc_url($module_info['logo']); ?>">
							<?php else : ?>
								<img src="<?php echo esc_url(OSDXP_DASHBOARD_PLACEHOLDER_IMAGE_URL); ?>">
							<?php endif; ?>
						</div>
						<div>
							<h3><?php echo esc_html($module_info['name']); ?></h3>
							<p><?php echo esc_html($module_info['description']); ?></p>
							<p><?php esc_html_e('By', 'osdxp-dashboard'); ?>: <?php echo esc_html($module_info['author']); // phpcs:ignore?></p>
						</div>
					</div>
					<div>
						<div class="am-pricing">
							<div>
								<span>
									<?php
									echo (empty($module_info['before-price-text'])) // phpcs:ignore
										? ''
										: esc_html($module_info['before-price-text']);
									?>
								</span>
							</div>
							<div class="price">
								<strong>
									<?php
									echo (empty($module_info['price'])) // phpcs:ignore
										? ''
										: esc_html($module_info['price']);
									?>
								</strong>
							</div>
							<div>
								<span>
									<?php
									echo (empty($module_info['after-price-text'])) // phpcs:ignore
										? ''
										: esc_html($module_info['after-price-text']);
									?>
								</span>
							</div>
							<div>
								<a
									class="get-module button-primary"
									target="_blank"
									href="<?php echo esc_url($module_info['url']); ?>"
								>
									<?php esc_html_e('Get Module', 'osdxp-dashboard');?>
								</a>
							</div>
						</div>
					</div>

				</div>
				<div class="am-module-footer">
					<p>
						<span class="certified">&#10003;</span>
						<strong><?php esc_html_e('Certified', 'osdxp-dashboard');?></strong>
						<?php esc_html_e(' and tested with your OSDXP version.', 'osdxp-dashboard');?>
					</p>
				</div>
			</div>
			<?php
		}
	}
	?>
</div>
