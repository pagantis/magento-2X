define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader, quote, selectPaymentMethodAction, checkoutData, totals, priceUtils) {
        'use strict';

        window.checkoutConfig.payment.paylater.guestEmail = quote.guestEmail;

        return Component.extend({
                defaults: {
                    template: 'DigitalOrigin_Pmt/payment/checkout-form'
                },

                redirectAfterPlaceOrder: false,

                getTitle: function () {
                    return window.checkoutConfig.payment.paylater.title
                },

                getSubtitle: function () {
                    return window.checkoutConfig.payment.paylater.subtitle
                },

                getDisplayMode: function () {
                    return window.checkoutConfig.payment.paylater.displayMode
                },

                selectPaymentMethod: function() {
                    selectPaymentMethodAction(this.getData());
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    return true;
                },

                placeOrder: function () {
                    var paymentUrl = url.build('paylater/Payment');
                    $.post(paymentUrl, { email: window.checkoutConfig.payment.paylater.guestEmail }, 'json')
                        .done(function (response) {
                            window.location.replace(response);
                        })
                        .fail(function (response) {
                            window.location.replace(response);
                        })
                },
            });
    }
);
