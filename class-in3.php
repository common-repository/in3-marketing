<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * In_3 Class
 */
final class In_3
{

    public $version = '1.1.0';

    protected static $_instance = null;

    /**
     * Throw error on object clone
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object therefore, we don't want the object to be cloned.
     *
     * @return void
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'in3'), '1.0.0');
    }

    /**
     * Disable unserializing of the class
     *
     * @return void
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'in3'), '1.0.0');
    }

    /**
     * Main In3 Instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * In3 Constructor.
     */
    public function __construct()
    {
        if (!class_exists('Mustache_Autoloader')) {
            include_once($this->plugin_path() . '/includes/vendors/Mustache/Autoloader.php');
            Mustache_Autoloader::register();
        }
        include_once($this->plugin_path() . '/includes/functions.php');

        $this->init_hooks();
    }

    /**
     *  Init hooks
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'), 0);
        add_action('admin_menu', array($this, 'settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp', array($this, 'displaying_hooks'));

        add_action('wp_ajax_get_preview_html', array($this, 'get_preview_html'));

        add_action('admin_enqueue_scripts', array($this, 'load_scripts_admin'), 10, 1);
        add_action('wp_enqueue_scripts', array($this, 'load_scripts_frontend'));

        add_shortcode('in3', [$this, 'shortcode']);

        add_filter('plugin_action_links', array($this, 'plugin_add_settings_link'), 10, 5);
    }

    /**
     *  Init displaying hooks
     */
    public function displaying_hooks()
    {
        $in3_settings = get_option('in3_settings');
        $is_fse_theme = isset($in3_settings['for_fse_theme']) && 'yes' === $in3_settings['for_fse_theme'];

        if (!is_array($in3_settings) || empty($in3_settings['locations'])) {
            return;
        }

        if (in_array('single_product', $in3_settings['locations'])) {
            if (is_singular('product')) {
                global $post;
                $product = wc_get_product($post->ID);

                if (!$product) {
                    return;
                }

                if ($is_fse_theme) {
                    if ('variable' === $product->get_type()) {
                        add_action('woocommerce_single_product_summary', 'in3_display_on_single_product', 11);
                    }

                    if ('variable' !== $product->get_type()) {
                        add_filter('render_block', 'in3_display_on_single_product_before_block', 10, 2);
                    }
                } else {
                    add_action('woocommerce_single_product_summary', 'in3_display_on_single_product', 11);
                }
            }
        }

        if (in_array('cart', $in3_settings['locations'])) {
            add_action('woocommerce_cart_totals_after_order_total', 'in3_display_in_cart', 11);
        }

        if (in_array('checkout', $in3_settings['locations'])) {
            add_action('woocommerce_review_order_after_order_total', 'in3_display_in_cart', 11);
        }

        if (in_array('shop', $in3_settings['locations'])) {
            // silence
        }
    }

    /**
     * Conditionally loading styles/scripts in admin area
     */
    function load_scripts_admin($hook)
    {
        if ('woocommerce_page_in3-settings' === $hook) {
            wp_enqueue_style(
                'in3-styles',
                $this->plugin_url() . '/assets/css/admin-styles.css',
                array(),
                $this->version
            );

            wp_enqueue_script(
                'in3-admin-scripts',
                $this->plugin_url() . '/assets/js/admin-scripts.js',
                array(),
                $this->version,
                true
            );

            $in3Data = [
                'i18n' => [
                    'itemSelectText' => __('Press to select', 'in3'),
                ]
            ];

            wp_localize_script('in3-admin-scripts', 'in3Data', $in3Data);
        }
    }

