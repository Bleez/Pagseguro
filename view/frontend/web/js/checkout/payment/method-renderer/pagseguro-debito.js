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
                template: 'Bleez_Pagseguro/payment/pagseguro_debito',
                bank: ko.observable(),
                bancos: [
                    {
                        label: 'Escolha o Banco', value : ''
                    },
                    {
                        label: 'Bradesco', value : 'bradesco'
                    },
                    {
                        label: 'Itau', value : 'itau'
                    },
                    {
                        label: 'Banco do Brasil', value : 'bancodobrasil'
                    },
                    {
                        label: 'Banrisul', value : 'banrisul'
                    },
                    {
                        label: 'HSBC', value : 'hsbc'
                    }
                ],
                userHash: ko.observable(),
            },

            getMsg: function(){
                return window.pagseguroMsgs.debito;
            },

            isActive: function() {
                return true;
            },

            getCode: function() {
                return 'pagseguro_debito';
            },

            validate: function() {
                var $form = jQuery('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            getData: function() {
                var billing = checkoutData.getShippingAddressFromData();
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'userHash': window.pagseguro.userHash,
                        'banco': this.bank()
                    }
                };
            },
        });
    }
);
