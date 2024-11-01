<?php

namespace SimpleProductBadges\Functionality;

class WoocommerceActions
{

	protected $plugin_name;
	protected $plugin_version;

	public function __construct($plugin_name, $plugin_version)
	{
		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

		add_action('woocommerce_shop_loop_item_title',  [$this, 'show_custom_bagde'], 3);
		add_filter('woocommerce_sale_flash', [$this, 'lw_hide_sale_flash'], 3);
	}

	function lw_hide_sale_flash()
	{
		return false;
	}

	public function show_custom_bagde()
	{
		//Si es -1 Sale! si no Custom!
		global $product;
		$badge_id = -1;

		//Si en un producto esta descativado
		$single_disabled = carbon_get_post_meta($product->get_id(), 's_badge_disabled_all');
		if ($single_disabled) {
			return false;
		}

		$single_only = carbon_get_post_meta($product->get_id(), 's_badge_single_active');
		if ($single_only) {
			$badge_id = $product->get_id();
		} else {
			$badge_id = $this->get_badge_id();
		}

		if ($badge_id === -1) { //Producto sin badge asociado.
			if ($product->is_on_sale()) {
				echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-right-0 pb-top-0">Sale!</span>';
			}
		} else {
			$badge_type = carbon_get_post_meta($badge_id, 'badge_type');
			switch ($badge_type) {
				case "type_custom":
					$this->badge_type_custom($badge_id, $product);
					break;
				case "type_new":
					$this->badge_type_new($badge_id, $product);
					break;
				case "type_lowstock":
					$this->badge_type_lowinstock($badge_id, $product);
					break;
				case "type_sale":
					$this->badge_type_sale($badge_id, $product);
					break;
			}
		}
	}

	function get_badge_id()
	{
		global $product;
		$product_tags = $product->get_tag_ids();
		$product_categories = $product->get_category_ids();

		$query_id = new \WP_Query(array(
			'post_type'			=> 'badges',
			'post_status'		=> 'publish',
			'posts_per_page' => 1,
			'orderby' => 'text_field',
			'order' => 'asc',
			'meta_query' => array(
				'text_field' => array(
					'key' => 'badge_priority',
					'compare' => 'EXISTS',
				),
				array(
					'relation' => 'OR',
					array(
						'key' => 'apply_tags',
						'value' => $product_tags,
						'compare' => 'IN',
					),
					array(
						'key' => 'apply_categories',
						'value' => $product_categories,
						'compare' => 'IN',
					),
					array(
						'key' => 'badge_apply',
						'value' => 'apply_all',
						'compare' => 'EXISTS',
					),
				),

			),
		));

		if (!empty($query_id->posts)) {
			$id = $query_id->posts[0]->ID;
			wp_reset_postdata();
			return $id;
		}
		wp_reset_postdata();
		return -1;
	}

	function badge_type_new($badge_id, $product)
	{
		$new_arrival_interval = carbon_get_post_meta($badge_id, 'badge_new_days');
		$created = strtotime($product->get_date_created());

		if ((time() - (60 * 60 * 24 * $new_arrival_interval)) < $created) {
			$name = carbon_get_post_meta($badge_id, 'badge_text');
			$text_size = carbon_get_post_meta($badge_id, 'badge_text_size') ? carbon_get_post_meta($badge_id, 'badge_text_size') : '12';
			$text_color = carbon_get_post_meta($badge_id, 'badge_text_color') ? carbon_get_post_meta($badge_id, 'badge_text_color') : '#000000';
			$bg_color = carbon_get_post_meta($badge_id, 'badge_bg_color') ? carbon_get_post_meta($badge_id, 'badge_bg_color') : 'transparent';
			$position = carbon_get_post_meta($badge_id, 'badge_position') === 'position_tr' ? 'right' : 'left';

			echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-' . $position . '-0 pb-top-0" 
			style="font-size: ' . $text_size . 'px; color: ' . $text_color . '; background-color: ' . $bg_color . ' ">' . $name . '</span>';
		} else if ($product->is_on_sale()) {
			echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-right-0 pb-top-0">Sale!</span>';
		}
	}

