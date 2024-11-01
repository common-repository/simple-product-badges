<?php
namespace SimpleProductBadges\Includes;

class Loader
{
	protected $plugin_name;
	protected $plugin_version;

	public function __construct()
	{
		$this->plugin_version = defined('SIMPLEPRODUCTBADGES_VERSION') ? SIMPLEPRODUCTBADGES_VERSION : '1.0.0';
		$this->plugin_name = 'simple-product-badges';
		$this->load_dependencies();
		pb_style("simple-product-badges", SIMPLEPRODUCTBADGES_URL . "dist/app.css");
		add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);
	}

	private function load_dependencies()
	{
		foreach (glob(SIMPLEPRODUCTBADGES_PATH . 'Functionality/*.php') as $filename) {
			$class_name = '\\SimpleProductBadges\Functionality\\'. basename($filename, '.php');
			if (class_exists($class_name)) {
				try {
					new $class_name($this->plugin_name, $this->plugin_version);
				}
				catch (\Throwable $e) {
					pb_log($e);
					continue;
				}
			}
		}
	}

	public function load_plugin_textdomain()
	{
		load_plugin_textdomain('simple-product-badges', false, SIMPLEPRODUCTBADGES_BASENAME . '/languages/');
	}
}
