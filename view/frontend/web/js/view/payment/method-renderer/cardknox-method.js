/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'ifields',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/model/messageList',
        'Magento_Vault/js/view/payment/vault-enabler',
        'ko'
    ],
    function (Component, $, v, i,fullScreenLoader,placeOrderAction,messageList,VaultEnabler, ko) {
        'use strict';
        return Component.extend({
            cardNumberIsValid: ko.observable(false),
            cvvIsValid:  ko.observable(false),
            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                
                this._super();
                this.initCardknox();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                return this;
            },
            defaults: {
                template: 'CardknoxDevelopment_Cardknox/payment/cardknox-form'
            },
            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            getCode: function () {
                return 'cardknox';
            },
            /**
             * Get data
             *
             * @returns {Object}
             */
            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'xCVV': document.getElementsByName("xCVV")[0].value,
                        'xCardNum': document.getElementsByName("xCardNum")[0].value
                    }
                };
                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
                this.vaultEnabler.visitAdditionalData(data);
                return data;
            },
            isActive: function () {
                return true;
            },
            validate: function () {
                var self = this;
                var $form = $('#' + self.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            defaultStyle: {
                border: '1px solid #adadad',
                'font-size': '14px',
                padding: '3px',
                width: '145px',
                height: '25px'
            },
            validStyle: {
                border: '2px solid green',
                'font-size': '14px',
                padding: '3px',
                width: '145px',
                height: '25px'
            },
            invalidStyle: {
                border: '2px solid red',
                'font-size': '14px',
                padding: '3px',
                width: '145px',
                height: '25px'
            },
            validateCardIfPresent: function(data) {
                return data.cardNumberFormattedLength <= 0 || data.cardNumberIsValid ? true : false;            
            },
            validateCVVIfPresent: function(data) {
               return data.issuer === 'unknown' || data.cvvLength <= 0 || data.cvvIsValid ? true : false;     
            },
            validateCVVLengthIfPresent: function(data) {
                if (data.issuer === 'unknown' || data.cvvLength <= 0) {
                    return true;
                } else if (data.issuer === 'amex'){
                    return data.cvvLength === 4 ? true : false;
                } else {
                    return data.cvvLength === 3 ? true : false;
                }
            },
            isEnabledReCaptcha: function () {
                if (window.checkoutConfig.payment.cardknox.isEnabledReCaptcha == 1) {
                    return true; 
                } else {
                    return false;
                }
            },
            getSiteKeyV2: function () {
                return window.checkoutConfig.payment.cardknox.googleReCaptchaSiteKey;
            },
            onloadCallback: function () {
                var cardknox_recaptcha_widget;
                setTimeout(function(){ 
                    if($('#cardknox_recaptcha').length) {
                        cardknox_recaptcha_widget = grecaptcha.render('cardknox_recaptcha', {
                          'sitekey' : window.checkoutConfig.payment.cardknox.googleReCaptchaSiteKey
                        });
                    }
                }, 2000);
                return cardknox_recaptcha_widget;
            },
            initCardknox: function () {
                var self = this;
                enableLogging();
                enableAutoFormatting();
                setAccount(window.checkoutConfig.payment.cardknox.tokenKey, "Magento2", "1.0.12");
                setIfieldStyle('card-number', self.defaultStyle);
                setIfieldStyle('cvv', self.defaultStyle);
                this.onloadCallback();
                require(['https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit']);
                addIfieldCallback('input', function(data) {
                    if (data.ifieldValueChanged) {
                        self.cardNumberIsValid(self.validateCardIfPresent(data));
                        setIfieldStyle('card-number', data.cardNumberFormattedLength <= 0 ? self.defaultStyle : data.cardNumberIsValid ? self.validStyle : self.invalidStyle);
                        if (data.lastIfieldChanged === 'cvv'){
                            self.cvvIsValid(self.validateCVVIfPresent(data));
                            setIfieldStyle('cvv', data.issuer === 'unknown' || data.cvvLength <= 0 ? self.defaultStyle : data.cvvIsValid ? self.validStyle : self.invalidStyle);
                        } else if (data.lastIfieldChanged === 'card-number') {
                            self.cvvIsValid(self.validateCVVLengthIfPresent(data));
                            if (data.issuer === 'unknown' || data.cvvLength <= 0) {
                                setIfieldStyle('cvv', self.defaultStyle);
                            } else if (data.issuer === 'amex'){
                                setIfieldStyle('cvv', data.cvvLength === 4 ? self.validStyle : self.invalidStyle);
                            } else {
                                setIfieldStyle('cvv', data.cvvLength === 3 ? self.validStyle : self.invalidStyle);
                            }
                        }
                    }
                });
            },
            /**
             * Prepare data to place order
             * @param {Object} data
             */
            PlaceOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this;
                var isEnabledGoogleReCaptcha = this.isEnabledReCaptcha();
                if (isEnabledGoogleReCaptcha == true){
                    var captchResponse = $('#g-recaptcha-response').val();
                    if(captchResponse.length == 0 ){
                        $(".recaptcha-error").show();
                        return;
                    } else {
                        $(".recaptcha-error").hide();
                    }
                }
                if (self.validate()) {
                    self.isPlaceOrderActionAllowed(false);
                    if (!self.cardNumberIsValid() || !self.cvvIsValid()) {
                        self.showError(!this.cardNumberIsValid() ? "Invalid card" : "Invalid CVV");  
                        self.isPlaceOrderActionAllowed(true); 
                        return false;
                    }
                    getTokens(
                        function () {
                            //onSuccess
                            //perform your own validation here...
                            if (document.getElementsByName("xCardNum")[0].value === '') {
                                self.showError("Card Number Required");
                                self.isPlaceOrderActionAllowed(true);
                                return false
                            }
                            if (document.getElementsByName("xCVV")[0].value === '') {
                                self.showError("CVV Required");
                                self.isPlaceOrderActionAllowed(true);
                                return false
                            }
                            self.isPlaceOrderActionAllowed(true);
                            return self.placeOrder('parent');
                        },
                        function () { 
                            //onError
                            self.showError(document.getElementById('ifieldsError').textContent);
                            self.isPlaceOrderActionAllowed(true);
                        },
                        //30 second timeout
                        30000
                    );
                    return false
                } else {
                    return false
                }
            },
            /**
             * Show error message
             * @param {String} errorMessage
             */
            showError: function (errorMessage) {
                let statusElement = document.getElementById('transaction-status');
                statusElement.innerHTML = errorMessage;
                statusElement.style.color = "red";
                statusElement.focus();
            },
            /**
             * @returns {Bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },
            /**
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
            }
        });
    }
);