<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_EnviaMX_Handling_Fee_Compatibility {
    
    public function __construct() {
        $this->check_compatibility();
    }
    
    /**
     * Verificar compatibilidad con HPOS y otras características
     */
    public function check_compatibility() {
        // Verificar si HPOS está activado
        if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            // Declarar compatibilidad con HPOS (ya se hace en el archivo principal)
            
            // Verificar si hay conflictos
            add_action('admin_notices', array($this, 'check_for_conflicts'));
        }
    }
    
    /**
     * Verificar conflictos potenciales
     */
    public function check_for_conflicts() {
        // Verificar si hay otros plugins que modifiquen costos de envío
        $conflicting_plugins = array();
        
        // Lista de plugins potencialmente conflictivos
        $potential_conflicts = array(
            'advanced-shipping-packages/advanced-shipping-packages.php',
            'weight-based-shipping-for-woocommerce/wbs.php',
            'flexible-shipping/flexible-shipping.php',
            // Agrega más plugins si es necesario
        );
        
        foreach ($potential_conflicts as $plugin) {
            if (is_plugin_active($plugin)) {
                $conflicting_plugins[] = $plugin;
            }
        }
        
        if (!empty($conflicting_plugins)) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>WooCommerce envia.mx Handling Fee:</strong> 
                    <?php _e('Se detectaron plugins que podrían causar conflictos con los cálculos de envío:', 'wc-enviamx-handling-fee'); ?>
                </p>
                <ul>
                    <?php foreach ($conflicting_plugins as $plugin): ?>
                        <li><?php echo $plugin; ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <?php _e('Por favor, verifica que los costos de envío se calculen correctamente.', 'wc-enviamx-handling-fee'); ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Método para manejar diferentes versiones de WooCommerce
     */
    public static function get_shipping_method_instance($instance_id) {
        if (method_exists('WC_Shipping_Zones', 'get_shipping_method')) {
            return WC_Shipping_Zones::get_shipping_method($instance_id);
        }
        
        // Fallback para versiones antiguas
        global $wpdb;
        
        $method = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods 
            WHERE instance_id = %d
        ", $instance_id));
        
        if ($method) {
            $shipping_method = WC_Shipping::instance()->get_shipping_method($method->method_id);
            if ($shipping_method) {
                $shipping_method->instance_id = $instance_id;
                return $shipping_method;
            }
        }
        
        return false;
    }
}

// Inicializar la clase de compatibilidad
new WC_EnviaMX_Handling_Fee_Compatibility();