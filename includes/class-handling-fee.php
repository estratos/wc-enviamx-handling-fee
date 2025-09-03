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
                } else {
                    $additional_cost = $handling_fee_amount;
                }
                
                // Aplicar el costo adicional
                $rate->cost += $additional_cost;
                
                // Actualizar taxes si existen
                if (!empty($rate->taxes)) {
                    $taxes = array();
                    foreach ($rate->taxes as $key => $tax) {
                        if ($tax > 0 && $original_cost > 0) {
                            $tax_ratio = $rate->cost / $original_cost;
                            $taxes[$key] = $tax * $tax_ratio;
                        } else {
                            $taxes[$key] = $tax;
                        }
                    }
                    $rate->taxes = $taxes;
                }
                
                // Agregar metadata para tracking (solo debug)
                $rate->add_meta_data('_handling_fee_added', $additional_cost, true);
                $rate->add_meta_data('_original_cost', $original_cost, true);
            }
        }
        
        return $rates;
    }
    
    /**
     * Verificar si es un método de envia.mx
     */
    private function is_envia_mx_method($rate) {
        $envia_mx_identifiers = array(
            'envia_mx',
            'enviamx',
            'envia-mx',
            'envia',
            'shipping_envia_mx'
        );
        
        $method_id = $rate->method_id;
        $instance_id = $rate->instance_id;
        
        // Verificar por method_id
        foreach ($envia_mx_identifiers as $identifier) {
            if (strpos($method_id, $identifier) !== false) {
                return true;
            }
        }
        
        // Verificación adicional por instance_id
        if ($instance_id > 0) {
            $shipping_method = WC_Shipping_Zones::get_shipping_method($instance_id);
            if ($shipping_method && is_object($shipping_method)) {
                $shipping_method_id = $shipping_method->id;
                foreach ($envia_mx_identifiers as $identifier) {
                    if (strpos($shipping_method_id, $identifier) !== false) {
                        return true;
                    }
                }
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
                WC_ENVIAMX_HANDLING_FEE_PLUGIN_URL . 'assets/admin.js',
                array('jquery', 'wc-checkout'),
                WC_ENVIAMX_HANDLING_FEE_VERSION,
                true
            );
        }
    }
}