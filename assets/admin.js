jQuery(document).ready(function($) {
    // Validación adicional para el campo de monto
    $('input[name="wc_enviamx_handling_fee_amount"]').on('change', function() {
        var amount = parseFloat($(this).val());
        var type = $('select[name="wc_enviamx_handling_fee_type"]').val();
        
        if (type === 'percentage' && amount > 100) {
            alert('Para porcentaje, el valor no puede ser mayor a 100%.');
            $(this).val('100');
        }
    });
    
    // Mostrar/ocultar descripción según el tipo
    $('select[name="wc_enviamx_handling_fee_type"]').on('change', function() {
        var description = $(this).closest('tr').next('tr').find('td .description');
        if ($(this).val() === 'percentage') {
            description.text('Ingresa el porcentaje (ej: 10 para 10%). Máximo 100%.');
        } else {
            description.text('Ingresa el monto fijo del costo de manejo.');
        }
    });
});