    /**
     * Conditionally loading for front end styles/scripts
     */
    function load_scripts_frontend()
    {
        global $post;

        if (is_singular('product') || is_cart() || is_checkout()) {
            $in3_settings = get_option('in3_settings');

            wp_enqueue_style(
                'in3-styles',
                $this->plugin_url() . '/assets/css/frontend.css',
                array(),
                $this->version
            );

            wp_enqueue_script(
                'in3-scripts',
                $this->plugin_url() . '/assets/js/frontend.js',
                array('jquery'),
                $this->version,
                true
            );

            $in3Data = [
                'minAmount' => $in3_settings['min_amount'],
                'maxAmount' => $in3_settings['max_amount']
            ];

            if (function_exists('wc_get_price_decimals')) {
                $in3Data['currencySymbol'] = get_woocommerce_currency_symbol();
                $in3Data['decimalPoint']   = wc_get_price_decimal_separator();
                $in3Data['separator']      = wc_get_price_thousand_separator();
                $in3Data['decimals']       = wc_get_price_decimals();
            } else {
                $in3Data['currencySymbol'] = '€';
                $in3Data['decimalPoint']   = '.';
                $in3Data['separator']      = '';
                $in3Data['decimals']       = 2;
            }

            wp_localize_script('in3-scripts', 'in3Data', $in3Data);
        }
    }

