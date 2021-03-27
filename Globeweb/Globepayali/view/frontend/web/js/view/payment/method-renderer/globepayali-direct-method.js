/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Globeweb_Globepayali/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data'
    ],
    function (Component, $, setPaymentMethodAction, additionalValidators, quote, customerData) {
        'use strict';
 
        return Component.extend({
            defaults: {
                template: 'Globeweb_Globepayali/payment/globepayalidirect'
            },
            /** Redirect to Globepayali*/
            continueToGlobepayali: function () {
                if (additionalValidators.validate()) {
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(
                        function () {
                            customerData.invalidate(['cart']);
                            $.mage.redirect(window.checkoutConfig.payment.globepayalidirect.redirectUrl);
                        }
                    );
                    return false;
                }
            },
            getGlobepayaliLogoSrc: function () {
                return window.checkoutConfig.payment.globepayalidirect.globepayaliLogoUrl;
            },
        });
    }
);