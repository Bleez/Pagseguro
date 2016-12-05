/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            'jquery',
            'Magento_Payment/js/model/credit-card-validation/cvv-validator'
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {
    "use strict";

    $.each({
        'validate-card-number-pagseguro': [
            function (value, el, params) {
                var paramValidate = JSON.parse($(el).attr('data-validate'));

                var paramName = 'validate-card-number-pagseguro';

                if(paramValidate[paramName].token != null){
                    return true;
                }
                return false;
            },
            'Cartão ou CVV Inválidos.'
        ]
    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
}));