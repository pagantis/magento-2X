define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        '//js.afterpay.com/afterpay-1.x.js',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader, quote, cpSDK, selectPaymentMethodAction, checkoutData, totals, priceUtils) {
        'use strict';

        window.checkoutConfig.payment.clearpay.fullQuote = quote;

        return Component.extend({
            defaults: {
                template: 'Clearpay_Clearpay/payment/checkout-form'
            },

            redirectAfterPlaceOrder: false,

            getCurrency: function () {
                return window.checkoutConfig.payment.clearpay.currency
            },

            getLocale: function () {
                return window.checkoutConfig.payment.clearpay.locale
            },

            getTotalAmount: function () {
                return window.checkoutConfig.payment.clearpay.total
            },

            getTitle: function () {
                return window.checkoutConfig.payment.clearpay.title
            },

            getTitleExtra: function () {
                let title_extra = window.checkoutConfig.payment.clearpay.title_extra;
                document.getElementById('clearpay-header-span').innerText = title_extra;
                return true
            },

            getImage: function () {
                return window.checkoutConfig.payment.clearpay.image
            },

            getHeaderImage: function () {
                return window.checkoutConfig.payment.clearpay.header_image
            },

            getMoreInfoText: function () {
                let infotext = window.checkoutConfig.payment.clearpay.more_info1 +
                               window.checkoutConfig.payment.clearpay.more_info2 +
                               window.checkoutConfig.payment.clearpay.more_info3;
                document.getElementById('clearpay-more-info').innerText = infotext;
                return true;
            },

            getTCText: function () {
                let tctext= window.checkoutConfig.payment.clearpay.TCText;
                document.getElementById('clearpay-terms').innerText = tctext;
                return true;
            },

            getTCLink: function () {
                return window.checkoutConfig.payment.clearpay.TCLink
            },

            placeOrder: function () {
                var self = this;

                if (!this.validate()) {
                    return false;
                }

                var paymentUrl = url.build('clearpay/Payment');

                var guestEmail = window.checkoutConfig.payment.clearpay.fullQuote.guestEmail;

                $.post(paymentUrl, { email: guestEmail }, 'json')
                    .done(function (response) {
                        window.location.replace(response);
                        //console.log(response);
                    })
                    .fail(function (response) {
                        window.location.replace(response);
                        //console.log(response);
                    })
            },
        });
    }
);