    /**
     * Add settings page menu item
     */
    function settings_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('iDEAL in3 Marketing Tool', 'in3'),
            __('iDEAL in3 Marketing Tool', 'in3'),
            'manage_woocommerce',
            'in3-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Add settings page
     */
    function settings_page()
    {
        if (isset($_POST) && isset($_POST['option_page']) && $_POST['option_page'] === 'in3_general_settings') {
            $this->save_settings();
        }

        ?>
        <div class="wrap">
            <div
                    id="icon-themes"
                    class="icon32"></div>
            <h2><?php
                _e('iDEAL in3 marketing tool', 'in3') ?></h2>

            <?php
            settings_errors(); ?>

            <form
                    class="in3SettingsForm"
                    method="POST"
                    action="admin.php?page=in3-settings">
                <?php
                settings_fields('in3_general_settings');
                do_settings_sections('in3_general_settings');
                $this->admin_preview();
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register plugin's settings on the settings page
     */
    function register_settings()
    {
        add_settings_section(
            'in3_widget_settings_section',
            __('The marketing tool settings page', 'in3'),
            array($this, 'in3_widget_settings_section_desc'),
            'in3_general_settings'
        );

        $settings_cfg = $this->settings_config();

        foreach ($settings_cfg as $s_id => $s_args) {
            add_settings_field(
                $s_id,
                $s_args['label'],
                array($this, 'in3_render_setting_field'),
                'in3_general_settings',
                'in3_widget_settings_section',
                $s_args
            );
            register_setting(
                'in3_general_settings',
                $s_id,
                [
                    'sanitize_callback' => array($this, 'in3_setting_validation')
                ]
            );
        }
    }

    /**
     * Render a setting template based on given config
     */
    function in3_widget_settings_section_desc()
    {
    }

    /**
     * Render a setting template based on given config
     */
    function in3_render_setting_field($args)
    {
        $saved_data = get_option('in3_settings');
        $saved_data = maybe_unserialize($saved_data);
        $required   = '';

        if (isset($args['required'])) {
            $required = ' required';
        }

        $description = (isset($args['desc'])) ? $args['desc'] : '';
        $kses_args   = array(
            'div'    => array(
                'class' => array(),
            ),
            'span'   => array(
                'class' => array(),
            ),
            'em'     => array(),
            'strong' => array(),
            'code'   => array(),
            'br'     => array()
        );

        switch ($args['type']) {
            case 'input':
                $value = !empty($saved_data[$args['id']]) ? $saved_data[$args['id']] : '';
                if ($args['subtype'] === 'number') {
                    $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
                    $prependEnd   = (isset($args['prepend_value'])) ? '</div>' : '';
                    $step         = (isset($args['step'])) ? ' step="' . esc_attr($args['step']) . '"' : '';
                    $min          = (isset($args['min'])) ? ' min="' . esc_attr($args['min']) . '"' : '';
                    $max          = (isset($args['max'])) ? ' max="' . esc_attr($args['max']) . '"' : '';
                    $placeholder  = (isset($args['placeholder'])) ? ' placeholder="' . esc_attr(
                            $args['placeholder']
                        ) . '"' : '';
                    if (isset($args['disabled'])) {
                        echo $prependStart . '<input type="' . esc_attr($args['subtype']) . '" id="' . esc_attr(
                                $args['id']
                            ) . '_disabled"' . $step . '' . $max . '' . $min . $placeholder . ' name="' . $args['name'] . '_disabled" size="40" disabled value="' . esc_attr(
                                $value
                            ) . '" /><input type="hidden" id="' . esc_attr($args['id']) . '" ' . esc_attr(
                                $step
                            ) . ' ' . esc_attr($max) . ' ' . esc_attr($min) . ' name="' . esc_attr(
                                $args['name']
                            ) . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
                    } else {
                        echo $prependStart . '<input type="' . esc_attr($args['subtype']) . '" id="' . esc_attr(
                                $args['id']
                            ) . '" "' . $args['required'] . '"' . $step . '' . $max . '' . $min . $placeholder . ' name="' . esc_attr(
                                $args['name']
                            ) . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
                    }
                } elseif ($args['subtype'] === 'checkbox') {
                    $checked = ($value) ? 'checked' : '';
                    echo '<input type="' . esc_attr($args['subtype']) . '" id="' . esc_attr(
                            $args['id']
                        ) . '"' . esc_attr($required) . ' name="' . esc_attr(
                            $args['name']
                        ) . '" size="40" value="1" ' . esc_attr($checked) . ' />';
                }

                if (!empty($description)) {
                    echo '<p class="description">' . wp_kses($description, $kses_args) . '</p>';
                }

                break;
            case 'select':
                $value    = !empty($saved_data[$args['id']]) ? $saved_data[$args['id']] : '';
                $multiple = '';

                if ($args['multiple']) {
                    $value        = !empty($saved_data[$args['id']]) ? $saved_data[$args['id']] : [];
                    $value        = is_array($value) ? $value : [$value];
                    $multiple     = ' multiple';
                    $args['name'] = $args['name'] . '[]';
                }

                echo '<select id="' . $args['id'] . '"' . esc_attr(
                        $required
                    ) . ' name="' . $args['name'] . '"' . $multiple . '>';
                if (!empty($args['options_list'])) {
                    foreach ($args['options_list'] as $v => $l) {
                        if ($args['multiple']) {
                            $checked = ($value && in_array($v, $value)) ? 'selected' : '';
                        } else {
                            $checked = ($value && $value === $v) ? 'selected' : '';
                        }
                        echo '<option value="' . esc_attr($v) . '"' . esc_attr($checked) . '>' . $l . '</option>';
                    }
                }
                echo '</select>';

                if (!empty($description)) {
                    echo '<p class="description">' . wp_kses($description, $kses_args) . '</p>';
                }

                break;
            case 'textarea':
                $placeholder = (isset($args['placeholder'])) ? ' placeholder="' . esc_attr(
                        $args['placeholder']
                    ) . '"' : '';
                $value       = !empty($saved_data[$args['id']]) ? $saved_data[$args['id']] : '';
                echo '<textarea class="large-text" id="' . esc_attr($args['id']) . '"' . $placeholder . esc_attr(
                        $required
                    ) . ' name="' . esc_attr($args['name']) . '">';
                echo $value;
                echo '</textarea>';

                if (!empty($description)) {
                    echo '<p class="description">' . wp_kses($description, $kses_args) . '</p>';
                }

                break;
            case 'paragraph':
                echo '<p class="description">' . wp_kses($description, $kses_args) . '</p>';
                break;
            default:
                echo 'Error in settings config';
                break;
        }
    }

    /**
     * Plugin's settings main config.
     */
    function settings_config()
    {
        return [
            'locations'     => [
                'label'        => __('Activate the marketing tool on:', 'in3'),
                'type'         => 'select',
                'multiple'     => true,
                'id'           => 'locations',
                'name'         => 'locations',
                'required'     => false,
                'options_list' => [
                    ''               => __('Click here to add locations', 'in3'),
                    'single_product' => __('Product page', 'in3'),
                    //'shop'           => __( 'Shop page', 'in3' ),
                    'cart'           => __('Cart page', 'in3'),
                    'checkout'       => __('Checkout page', 'in3')
                ],
                'desc'         => __(
                    'The marketing tool will be displayed in the locations that you\'ve selected above. Additionally, you can manually place the following shortcode on a WooCommerce product page to display the marketing tool at any position: <code>[in3]</code>',
                    'in3'
                )
            ],
            'for_fse_theme' => [
                'label'        => __('Enhanced compatibility mode for Gutenberg blocks', 'in3'),
                'type'         => 'select',
                'multiple'     => false,
                'id'           => 'for_fse_theme',
                'name'         => 'for_fse_theme',
                'required'     => false,
                'options_list' => [
                    'no'  => __('No', 'in3'),
                    'yes' => __('Yes', 'in3')
                ],
                'desc'         => __(
                    'This setting lets you place a widget inside the Gutenberg "add to cart" block and turns off the usual WordPress hook. This makes sure the widget appears only within the block and avoids showing it twice on the product page.',
                    'in3'
                )
            ],
            /*'appearance'    => [
                'label'        => __('Choose the marketing tool appearance', 'in3'),
                'type'         => 'select',
                'multiple'     => false,
                'id'           => 'appearance',
                'name'         => 'appearance',
                'required'     => false,
                'options_list' => [
                    'branded' => __('Branded with iDEAL in3 logo and with label', 'in3'),
                    //'branded_only' => __( 'Only branded', 'in3' ),
                    'label'   => __('Only label', 'in3'),
                    'textual' => __('Textual', 'in3')
                ]
            ],*/
            'theme'         => [
                'label'        => __('Displaying theme', 'in3'),
                'type'         => 'select',
                'multiple'     => false,
                'id'           => 'theme',
                'name'         => 'theme',
                'required'     => false,
                'options_list' => [
                    'light' => __('Light', 'in3'),
                    'dark'  => __('Dark', 'in3')
                ]
            ],
            /*'display_as'    => [
                'label'        => __('Display as', 'in3'),
                'type'         => 'select',
                'multiple'     => false,
                'id'           => 'display_as',
                'name'         => 'display_as',
                'required'     => false,
                'options_list' => $this->widget_display_options()
            ],*/
            'tooltip_desc'  => [
                'label'       => __('Description text for info tooltip', 'in3'),
                'type'        => 'textarea',
                'multiple'    => false,
                'id'          => 'tooltip_desc',
                'name'        => 'tooltip_desc',
                'placeholder' => __(
                    'Reken direct één derde deel af, het tweede en derde deel betaal je binnen 30 en 60 dagen.',
                    'in3'
                ),
                'required'    => false
            ],
            'min_amount'    => [
                'label'       => __('Min amount (euro)', 'in3'),
                'type'        => 'input',
                'subtype'     => 'number',
                'id'          => 'min_amount',
                'name'        => 'min_amount',
                'placeholder' => '50',
                'min'         => 50,
                'required'    => false,
                'desc'        => __('The minimum amount from which the marketing tool will be visible', 'in3')
            ],
            'max_amount'    => [
                'label'       => __('Max amount (euro)', 'in3'),
                'type'        => 'input',
                'subtype'     => 'number',
                'id'          => 'max_amount',
                'name'        => 'max_amount',
                'placeholder' => '3000',
                'min'         => 0,
                'required'    => false,
                'desc'        => __('The maximum amount from which the marketing tool will be visible', 'in3')
            ],
            'amount_desc'   => [
                'label' => '',
                'type'  => 'paragraph',
                'desc'  => __('The chosen range is used for both the product price and the cart totals price', 'in3')
            ],
        ];
    }

    /**
     * Plugin's settings form handler.
     */
    public function save_settings()
    {
        $post_data = (array)$_POST;

        $valid_setting_keys = array_keys($this->settings_config());
        $filtered_data      = array_filter(
            $post_data,
            function ($k) use ($valid_setting_keys) {
                return in_array($k, $valid_setting_keys);
            },
            ARRAY_FILTER_USE_KEY
        );

        // sanitazing
        $sanitized_data = $this->sanitize_data($filtered_data);

        add_settings_error(
            'in3_general_settings',
            1,
            __('Settings saved!', 'in3'),
            'success'
        );

        update_option('in3_settings', $sanitized_data);
    }

    /**
     * Sanitize data
     */
    public function sanitize_data($data)
    {
        $new = [];

        array_walk(
            $data,
            function ($val, $key) use (&$new) {
                if (is_string($val)) {
                    if (in_array($key, ['min_amount', 'max_amount'])) {
                        $new[$key] = absint($val);
                    } elseif ('tooltip_desc' === $key) {
                        $new[$key] = sanitize_textarea_field($val);
                    } else {
                        $new[$key] = sanitize_text_field($val);
                    }
                } elseif (is_array($val)) {
                    $new[$key] = array_map('sanitize_text_field', $val);
                }
            }
        );

        return $new;
    }

    /**
     * The list of all available displaying options. To be shown in setting's select field
     */
    public function widget_display_options()
    {
        return [
            'opt1' => __('Of 3x {{{bedrag}}}, 0% rente (i)', 'in3'),
            'opt2' => __('Of 3x {{{bedrag}}} zonder rente (i)', 'in3'),
            'opt3' => __('Of {{{bedrag}}} in 3 termijnen, 0% rente (i)', 'in3'),
            'opt4' => __('Of {{{bedrag}}} in 3 termijnen zonder rente (i)', 'in3'),
            'opt5' => __('Of in 3 termijnen, 0% rente (i)', 'in3'),
            'opt6' => __('Of in 3 termijnen zonder rente (i)', 'in3')
        ];
    }

    /**
     * The list of all available displaying options. To be used in Mustache tmpl engine
     */
    public function widget_get_display_text_tmpl($setting)
    {
        $texts = [
            'opt1' => __('Of <strong>3x {{{bedrag}}}</strong>, 0% rente', 'in3'),
            'opt2' => __('Of <strong>3x {{{bedrag}}} zonder rente</strong>', 'in3'),
            'opt3' => __('Of <strong>{{{bedrag}}} in 3 termijnen</strong>, 0% rente', 'in3'),
            'opt4' => __('Of <strong>{{{bedrag}}} in 3 termijnen zonder rente</strong>', 'in3'),
            'opt5' => __('Of in <strong>3 termijnen</strong>, 0% rente', 'in3'),
            'opt6' => __('Of in <strong>3 termijnen zonder rente</strong>', 'in3')
        ];

        return isset($texts[$setting]) ? $texts[$setting] : '';
    }

    /**
     * Set default values for settings upon plugin activation
     */
    public function set_defaults()
    {
        $existing = get_option('in3_settings');

        if (!$existing) {
            $defaults = [
                'locations'     => ['single_product', 'cart', 'checkout'],
                'appearance'    => 'branded',
                'theme'         => 'dark',
                'display_as'    => 'opt1',
                'for_fse_theme' => 'no',
                'tooltip_desc'  => __(
                    'Reken direct één derde deel af, het tweede en derde deel betaal je binnen 30 en 60 dagen.',
                    'in3'
                ),
                'min_amount'    => 50,
                'max_amount'    => 3000
            ];

            update_option('in3_settings', $defaults);
        }
    }

    /**
     * Display preview of the widget in admin area
     */
    function admin_preview()
    {
        $in3_settings     = get_option('in3_settings');
        $bedrag_formatted = in3_formatted(420);

        $data = [
            'appearance'       => 'branded',
            'theme'            => $in3_settings['theme'],
            'tooltip_desc'     => $in3_settings['tooltip_desc'],
            'display_as_text'  => $this->widget_get_display_text_tmpl('opt1'),
            'bedrag_formatted' => get_woocommerce_currency_symbol() . $bedrag_formatted
        ];

        if (empty($in3_settings['tooltip_desc'])) {
            $data['tooltip_desc'] = __(
                'Reken direct één derde deel af, het tweede en derde deel betaal je binnen 30 en 60 dagen.',
                'in3'
            );
        }

        ?>
        <button
                type="button"
                class="in3PreviewBtn button action">
            <?php
            _e('Refresh preview', 'in3') ?>
        </button>

        <div class="in3PreviewContainer">
            <?php
            in3_widget_tmpl($data) ?>
        </div>
        <?php
    }

    /**
     * Get widget HTML
     */
    function get_preview_html()
    {
        $_POST = json_decode(file_get_contents('php://input', true), true);

        $valid_setting_keys = array_keys($this->settings_config());
        $filtered_data      = array_filter(
            $_POST,
            function ($k) use ($valid_setting_keys) {
                return in_array($k, $valid_setting_keys);
            },
            ARRAY_FILTER_USE_KEY
        );

        // sanitazing
        $sanitized_data   = $this->sanitize_data($filtered_data);
        $bedrag_formatted = in3_formatted(420);

        $data = [
            'href'             => 'https://www.payin3.nl/nl/?utm_source=Plug-in&utm_medium=WooCommerce&utm_campaign=Meer_info',
            'appearance'       => 'branded',
            'theme'            => $sanitized_data['theme'],
            'tooltip_desc'     => $sanitized_data['tooltip_desc'],
            'display_as_text'  => $this->widget_get_display_text_tmpl('opt1'),
            'bedrag_formatted' => get_woocommerce_currency_symbol() . $bedrag_formatted
        ];

        if (empty($sanitized_data['tooltip_desc'])) {
            $data['tooltip_desc'] = __(
                'Reken direct één derde deel af, het tweede en derde deel betaal je binnen 30 en 60 dagen.',
                'in3'
            );
        }

        wp_send_json_success(in3_widget_tmpl($data, true));
    }

    /**
     * shortcode()
     */
    public function shortcode()
    {
        global $product;

        if (!is_singular('product')) {
            return;
        }

        if (!$product) {
            return;
        }

        if ('variable' !== $product->get_type()) {
            $in3_settings   = get_option('in3_settings');
            $price          = $product->get_price();
            $should_display = in3_should_display($price, $in3_settings);

            if (!$should_display) {
                return;
            }

            $bedrag_formatted = in3_formatted($price);

            $data = [
                'href'             => 'https://www.payin3.nl/nl/?utm_source=Plug-in&utm_medium=WooCommerce&utm_campaign=Meer_info',
                'appearance'       => 'branded',
                'theme'            => $in3_settings['theme'],
                'tooltip_desc'     => $in3_settings['tooltip_desc'],
                'display_as_text'  => In3()->widget_get_display_text_tmpl('opt1'),
                'bedrag_formatted' => get_woocommerce_currency_symbol() . $bedrag_formatted
            ];

            if (empty($in3_settings['tooltip_desc'])) {
                $data['tooltip_desc'] = __(
                    'Reken direct één derde deel af, het tweede en derde deel betaal je binnen 30 en 60 dagen.',
                    'in3'
                );
            }

            return in3_widget_tmpl($data, true);
        }
    }

    /**
     * @param $links
     * @return mixed
     */
    public function plugin_add_settings_link($actions, $plugin_file)
    {
        if ('in3/index.php' === $plugin_file) {
            array_unshift($actions, '<a href="admin.php?page=in3-settings">' . __('Settings', 'in3') . '</a>');
        }
        return $actions;
    }

    /**
     * load_plugin_textdomain()
     */
    public function load_plugin_textdomain()
    {
        load_textdomain('in3', WP_LANG_DIR . '/in3/in3-' . get_locale() . '.mo');
        load_plugin_textdomain('in3', false, plugin_basename(dirname(__FILE__)) . "/languages");
    }

    /**
     * plugin_url()
     */
    public function plugin_url()
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * plugin_path()
     */
    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Get Ajax URL.
     * @return string
     */
    public function ajax_url()
    {
        return admin_url('admin-ajax.php', 'relative');
    }

    /**
     * on plugin activation
     */
    public function activation()
    {
        $this->set_defaults();
    }

    /**
     * on plugin activation
     */
    public function deactivation()
    {
    }
}
