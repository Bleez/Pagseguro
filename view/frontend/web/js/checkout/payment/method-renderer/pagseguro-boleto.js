/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'ko',
        'mage/url',
        'jquery',
        'Magento_Checkout/js/checkout-data',
    ],
    function (Component, ko, urlBuilder, jQuery, checkoutData) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Bleez_Pagseguro/payment/pagseguro',
                userHash: ko.observable(),
            },

            getMsg: function(){
                return window.pagseguroMsgs.boleto;
            },

            isActive: function() {
                return true;
            },

            getCode: function() {
                return 'pagseguro_boleto';
            },

            getData: function() {
                var billing = checkoutData.getBillingAddressFromData();
                if(billing == null){
                    billing = checkoutData.getNewCustomerBillingAddress();
                }
                if(billing == null){
                    billing = checkoutData.getSelectedBillingAddress();
                }
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'userHash': window.pagseguro.userHash,
                    }
                };
            },
        });
    }
);
