require(['jquery'], function($) {
    var $deliveryAddress = $('#delivery-address');
    $('#ekyna_cart_step_information_sameAddress').on('change', function() {
        if ($(this).prop('checked')) {
            $deliveryAddress.hide();
        } else {
            $deliveryAddress.show();
        }
    }).trigger('change');
});