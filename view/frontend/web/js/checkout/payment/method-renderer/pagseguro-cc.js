/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'underscore',
        'ko',
        'Magento_Payment/js/view/payment/cc-form',
        'mage/url',
        'jquery',
        'mask',
        'Bleez_Pagseguro/js/checkout/payment/validator',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Checkout/js/model/quote',
    ],
    function (_, ko, Component, urlBuilder, jQuery, mask, validator, cardNumberValidator, quote) {
        'use strict';

        ko.bindingHandlers.mask = {
            init: function (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
                var mask = valueAccessor();
                jQuery(element).mask(mask);
            }
        };

        return Component.extend({
            brandc: null,
            defaults: {
                parcelasItems: ko.observableArray([{ value: "", label: "Informe os dados do cartão para calcular as parcelas" }]),
                ccToken: ko.observable(null),
                userHash: ko.observable(),
                ccOwner: ko.observable(),
                ccParcelas: ko.observable(),
                ccCpf: ko.observable(),
                ccDdd: ko.observable(),
                ccTelefone: ko.observable(),
                ccDob: ko.observable(),
                parcelaAmount: ko.observable(),
                showBrand: ko.observable(false),
                brandUrl: ko.observable(),
                template: 'Bleez_Pagseguro/payment/pagseguro_cc',
                brandc : null,
            },

            isActive: function() {
                return true;
            },

            getCode: function() {
                return 'pagseguro_cc';
            },

            changeBrand:  function(){
                this.showBrand(true);
                this.brandUrl('https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/42x20/'+this.brandc+'.png');
            },

            initialize: function() {
                this._super();
                this.origParcelas = this.parcelasItems;
                var self = this;
                var lastBrand = null;
                var ccIsValid = false;

                this.creditCardNumber.subscribe(function(value) {
                    if(lastBrand != self.brandc || lastBrand == null){
                        PagSeguroDirectPayment.getBrand({
                            cardBin: self.creditCardNumber(),
                            success: function(response){
                                self.brandc = response.brand.name;
                                self.changeBrand();
                                lastBrand = self.brandc;
                                PagSeguroDirectPayment.getInstallments({
                                    amount: quote.totals().grand_total,
                                    brand: response.brand.name,
                                    success: function(data){
                                        self.parcelasItems.removeAll();
                                        var parcelas = {value: '', label: "Escolha a quantidade de parcelas"}
                                        self.parcelasItems.push(parcelas);
                                        _.each(data.installments[self.brandc], function(element, index, list){
                                            var novaParcela = {value : element.quantity};
                                            if(element.interestFree){
                                                novaParcela.label = element.quantity+'x de RS'+parseFloat(Math.round(element.installmentAmount * 100) / 100).toFixed(2)+' sem Juros';
                                            }else{
                                                novaParcela.label = element.quantity+'x de RS'+parseFloat(Math.round(element.installmentAmount * 100) / 100).toFixed(2)+' com Juros';
                                            }
                                            novaParcela.amount = element.installmentAmount;
                                            self.parcelasItems.push(novaParcela);
                                        });
                                    },
                                    error: function(data){
                                        self.parcelasItems = self.origParcelas;
                                    }
                                });
                            },
                        });
                    }
                    self.getCCtoken();
                });

                this.creditCardExpYear.subscribe(function(value) {
                    self.getCCtoken();
                });

                this.creditCardExpMonth.subscribe(function(value) {
                    self.getCCtoken();
                });

                this.creditCardVerificationNumber.subscribe(function(value) {
                    self.getCCtoken();
                });

                this.ccParcelas.subscribe(function(value) {
                    self.getCCtoken();
                    if(value != '' && typeof value != 'undefined'){
                        var parcelaSelected = _.findWhere(self.parcelasItems(), {value: value});
                        self.parcelaAmount(parcelaSelected.amount);
                    }else{
                        self.parcelaAmount('');
                    }
                });
            },

            validate: function() {
                if(this.ccToken() == null){
                    alert('Cartão ou CVV inválidos');
                    return false;
                }
                var $form = jQuery('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_ss_start_month': this.creditCardSsStartMonth(),
                        'cc_ss_start_year': this.creditCardSsStartYear(),
                        'cc_type': this.brandc,
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_owner': this.ccOwner(),
                        'cc_parcelas': this.ccParcelas(),
                        'cc_cpf': this.ccCpf(),
                        'cc_ddd': this.ccDdd(),
                        'cc_telefone': this.ccTelefone(),
                        'cc_dob': this.ccDob(),
                        'cc_token': this.ccToken(),
                        'parcela_amount': this.parcelaAmount(),
                        'userHash': window.pagseguro.userHash
                    }
                };
            },

            getCCtoken: function(){
                var self = this;
                PagSeguroDirectPayment.createCardToken({
                    cardNumber: self.creditCardNumber(),
                    brand: self.brandc,
                    cvv: self.creditCardVerificationNumber(),
                    expirationMonth: self.creditCardExpMonth(),
                    expirationYear: self.creditCardExpYear(),
                    success: function(data){
                        self.ccToken(data.card.token);
                        return true;
                    },error: function(data){
                        self.ccToken(null);
                        return false;
                    }
                });
            }
        });
    }
);