	function badge_type_lowinstock($badge_id, $product)
	{
		$variations = $product->get_children();
		$low_stock = false;
		$have_variations = count($variations) > 0 ? true : false;
		$low_stock_cuantity = carbon_get_post_meta($badge_id, 'badge_lowstock_low');
		$medium_stock_cuantity = carbon_get_post_meta($badge_id, 'badge_lowstock_medium');


		if ($have_variations) { //Variations
			$product_stock = '';
			foreach ($variations as $variation) {
				$product_child = wc_get_product($variation);
				$stock = $product_child->get_stock_quantity();

				if ($stock <= $low_stock_cuantity) $low_stock = true;

				if ($stock > $medium_stock_cuantity) {
					$product_stock .= "<span class='pb-text-green-500 pb-text-[12px]'>" . $product_child->get_name() . ", Stock: " .  $stock . "</span><br>";
				} else if ($stock >= $low_stock_cuantity && $stock <= $medium_stock_cuantity) {
					$product_stock .= "<span class='pb-text-yellow-500 pb-text-[12px]'>" . $product_child->get_name() . ", Stock: " .  $stock . "</span><br>";
				} else if ($stock <= $low_stock_cuantity && $stock >= 0) {
					$product_stock .= "<span class='pb-text-red-500 pb-text-[12px]'>" . $product_child->get_name() . ", Stock: " .  $stock . "</span><br>";
				} else if ($stock <= 0) {
					$product_stock .= "<span class='pb-text-red-500 pb-text-[12px]'>" . $product_child->get_name() . ", Stock: 0 </span><br>";
				}
			}
		} else if (!empty($product->get_stock_quantity()) && $product->get_stock_quantity() <= $low_stock_cuantity) { //Single product
			$product_stock = $product->get_stock_quantity();
			$low_stock = true;
		}

		$name = carbon_get_post_meta($badge_id, 'badge_text');
		$text_size = carbon_get_post_meta($badge_id, 'badge_text_size') ? carbon_get_post_meta($badge_id, 'badge_text_size') : '12';
		$text_color = carbon_get_post_meta($badge_id, 'badge_text_color') ? carbon_get_post_meta($badge_id, 'badge_text_color') : '#000000';
		$bg_color = carbon_get_post_meta($badge_id, 'badge_bg_color') ? carbon_get_post_meta($badge_id, 'badge_bg_color') : 'transparent';
		$position = carbon_get_post_meta($badge_id, 'badge_position') === 'position_tr' ? 'right' : 'left';


		if ($low_stock && $have_variations) {
			echo '<span class="onsale pb-group pb-cursor-pointer pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-inline-block pb-' . $position . '-0 pb-top-0" style="font-size: ' . $text_size . 'px; color: ' . $text_color . '; background-color: ' . $bg_color . ' ">' . $name . '
			<div class="pb-whitespace-nowrap pb-bg-black pb-text-base pb-rounded-lg pb-absolute pb-z-10 pb-opacity-0 group-hover:pb-opacity-100 pb-bottom-full pb-left-1/2 pb--translate-x-1/2 pb-mb-2 pb-p-3 pb-pointer-events-none">
			<svg class="pb-absolute pb-text-black pb-h-2 pb-w-full pb-left-0 pb-top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve"><polygon class="fill-current" points="0,0 127.5,127.5 255,0"/></svg>'
				. $product_stock .
				'</div></span>';
		} else if ($low_stock && !$have_variations) {
			echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-' . $position . '-0 pb-top-0" 
			style="font-size: ' . $text_size . 'px; color: ' . $text_color . '; background-color: ' . $bg_color . ' ">' . $name . '</span>';
		} else if ($product->is_on_sale()) {
			echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-right-0 pb-top-0">Sale!</span>';
		}
	}

	function badge_type_custom($badge_id, $product)
	{
		$initial_date = strtotime(carbon_get_post_meta($badge_id, 'badge_initial_date'));
		$end_date = strtotime(carbon_get_post_meta($badge_id, 'badge_end_date'));
		$actual_date = strtotime(date("Y-m-d", time()));

		if (!carbon_get_post_meta($badge_id, 'badge_custom_set_date')  || ($actual_date >= $initial_date && $actual_date <= $end_date)) {
			$name = carbon_get_post_meta($badge_id, 'badge_text');
			$text_size = carbon_get_post_meta($badge_id, 'badge_text_size') ? carbon_get_post_meta($badge_id, 'badge_text_size') : '12';
			$text_color = carbon_get_post_meta($badge_id, 'badge_text_color') ? carbon_get_post_meta($badge_id, 'badge_text_color') : '#000000';
			$bg_color = carbon_get_post_meta($badge_id, 'badge_bg_color') ? carbon_get_post_meta($badge_id, 'badge_bg_color') : 'transparent';
			$position = carbon_get_post_meta($badge_id, 'badge_position') === 'position_tr' ? 'right' : 'left';

			echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-' . $position . '-0 pb-top-0" 
			style="font-size: ' . $text_size . 'px; color: ' . $text_color . '; background-color: ' . $bg_color . ' ">' . $name . '</span>';
		} else if ($product->is_on_sale()) {
			echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-right-0 pb-top-0">Sale!</span>';
		}
	}

	function badge_type_sale($badge_id, $product)
	{
		$name = carbon_get_post_meta($badge_id, 'badge_text');
		$text_size = carbon_get_post_meta($badge_id, 'badge_text_size') ? carbon_get_post_meta($badge_id, 'badge_text_size') : '12';
		$text_color = carbon_get_post_meta($badge_id, 'badge_text_color') ? carbon_get_post_meta($badge_id, 'badge_text_color') : '#000000';
		$bg_color = carbon_get_post_meta($badge_id, 'badge_bg_color') ? carbon_get_post_meta($badge_id, 'badge_bg_color') : 'transparent';
		$position = carbon_get_post_meta($badge_id, 'badge_position') === 'position_tr' ? 'right' : 'left';

		echo '<span class="onsale pb-absolute pb-py-1 pb-px-2 pb-m-2 pb-' . $position . '-0 pb-top-0" 
			style="font-size: ' . $text_size . 'px; color: ' . $text_color . '; background-color: ' . $bg_color . ' ">' . $name . '</span>';
	}
}
