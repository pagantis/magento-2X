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

        window.checkoutConfig.payment.pagantis.guestEmail = quote.guestEmail;

        return Component.extend({
                defaults: {
                    template: 'DigitalOrigin_Pmt/payment/checkout-form'
                },

                redirectAfterPlaceOrder: false,

                getTitle: function () {
                    return window.checkoutConfig.payment.pagantis.title
                },

                getSubtitle: function () {
                    return window.checkoutConfig.payment.pagantis.subtitle
                },

                getDisplayMode: function () {
                    return window.checkoutConfig.payment.pagantis.displayMode
                },

                getImage: function () {
                    return window.checkoutConfig.payment.pagantis.image
                },

                selectPaymentMethod: function() {
                    selectPaymentMethodAction(this.getData());
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    return true;
                },

                placeOrder: function () {
                    var paymentUrl = url.build('pagantis/Payment');
                    $.post(paymentUrl, { email: window.checkoutConfig.payment.pagantis.guestEmail }, 'json')
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
