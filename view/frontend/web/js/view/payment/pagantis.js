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
                type: 'pagantis',
                component: 'Pagantis_Pagantis/js/view/payment/method-renderer/pagantis'
            },
            {
                type: 'pagantis4x',
                component: 'Pagantis_Pagantis/js/view/payment/method-renderer/pagantis4x'
            }
        );
        return Component.extend({});
    }
);
