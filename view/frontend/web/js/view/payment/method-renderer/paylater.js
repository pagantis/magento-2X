define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        '//cdn.pagamastarde.com/pmt-js-client-sdk/3/js/client-sdk.min.js'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader, quote, pmtClient) {
        'use strict';

        window.checkoutConfig.payment.paylater.guestEmail = quote.guestEmail;

        window.pmtClient = pmtClient;

        return Component.extend({
                defaults: {
                    template: 'DigitalOrigin_Pmt/payment/checkout-form'
                },

                redirectAfterPlaceOrder: false,

                loadSimulator: function () {
                    if (window.checkoutConfig.payment.paylater.pmtType  !='0' &&
                        window.checkoutConfig.payment.paylater.publicKey!=''  &&
                        window.checkoutConfig.payment.paylater.secretKey!='') {
                        if (typeof window.pmtClient !== 'undefined') {
                            window.pmtClient.setPublicKey(window.checkoutConfig.payment.paylater.publicKey);
                            window.pmtClient.simulator.reload();
                            return true;
                        }
                    }
                },

                getPmtNumQuota: function () {
                    return window.checkoutConfig.payment.paylater.pmtNumQuota
                },

                dataPmtMaxIns: function () {
                    return window.checkoutConfig.payment.paylater.pmtMaxIns
                },

                getPmtType: function () {
                    return window.checkoutConfig.payment.paylater.pmtType
                },

                getPmtTotal: function () {
                    return window.checkoutConfig.payment.paylater.total
                },

                getPublicKey: function () {
                    return window.checkoutConfig.payment.paylater.publicKey
                },

                getSecretKey: function () {
                    return window.checkoutConfig.payment.paylater.secretKey
                },

                getDisplayMode: function () {
                    return window.checkoutConfig.payment.paylater.displayMode
                },

                placeOrder: function () {
                    var paymentUrl = url.build('paylater/Payment');
                    $.post(paymentUrl, { email: window.checkoutConfig.payment.paylater.guestEmail }, 'json')
                        .done(function (response) {
                            console.log(response);
                            window.location.replace(response);
                        })
                        .fail(function (response) {
                            console.log(response);
                            //window.location.replace(response);
                        })
                },
            });
    }
);
