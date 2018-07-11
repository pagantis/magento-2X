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
                    if (window.checkoutConfig.payment.paylater.pmtType  !='0' &&
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

            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            /**
             * @override placeOrder function:
             */
            placeOrder: function (data, event) { //data or this => UiClass  - Event JqueryEvent

                var self = this;

                if (event) {
                    event.preventDefault();
                }

                var paymentUrl = url.build('paylater/Payment'); //http://magento2.docker:8086/index.php/paylater/Payment

                $.post(paymentUrl, 'json')
                    .done(function (response) {
                        console.log(response);
                        window.location.replace(response);
                    })
                    .fail(function (response) {
                        console.log('FAIL CASE');
                    })
                    .always(function () {
                        console.log('ALWAYS CASE');
                        fullScreenLoader.stopLoader();
                    });
            }
        });
    }
);
