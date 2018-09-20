define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        '//cdn.pagamastarde.com/pmt-js-client-sdk/3/js/client-sdk.min.js',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader, quote, pmtClient, selectPaymentMethodAction, checkoutData, totals, priceUtils) {
        'use strict';

        window.checkoutConfig.payment.paylater.guestEmail = quote.guestEmail;

        window.pmtClient = pmtClient;

        return Component.extend({
                defaults: {
                    template: 'DigitalOrigin_Pmt/payment/checkout-form'
                },

                redirectAfterPlaceOrder: false,

                loadSimulator: function ()
                {
                    setTimeout(function(){
                        if (window.checkoutConfig.payment.paylater.pmtType  !='0' &&
                            window.checkoutConfig.payment.paylater.publicKey!=''  &&
                            window.checkoutConfig.payment.paylater.secretKey!='')
                        {
                            if (typeof window.pmtClient !== 'undefined')
                            {
                                window.pmtClient.setPublicKey(window.checkoutConfig.payment.paylater.publicKey);
                                window.pmtClient.simulator.reload();
                                return true;
                            }
                        }
                    }, 3000);
                },

                getSubtitle: function () {
                    return window.checkoutConfig.payment.paylater.subtitle
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
                    return priceUtils.formatPrice(totals.totals().grand_total, quote.getPriceFormat());
                    //return window.checkoutConfig.payment.paylater.pmtType
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

                selectPaymentMethod: function() {
                    selectPaymentMethodAction(this.getData());
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    return true;
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
                            window.location.replace(response);
                        })
                },
            });
    }
);
