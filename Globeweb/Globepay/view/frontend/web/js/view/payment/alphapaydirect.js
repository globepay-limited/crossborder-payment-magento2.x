/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */
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
                type: 'globepaydirect',
                component: 'Globeweb_Globepay/js/view/payment/method-renderer/globepay-direct-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);