<?php
/**
 * Plugin Name: WooCommerce envia.mx Handling Fee
 * Plugin URI: https://tu-sitio.com
 * Description: Agrega un costo de manejo adicional a los envíos de envia.mx sin mostrarlo al cliente
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: wc-enviamx-handling-fee
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// Definir constantes del plugin
define('WC_ENVIAMX_HANDLING_FEE_VERSION', '1.0.0');
define('WC_ENVIAMX_HANDLING_FEE_PLUGIN_FILE', __FILE__);
define('WC_ENVIAMX_HANDLING_FEE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_ENVIAMX_HANDLING_FEE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Verificar si WooCommerce está activo
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'wc_enviamx_handling_fee_missing_woocommerce_notice');
    return;
}

/**
 * Mostrar aviso si WooCommerce no está activo
 */
function wc_enviamx_handling_fee_missing_woocommerce_notice() {
    ?>
    <div class="error">
        <p>
            <strong>WooCommerce envia.mx Handling Fee</strong> requiere que WooCommerce esté instalado y activado.
        </p>
    </div>
    <?php
}

// Cargar clases del plugin
add_action('plugins_loaded', 'wc_enviamx_handling_fee_init');

function wc_enviamx_handling_fee_init() {
    // Cargar archivos necesarios
    require_once WC_ENVIAMX_HANDLING_FEE_PLUGIN_DIR . 'includes/class-handling-fee.php';
    require_once WC_ENVIAMX_HANDLING_FEE_PLUGIN_DIR . 'includes/class-settings.php';
    
    // Inicializar clases
    new WC_EnviaMX_Handling_Fee();
    new WC_EnviaMX_Handling_Fee_Settings();
    
    // Cargar traducciones
    load_plugin_textdomain('wc-enviamx-handling-fee', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Activar el plugin
 */
register_activation_hook(__FILE__, 'wc_enviamx_handling_fee_activate');

function wc_enviamx_handling_fee_activate() {
    // Configuración por defecto
    add_option('wc_enviamx_handling_fee_amount', '50');
    add_option('wc_enviamx_handling_fee_type', 'fixed');
    add_option('wc_enviamx_handling_fee_enabled', 'yes');
}

/**
 * Desactivar el plugin
 */
register_deactivation_hook(__FILE__, 'wc_enviamx_handling_fee_deactivate');

function wc_enviamx_handling_fee_deactivate() {
    // Limpiar opciones si es necesario
    // delete_option('wc_enviamx_handling_fee_amount');
    // delete_option('wc_enviamx_handling_fee_type');
    // delete_option('wc_enviamx_handling_fee_enabled');
}
