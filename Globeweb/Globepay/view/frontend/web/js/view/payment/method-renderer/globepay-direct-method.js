/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Globeweb_Globepay/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data'
    ],
    function (Component, $, setPaymentMethodAction, additionalValidators, quote, customerData) {
        'use strict';
 
        return Component.extend({
            defaults: {
                template: 'Globeweb_Globepay/payment/globepaydirect'
            },
            /** Redirect to Globepay*/
            continueToGlobepay: function () {
                if (additionalValidators.validate()) {
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(
                        function () {
                            customerData.invalidate(['cart']);
                            $.mage.redirect(window.checkoutConfig.payment.globepaydirect.redirectUrl);
                        }
                    );
                    return false;
                }
            },
            getGlobepayLogoSrc: function () {
                return window.checkoutConfig.payment.globepaydirect.globepayLogoUrl;
            },
        });
    }
);