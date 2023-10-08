/*browser:true*/
/*global define*/

define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'GetnetArg_Payments/js/action/set-payment-method-action',
        'Magento_Customer/js/customer-data'
    ],
function (ko, $, Component, setPaymentMethodAction, customerData) {
    'use strict';
    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            logo: 'GetnetArg_Payments/images/icon_getnet_mini.png',
            template: 'GetnetArg_Payments/payment/argenmagento-form.html'
        },
        
        getLogoUrl: function() {
            return require.toUrl(this.logo);
        },

        afterPlaceOrder: function () {
                        customerData.invalidate(['cart']);
                        customerData.set('checkout-data', {
                            'selectedShippingAddress': null,
                            'shippingAddressFromData': null,
                            'newCustomerShippingAddress': null,
                            'selectedShippingRate': null,
                            'selectedPaymentMethod': null,
                            'selectedBillingAddress': null,
                            'billingAddressFromData': null,
                            'newCustomerBillingAddress': null
                        });
            setPaymentMethodAction(this.messageContainer);
            return false;
        }
    });        
    
}
);
