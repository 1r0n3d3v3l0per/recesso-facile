<?php
/**
 * Plugin Name: Recesso Facile
 * Plugin URI: https://irn3.com/recesso-facile
 * Description: Soluzione completa per la gestione del diritto di recesso conforme all'Art. 54-bis del Codice del Consumo italiano. Easy In, Easy Out.
 * Version: 1.1.0
 * Author: Andrea Ferro
 * Author URI: https://irn3.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recesso-facile
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RF_VERSION', '1.1.0');
define('RF_PLUGIN_FILE', __FILE__);
define('RF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Recesso Facile Class
 *
 * @class Recesso_Facile
 * @version 1.1.0
 */
final class Recesso_Facile {

    /**
     * The single instance of the class
     *
     * @var Recesso_Facile
     */
    protected static $_instance = null;

    /**
     * Main Recesso Facile Instance
     *
     * Ensures only one instance of Recesso Facile is loaded or can be loaded.
     *
     * @return Recesso_Facile - Main instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Recesso Facile Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();

        do_action('recesso_facile_loaded');
    }

    /**
     * Include required core files
     */
    private function includes() {
        // Core classes
        require_once RF_PLUGIN_DIR . 'includes/class-rf-install.php';
        require_once RF_PLUGIN_DIR . 'includes/class-rf-update.php';
        require_once RF_PLUGIN_DIR . 'includes/class-rf-autoloader.php';

        // Initialize autoloader
        RF_Autoloader::init();
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(RF_PLUGIN_FILE, array('RF_Install', 'activate'));
        register_deactivation_hook(RF_PLUGIN_FILE, array('RF_Install', 'deactivate'));

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'), 10);
        add_action('init', array($this, 'load_textdomain'));

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('admin_init', array('RF_Admin_Requests', 'init'));
        }

        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('wp_footer', array($this, 'render_sticky_button'));

        // HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Check for updates
        RF_Update::check_version();

        // Initialize core components
        RF_Ajax_Handler::init();
        RF_Shortcodes::init();
        RF_REST_API::init();
        RF_WooCommerce_Integration::init();

        // Daily cleanup cron handler (the event is scheduled on activation).
        add_action('recesso_facile_daily_cleanup', array($this, 'run_daily_cleanup'));

