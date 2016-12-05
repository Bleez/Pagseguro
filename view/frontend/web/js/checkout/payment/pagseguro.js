define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'mage/url',
        'jquery',
    ],
    function (
        Component,
        rendererList,
        urlBuilder,
        jQuery
    ) {
        'use strict';

        if(window.pagseguroMsgs) {
            var ajaxurl = urlBuilder.build("rest/V1/pagseguro/getSessionId");

            window.pagseguro = {};

            jQuery.getJSON(ajaxurl, function (data) {
                PagSeguroDirectPayment.setSessionId(data);
                window.pagseguro.userHash = PagSeguroDirectPayment.getSenderHash();
            });
        }

        rendererList.push(
            {
                type: 'pagseguro_standard',
                component: 'Bleez_Pagseguro/js/checkout/payment/method-renderer/pagseguro-standard'
            },
            {
                type: 'pagseguro_cc',
                component: 'Bleez_Pagseguro/js/checkout/payment/method-renderer/pagseguro-cc'
            },
            {
                type: 'pagseguro_debito',
                component: 'Bleez_Pagseguro/js/checkout/payment/method-renderer/pagseguro-debito'
            },
            {
                type: 'pagseguro_boleto',
                component: 'Bleez_Pagseguro/js/checkout/payment/method-renderer/pagseguro-boleto'
            }
        );
        return Component.extend({});
    }
);
