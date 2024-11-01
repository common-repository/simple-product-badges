<?php

namespace SimpleProductBadges\Functionality;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class BadgeCustomPostTypes
{

    protected $plugin_name;
    protected $plugin_version;

    public function __construct($plugin_name, $plugin_version)
    {
        $this->plugin_name = $plugin_name;
        $this->plugin_version = $plugin_version;

        add_action('init', [$this, 'badge_custom_post_type']);

        add_action('after_setup_theme', array($this, 'load_cf'));
        add_action('carbon_fields_register_fields', array($this, 'register_fields'));
    }

    public function badge_custom_post_type()
    {
        $labels = array(
            'name'                  => _x('Badges', 'Post Type General Name', 'simple-product-badges'),
            'singular_name'         => _x('Badge', 'Post Type Singular Name', 'simple-product-badges'),
            'menu_name'             => __('Custom Badge', 'simple-product-badges'),
            'all_items'             => _x('All Badges', 'simple-product-badges'),
            'name_admin_bar'        => __('Custom Badge', 'simple-product-badges'),
            'add_new_item'          => __('Add New Badge', 'simple-product-badges'),
            'add_new'               => __('Add Badge', 'simple-product-badges'),
        );
        $args = array(
            'label'                 => __('Badge', 'simple-product-badges'),
            'labels'                => $labels,
            'supports'              => array('title', 'thumbnail', 'revisions'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'menu_icon'             => 'dashicons-editor-bold',
        );
        register_post_type('badges', $args);
    }


    public function load_cf()
    {
        \Carbon_Fields\Carbon_Fields::boot();
    }

    public function register_fields()
    {
        Container::make('post_meta', __('Badge', 'simple-product-badges'))
            ->where('post_type', '=', 'badges')
            ->add_fields(
                array(
                    Field::make('text', 'badge_priority', __('Badge priority (1 is max)', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_attribute('type', 'number')
                        ->set_visible_in_rest_api(true),
                    Field::make('select', 'badge_type', __('Badge Type'))
                        ->set_required(true)
                        ->add_options(array(
                            'type_custom' => __('Custom Badge', 'simple-product-badges'),
                            'type_new' => __('New Product', 'simple-product-badges'),
                            'type_lowstock' => __('Low in Stock', 'simple-product-badges'),
                            'type_sale' => __('Sale', 'simple-product-badges'),
                        ))
                        ->set_visible_in_rest_api(true),
                    // CUSTOM PRODUCT
                    Field::make('checkbox', 'badge_custom_set_date', __('¿You want the tag to be activated and deactivated automatically on selected dates?', 'simple-product-badges'))
                        ->set_visible_in_rest_api(true)
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_type',
                                'value' => 'type_custom',
                            )
                        )),
                    Field::make('date', 'badge_initial_date', __('Start Date', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_visible_in_rest_api(true)
                        ->set_storage_format('Y-m-d')
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_custom_set_date',
                                'value' => true,
                            ),
                            array(
                                'field' => 'badge_type',
                                'value' => 'type_custom',
                            )
                        )),
                    Field::make('date', 'badge_end_date', __('End Date', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_visible_in_rest_api(true)
                        ->set_storage_format('Y-m-d')
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_custom_set_date',
                                'value' => true,
                            ),
                            array(
                                'field' => 'badge_type',
                                'value' => 'type_custom',
                            )
                        )),
                    // NEW PRODUCT
                    Field::make('text', 'badge_new_days', __('Number of days to be considered new', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_attribute('type', 'number')
                        ->set_visible_in_rest_api(true)
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_type',
                                'value' => 'type_new',
                            )
                        )),
                    //LOW IN STOCK
                    Field::make('separator', 'crb_separator', __('In products with variations show a tooltip with the stock of each variation', 'simple-product-badges'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_type',
                                'value' => 'type_lowstock',
                            )
                        )),
                    Field::make('text', 'badge_lowstock_low', __('Quantity of stock to be considered low', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_attribute('type', 'number')
                        ->set_visible_in_rest_api(true)
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_type',
                                'value' => 'type_lowstock',
                            )
                        )),
                    Field::make(
                        'text',
                        'badge_lowstock_medium',
                        __('Quantity of stock to be considered High (For modal in product with variations)', 'simple-product-badges')
                    )
                        ->set_required(true)
                        ->set_attribute('type', 'number')
                        ->set_visible_in_rest_api(true)
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_type',
                                'value' => 'type_lowstock',
                            )
                        )),

                    //FOR ALL && SALE && CUSTOM
                    Field::make('text', 'badge_text', __('Display Name:', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_visible_in_rest_api(true),
                    Field::make('text', 'badge_text_size', __('Text Size, in px (By default 12px):', 'simple-product-badges'))
                        ->set_attribute('placeholder', '16')
                        ->set_attribute('type', 'number')
                        ->set_visible_in_rest_api(true),
                    Field::make('color', 'badge_text_color', __('Text Color: (By defalut black)', 'simple-product-badges'))
                        ->set_visible_in_rest_api(true),
                    Field::make('color', 'badge_bg_color', __('Badge Color: (Click on X, to set transparent)', 'simple-product-badges'))
                        ->set_visible_in_rest_api(true),
                    Field::make('select', 'badge_style', __('Style Badge (Coming soon)'))
                        ->add_options(array(
                            'bade_default' => __('Default Badge', 'simple-product-badges'),
                            //'new' => __('New', 'simple-product-badges'),
                        ))
                        ->set_visible_in_rest_api(true),
                    Field::make('select', 'badge_position', __('Badge Position (More coming soon)'))
                        ->set_required(true)
                        ->add_options(array(
                            'position_tl' => __('Top-Left', 'simple-product-badges'),
                            'position_tr' => __('Top-Right', 'simple-product-badges'),
                        ))
                        ->set_visible_in_rest_api(true),

                    //TRANSOFRMAR ESTO A UN SELECT
                    Field::make('select', 'badge_apply', __('Apply Badge In:'))
                        ->set_required(true)
                        ->add_options(array(
                            'apply_tag' => __('Product Tags', 'simple-product-badges'),
                            'apply_category' => __('Product Category', 'simple-product-badges'),
                            'apply_all' => __('All Products', 'simple-product-badges'),
                        ))
                        ->set_visible_in_rest_api(true),
                    Field::make('multiselect', 'apply_tags', __('Select Tags', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_options('badges_get_tags')
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_apply',
                                'value' => 'apply_tag',
                            )
                        ))
                        ->set_visible_in_rest_api(true),
                    Field::make('multiselect', 'apply_categories', __('Select Categorys', 'simple-product-badges'))
                        ->set_required(true)
                        ->set_options('badges_get_categorys')
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'badge_apply',
                                'value' => 'apply_category',
                            )
                        ))
                        ->set_visible_in_rest_api(true),
                )
            );
        return;
    }
}
