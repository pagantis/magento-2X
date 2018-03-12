define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'DigitalOrigin_Pmt/payment/checkout-form'
            },

            redirectAfterPlaceOrder: false,

            /**
             * @override placeOrder function:
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                var paymentUrl = url.build('paylater/payment');

                $.post(paymentUrl, 'json')
                    .done(function (response) {
                        window.location.replace(response);
                    })
                    .fail(function (response) {
                        //TODO handle errors
                    })
                    .always(function () {
                        fullScreenLoader.stopLoader();
                    });
            }
        });
    }
);