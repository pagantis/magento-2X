define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote'
    ],
    function ($,
              Component,
              redirectOnSuccessAction,
              setPaymentInformationAction,
              additionalValidators,
              quote
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'DigitalOrigin_Pmt/payment/checkout-form'
            },

            redirectAfterPlaceOrder: false,

            /**
             * @override PlaceOrder
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (additionalValidators.validate()) {
                    $.When(setPaymentInformationAction(this.messageContainer, {
                        'method': self.getCode()
                    })).done(this.refresh('https://google.es'))
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        );
                }
            }
        });
    }
);