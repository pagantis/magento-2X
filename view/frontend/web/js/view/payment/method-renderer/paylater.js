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
        require.config({
            paths: { "pmtSdk": "https://cdn.pagamastarde.com/pmt-js-client-sdk/3/js/client-sdk.min"},
            waitSeconds: 40
        });

        require( ["jquery","pmtSdk"],
            function ($, pmtClient) {
                $(document).ready(function() {
                    console.log(window.checkoutConfig.payment.paylater.pmtType);
                    if (window.checkoutConfig.payment.paylater.pmtType != '0' &&
                        window.checkoutConfig.payment.paylater.publicKey!='') {
                        if (typeof pmtClient !== 'undefined') {
                            pmtClient.setPublicKey(window.checkoutConfig.payment.paylater.publicKey);
                            pmtClient.simulator.reload();
                        }
                    }
                })
            }
        );

        return Component.extend({
            defaults: {
                template: 'DigitalOrigin_Pmt/payment/checkout-form'
            },

            redirectAfterPlaceOrder: false,

            getPmtNumQuota: function() {
                return  window.checkoutConfig.payment.paylater.pmtNumQuota
            },

            dataPmtMaxIns: function() {
                return  window.checkoutConfig.payment.paylater.pmtMaxIns
            },

            getPmtType: function() {
                return  window.checkoutConfig.payment.paylater.pmtType
            },

            getPmtTotal: function() {
                return  window.checkoutConfig.payment.paylater.total
            },

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
