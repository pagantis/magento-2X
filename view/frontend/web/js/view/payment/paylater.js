define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'paylater',
                component: 'DigitalOrigin_Pmt/js/view/payment/method-renderer/paylater'
            }
        );
        return Component.extend({});
    }
);
