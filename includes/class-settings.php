<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_EnviaMX_Handling_Fee_Settings {
    
    public function __construct() {
        add_filter('woocommerce_get_sections_shipping', array($this, 'add_settings_section'));
        add_filter('woocommerce_get_settings_shipping', array($this, 'add_settings'), 10, 2);
        add_action('woocommerce_admin_field_wc_enviamx_handling_fee_info', array($this, 'output_info_field'));
        add_action('admin_notices', array($this, 'add_hpos_notice'));
    }
    
    /**
     * Agregar sección de configuración
     */
    public function add_settings_section($sections) {
        $sections['wc_enviamx_handling_fee'] = __('envia.mx Handling Fee', 'wc-enviamx-handling-fee');
        return $sections;
    }
    
    /**
     * Agregar campos de configuración
     */
    public function add_settings($settings, $current_section) {
        if ($current_section !== 'wc_enviamx_handling_fee') {
            return $settings;
        }
        
        $settings = array();
        
        // Título de la sección
        $settings[] = array(
            'title' => __('Configuración de Costo de Manejo envia.mx', 'wc-enviamx-handling-fee'),
            'type'  => 'title',
            'desc'  => __('Agrega un costo adicional a los envíos calculados por envia.mx. Este costo no será visible para los clientes.', 'wc-enviamx-handling-fee'),
            'id'    => 'wc_enviamx_handling_fee_options'
        );
        
        // Campo de información
        $settings[] = array(
            'type' => 'wc_enviamx_handling_fee_info',
            'id'   => 'wc_enviamx_handling_fee_info'
        );
        
        // Habilitar/Deshabilitar
        $settings[] = array(
            'title'   => __('Habilitar', 'wc-enviamx-handling-fee'),
            'desc'    => __('Habilitar costo de manejo para envia.mx', 'wc-enviamx-handling-fee'),
            'id'      => 'wc_enviamx_handling_fee_enabled',
            'default' => 'yes',
            'type'    => 'checkbox'
        );
        
        // Tipo de costo
        $settings[] = array(
            'title'   => __('Tipo de Costo', 'wc-enviamx-handling-fee'),
            'id'      => 'wc_enviamx_handling_fee_type',
            'default' => 'fixed',
            'type'    => 'select',
            'options' => array(
                'fixed'      => __('Monto Fijo', 'wc-enviamx-handling-fee'),
                'percentage' => __('Porcentaje', 'wc-enviamx-handling-fee')
            )
        );
        
        // Monto del costo
        $settings[] = array(
            'title'    => __('Monto del Costo', 'wc-enviamx-handling-fee'),
            'desc'     => __('Ingresa el monto del costo de manejo. Para porcentaje, usa valores como 10 para 10%.', 'wc-enviamx-handling-fee'),
            'id'       => 'wc_enviamx_handling_fee_amount',
            'default'  => '50',
            'type'     => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min'  => '0'
            )
        );
        
        // Fin de la sección
        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'wc_enviamx_handling_fee_options'
        );
        
        return $settings;
    }
    
    /**
     * Campo de información personalizado
     */
    public function output_info_field($value) {
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php _e('Información', 'wc-enviamx-handling-fee'); ?>
            </th>
            <td class="forminp">
                <div style="background: #f8f9fa; border-left: 4px solid #2271b1; padding: 12px; margin: 10px 0;">
                    <p><strong>🚚 <?php _e('Métodos detectados:', 'wc-enviamx-handling-fee'); ?></strong></p>
                    <p><?php _e('El plugin detecta automáticamente métodos que contengan:', 'wc-enviamx-handling-fee'); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li>envia_mx</li>
                        <li>enviamx</li>
                        <li>envia-mx</li>
                        <li>envia</li>
                        <li>shipping_envia_mx</li>
                        <li>wc_envia_mx</li>
                        <li>woocommerce_envia_mx</li>
                    </ul>
                    <p><strong>💡 <?php _e('Nota:', 'wc-enviamx-handling-fee'); ?></strong></p>
                    <p><?php _e('El costo adicional se aplica de forma transparente y no es visible para los clientes.', 'wc-enviamx-handling-fee'); ?></p>
                    
                    <?php if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil') && 
                              \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled('custom_order_tables')) : ?>
                        <p style="color: #22bb33; font-weight: bold;">
                            ✅ <?php _e('Compatible con High-Performance Order Storage (HPOS)', 'wc-enviamx-handling-fee'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Agregar notificación de compatibilidad con HPOS
     */
    public function add_hpos_notice() {
        $current_screen = get_current_screen();
        
        // Mostrar solo en páginas relevantes de WooCommerce
        if ($current_screen && $current_screen->id === 'woocommerce_page_wc-settings') {
            if (isset($_GET['section']) && $_GET['section'] === 'wc_enviamx_handling_fee') {
                if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil') && 
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled('custom_order_tables')) {
                    ?>
                    <div class="notice notice-success">
                        <p>
                            <strong>✅ <?php _e('Compatible con HPOS', 'wc-enviamx-handling-fee'); ?></strong> 
                            <?php _e('Este plugin es totalmente compatible con High-Performance Order Storage.', 'wc-enviamx-handling-fee'); ?>
                        </p>
                    </div>
                    <?php
                }
            }
        }
    }
}