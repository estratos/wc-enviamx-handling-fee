<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_EnviaMX_Handling_Fee {
    
    public function __construct() {
        add_filter('woocommerce_package_rates', array($this, 'add_handling_fee'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Agregar costo de manejo a los envíos de envia.mx
     */
    public function add_handling_fee($rates, $package) {
        // Verificar si está habilitado
        if (get_option('wc_enviamx_handling_fee_enabled', 'yes') !== 'yes') {
            return $rates;
        }
        
        $handling_fee_amount = floatval(get_option('wc_enviamx_handling_fee_amount', '50'));
        $handling_fee_type = get_option('wc_enviamx_handling_fee_type', 'fixed');
        
        if ($handling_fee_amount <= 0) {
            return $rates;
        }
        
        foreach ($rates as $rate_key => $rate) {
            // Detectar métodos de envia.mx
            if ($this->is_envia_mx_method($rate)) {
                $original_cost = $rate->cost;
                
                // Calcular el costo adicional
                if ($handling_fee_type === 'percentage') {
                    $additional_cost = $original_cost * ($handling_fee_amount / 100);
                    // Limitar porcentaje máximo a 100%
                    if ($handling_fee_amount > 100) {
                        $additional_cost = $original_cost;
                    }
                } else {
                    $additional_cost = $handling_fee_amount;
                }
                
                // Aplicar el costo adicional
                $rate->cost += $additional_cost;
                
                // Actualizar taxes si existen (compatible con diferentes versiones)
                $this->update_taxes($rate, $original_cost);
                
                // Agregar metadata para tracking (solo debug)
                if (method_exists($rate, 'add_meta_data')) {
                    $rate->add_meta_data('_handling_fee_added', $additional_cost, true);
                    $rate->add_meta_data('_original_cost', $original_cost, true);
                }
            }
        }
        
        return $rates;
    }
    
    /**
     * Actualizar taxes de forma compatible
     */
    private function update_taxes($rate, $original_cost) {
        if (!empty($rate->taxes) && $original_cost > 0 && $rate->cost > 0) {
            $tax_ratio = $rate->cost / $original_cost;
            
            if (is_array($rate->taxes)) {
                foreach ($rate->taxes as $key => $tax) {
                    if ($tax > 0) {
                        $rate->taxes[$key] = $tax * $tax_ratio;
                    }
                }
            }
            
            // Para compatibilidad con versiones antiguas
            if (isset($rate->taxes) && !is_array($rate->taxes) && $rate->taxes > 0) {
                $rate->taxes = $rate->taxes * $tax_ratio;
            }
        }
    }
    
    /**
     * Verificar si es un método de envia.mx (mejorado)
     */
    private function is_envia_mx_method($rate) {
        $envia_mx_identifiers = array(
            'envia_mx',
            'enviamx',
            'envia-mx',
            'envia',
            'shipping_envia_mx',
            'wc_envia_mx',
            'woocommerce_envia_mx'
        );
        
        $method_id = $rate->method_id;
        
        // Verificar por method_id (case insensitive)
        foreach ($envia_mx_identifiers as $identifier) {
            if (stripos($method_id, $identifier) !== false) {
                return true;
            }
        }
        
        // Verificar también en la etiqueta del método por si acaso
        $label = isset($rate->label) ? strtolower($rate->label) : '';
        foreach ($envia_mx_identifiers as $identifier) {
            if (stripos($label, $identifier) !== false) {
                return true;
            }
        }
        
        // Verificación adicional por instance_id (si está disponible)
        if (isset($rate->instance_id) && $rate->instance_id > 0) {
            $shipping_method = $this->get_shipping_method_instance($rate->instance_id);
            
            if ($shipping_method && is_object($shipping_method)) {
                $shipping_method_id = $shipping_method->id;
                
                foreach ($envia_mx_identifiers as $identifier) {
                    if (stripos($shipping_method_id, $identifier) !== false) {
                        return true;
                    }
                }
                
                // Verificar también el título del método
                $method_title = isset($shipping_method->title) ? strtolower($shipping_method->title) : '';
                foreach ($envia_mx_identifiers as $identifier) {
                    if (stripos($method_title, $identifier) !== false) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Obtener instancia del método de envío (compatible con HPOS)
     */
    private function get_shipping_method_instance($instance_id) {
        // Método moderno (WC 3.4+)
        if (method_exists('WC_Shipping_Zones', 'get_shipping_method')) {
            return WC_Shipping_Zones::get_shipping_method($instance_id);
        }
        
        // Fallback para versiones antiguas
        global $wpdb;
        
        // Intentar obtener el método de la manera tradicional
        $method = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods 
            WHERE instance_id = %d
        ", $instance_id));
        
        if ($method && !empty($method->method_id)) {
            $shipping_method = WC_Shipping::instance()->get_shipping_method($method->method_id);
            if ($shipping_method) {
                $shipping_method->instance_id = $instance_id;
                return $shipping_method;
            }
        }
        
        return false;
    }
    
    /**
     * Enqueue scripts para posibles ajustes en el frontend
     */
    public function enqueue_scripts() {
        if (is_checkout() || is_cart()) {
            wp_enqueue_script(
                'wc-enviamx-handling-fee',
                WC_ENVIAMX_HANDLING_FEE_PLUGIN_URL . 'assets/frontend.js',
                array('jquery', 'wc-checkout'),
                WC_ENVIAMX_HANDLING_FEE_VERSION,
                true
            );
            
            // Pasar variables a JavaScript
            wp_localize_script('wc-enviamx-handling-fee', 'wc_enviamx_handling_fee_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_enviamx_handling_fee_nonce')
            ));
        }
    }
    
    /**
     * Debug method para logging (opcional)
     */
    private function log_debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WC envia.mx Handling Fee: ' . $message);
        }
    }
}