        do_action('recesso_facile_init');
    }

    /**
     * Daily cleanup task: removes old withdrawal requests and activity log
     * entries when the corresponding option is enabled. Runs via WP-Cron.
     */
    public function run_daily_cleanup() {
        if (get_option('rf_auto_delete_old_requests', 'no') !== 'yes') {
            return;
        }

        $days = absint(get_option('rf_delete_after_days', 365));
        if ($days < 1) {
            $days = 365;
        }

        if (class_exists('RF_Withdrawal_Service')) {
            RF_Withdrawal_Service::delete_old_requests($days);
        }
        if (class_exists('RF_Activity_Logger')) {
            RF_Activity_Logger::delete_old_activities($days);
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('recesso-facile', false, dirname(RF_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __('Recesso Facile', 'recesso-facile'),
            __('Recesso Facile', 'recesso-facile'),
            'manage_woocommerce',
            'recesso-facile',
            array($this, 'render_dashboard_page'),
            'dashicons-undo',
            56
        );

        add_submenu_page(
            'recesso-facile',
            __('Dashboard', 'recesso-facile'),
            __('Dashboard', 'recesso-facile'),
            'manage_woocommerce',
            'recesso-facile',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'recesso-facile',
            __('Richieste di Recesso', 'recesso-facile'),
            __('Richieste', 'recesso-facile'),
            'manage_woocommerce',
            'recesso-facile-requests',
            array($this, 'render_requests_page')
        );

        add_submenu_page(
            'recesso-facile',
            __('Eccezioni Prodotti', 'recesso-facile'),
            __('Eccezioni', 'recesso-facile'),
            'manage_woocommerce',
            'recesso-facile-exceptions',
            array($this, 'render_exceptions_page')
        );

        add_submenu_page(
            'recesso-facile',
            __('Registro Attività', 'recesso-facile'),
            __('Registro', 'recesso-facile'),
            'manage_woocommerce',
            'recesso-facile-activity',
            array($this, 'render_activity_page')
        );

        add_submenu_page(
            'recesso-facile',
            __('Impostazioni', 'recesso-facile'),
            __('Impostazioni', 'recesso-facile'),
            'manage_woocommerce',
            'recesso-facile-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        RF_Admin_Dashboard::render();
    }

    /**
     * Render requests page
     */
    public function render_requests_page() {
        RF_Admin_Requests::render();
    }

    /**
     * Render exceptions page
     */
    public function render_exceptions_page() {
        RF_Admin_Exceptions::render();
    }

    /**
     * Render activity page
     */
    public function render_activity_page() {
        RF_Admin_Activity::render();
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        RF_Admin_Settings::render();
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'recesso-facile') === false) {
            return;
        }

        wp_enqueue_style(
            'recesso-facile-admin',
            RF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            RF_VERSION
        );

        wp_enqueue_script(
            'recesso-facile-admin',
            RF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            RF_VERSION,
            true
        );

        wp_localize_script('recesso-facile-admin', 'recessoFacileAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('recesso_facile_admin'),
            'i18n' => array(
                'confirm_delete' => __('Sei sicuro di voler eliminare questo elemento?', 'recesso-facile'),
                'error' => __('Si è verificato un errore. Riprova.', 'recesso-facile'),
            )
        ));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_scripts() {
        if (!is_account_page() && !is_page()) {
            return;
        }

        wp_enqueue_style(
            'recesso-facile-frontend',
            RF_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            RF_VERSION
        );

        wp_enqueue_script(
            'recesso-facile-frontend',
            RF_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            RF_VERSION,
            true
        );

        wp_localize_script('recesso-facile-frontend', 'recessoFacile', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('recesso_facile_frontend'),
            'i18n' => array(
                'loading' => __('Caricamento...', 'recesso-facile'),
                'error' => __('Si è verificato un errore. Riprova.', 'recesso-facile'),
                'required_field' => __('Questo campo è obbligatorio', 'recesso-facile'),
                'invalid_email' => __('Inserisci un indirizzo email valido', 'recesso-facile'),
            )
        ));
    }

    /**
     * Render sticky withdrawal button
     */
    public function render_sticky_button() {
        if (!$this->should_show_sticky_button()) {
            return;
        }

        $button_text = get_option('rf_button_text', __('Richiedi Recesso', 'recesso-facile'));
        $button_position = get_option('rf_button_position', 'bottom-right');

        ?>
        <div id="rf-sticky-button" class="rf-sticky-button rf-position-<?php echo esc_attr($button_position); ?>">
            <a href="<?php echo esc_url($this->get_withdrawal_page_url()); ?>" class="rf-button">
                <span class="rf-button-icon">&#8634;</span>
                <span class="rf-button-text"><?php echo esc_html($button_text); ?></span>
            </a>
        </div>
        <?php
    }

    /**
     * Check if sticky button should be shown
     */
    private function should_show_sticky_button() {
        $enabled = get_option('rf_enable_sticky_button', 'yes');

        if ($enabled !== 'yes') {
            return false;
        }

        // Don't show on admin or cart/checkout pages
        if (is_admin() || is_cart() || is_checkout()) {
            return false;
        }

        return apply_filters('recesso_facile_show_sticky_button', true);
    }

    /**
     * Get withdrawal page URL
     */
    private function get_withdrawal_page_url() {
        $page_id = get_option('rf_withdrawal_page');

        if ($page_id) {
            return get_permalink($page_id);
        }

        return wc_get_account_endpoint_url('recesso');
    }

    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Recesso Facile richiede WooCommerce per funzionare. Installa e attiva WooCommerce.', 'recesso-facile'); ?></p>
        </div>
        <?php
    }

    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', RF_PLUGIN_FILE, true);
        }
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function version() {
        return RF_VERSION;
    }
}

/**
 * Returns the main instance of Recesso_Facile
 *
 * @return Recesso_Facile
 */
function RF() {
    return Recesso_Facile::instance();
}

// Initialize the plugin
RF();
