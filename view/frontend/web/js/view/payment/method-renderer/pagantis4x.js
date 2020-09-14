define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        '//cdn.pagantis.com/js/pg-v2/sdk.js',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader, quote, pgSDK4x, selectPaymentMethodAction, checkoutData, totals, priceUtils) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Pagantis_Pagantis/payment/checkout-form4x'
            },

            redirectAfterPlaceOrder: false,

            loadSimulator4x: function () {
                window.loadingSimulator4x = setTimeout(function () {
                    if (window.checkoutConfig.payment.pagantis4x.enabled  !='0' &&
                        window.checkoutConfig.payment.pagantis4x.publicKey!=''  &&
                        window.checkoutConfig.payment.pagantis4x.product_simulator=='1') {
                        var locale = window.checkoutConfig.payment.pagantis4x.locale;
                        var sdk = pgSDK4x;

                        var simulator_options4x = {
                            numInstalments : window.checkoutConfig.payment.pagantis4x.quotesStart,
                            type : eval(window.checkoutConfig.payment.pagantis4x.type),
                            skin : eval(window.checkoutConfig.payment.pagantis4x.skin),
                            publicKey: window.checkoutConfig.payment.pagantis4x.publicKey,
                            selector: window.checkoutConfig.payment.pagantis4x.position,
                            totalAmount: window.checkoutConfig.payment.pagantis4x.total,
                            locale: window.checkoutConfig.payment.pagantis4x.locale,
                            country: window.checkoutConfig.payment.pagantis4x.country
                        };

                        if (typeof sdk !== 'undefined') {
                            window.MGSimulatorId4x = sdk.simulator.init(simulator_options4x);
                            return false;
                        }
                    }
                }, 3000);
            },

            getTitle: function () {
                return window.checkoutConfig.payment.pagantis4x.title
            },

            getSubtitle: function () {
                return window.checkoutConfig.payment.pagantis4x.subtitle
            },

            getDisplayMode: function () {
                return window.checkoutConfig.payment.pagantis4x.displayMode
            },

            getImage: function () {
                return window.checkoutConfig.payment.pagantis4x.image
            },

            placeOrder: function () {
                var paymentUrl = url.build('pagantis/Payment');

                var guestEmail = window.checkoutConfig.payment.pagantis.fullQuote.guestEmail;
                var product = 'pagantis4x';

                $.post(paymentUrl, { email: guestEmail, product: product }, 'json')
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