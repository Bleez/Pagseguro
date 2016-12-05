/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'jquery',
    ],
    function (Component, urlBuilder, jQuery) {
        'use strict';

        var redirectUrl = function(){
            var url = '';
            jQuery.ajax({
                url: urlBuilder.build('rest/V1/pagseguro/redirectStandard'),
                async: false,
                success: function(data){
                    url = data;
                }
            });
            return url;
        }

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Bleez_Pagseguro/payment/pagseguro',
            },

            getMsg: function(){
                return window.pagseguroMsgs.standard;
            },

            afterPlaceOrder: function () {
                var url = redirectUrl();
                if(url){
                    window.location.replace(url);
                }else{
                    alert('Houve um erro ao processar sua requisição tente novamente mais tarde');
                }
            }
        });
    }
);
