/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'uiComponent',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'mage/translate',
    'Magento_Sales/order/create/scripts'
], function ($, Class, alert, domObserver, $t, validator) {
    'use strict';

    return Class.extend({

        defaults: {
            $selector: null,
            selector: 'edit_form',
            container: 'payment_form_cardknox',
            active: false,
            scriptLoaded: false,
            imports: {
                onActiveChange: 'active'
            }
        },

        /**
         * Set list of observable attributes
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            var self = this;
            self.$selector = $('#' + self.selector);
            this._super()
                .observe([
                    'active',
                    'scriptLoaded'
                ]);
            // re-init payment method events
            self.$selector.off('changePaymentMethod.' + this.code)
                .on('changePaymentMethod.' + this.code, this.changePaymentMethod.bind(this));
            // listen block changes
            domObserver.get('#' + self.container, function () {
                if (!self.scriptLoaded()) {
                    self.loadScript();
                }
            });
            self.enableEventListeners();
            return this;
        },

        /**
         * Enable/disable current payment method
         * @param {Object} event
         * @param {String} method
         * @returns {exports.changePaymentMethod}
         */
        changePaymentMethod: function (event, method) {
            this.active(method === this.code);
            return this;
        },

        /**
         * Triggered when payment changed
         * @param {Boolean} isActive
         */
        onActiveChange: function (isActive) {
            if (!isActive) {
                return;
            }
            if (!this.scriptLoaded()) {
                this.initCardknox()
            }
        },

        /**
         * Load external Cardknox SDK
         */
        loadScript: function () {
            var self = this;
            self.initCardknox();
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

        /**
         * Setup Cardknox SDK
         */
        initCardknox: function () {
            var self = this;
            try {
                enableLogging();
                setAccount(this.tokenKey, "Magento2", "1.2.71");
                enableAutoFormatting();
                setIfieldStyle('card-number', self.defaultStyle);
                setIfieldStyle('cvv', self.defaultStyle);
                addIfieldCallback('input', function(data) {

                    if (data.ifieldValueChanged) {
                        setIfieldStyle('card-number', data.cardNumberFormattedLength <= 0 ? self.defaultStyle : data.cardNumberIsValid ? self.validStyle : self.invalidStyle);
                        if (data.lastIfieldChanged === 'cvv'){
                            setIfieldStyle('cvv', data.issuer === 'unknown' || data.cvvLength <= 0 ? self.defaultStyle : data.cvvIsValid ? self.validStyle : self.invalidStyle);
                        } else if (data.lastIfieldChanged === 'card-number') {
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
            } catch (e) {
                $('body').trigger('processStop');
                self.error(e.message);
            }
        },


        /**
         * Show alert message
         * @param {String} message
         */
        error: function (message) {
            alert({
                content: message
            });
        },

        /**
         * Enable form event listeners
         */
        enableEventListeners: function () {
            var self = this;
            AdminOrder.prototype.submit = function () {
                // validate parent form
                self.$selector.validate().form();
                self.$selector.trigger('afterValidate.beforeSubmit');
                if (self.$selector.validate().errorList.length) {
                    return false;
                }
                if (window.order.paymentMethod == "cardknox") {
                    $('body').trigger('processStart');
                    self.submitOrder();
                } else {
                    self.placeOrder();
                }
            };
        },

        /**
         * Trigger order submit
         */
        submitOrder: function () {
            var self = this;
            getTokens(
                function () {
                    if (document.getElementsByName("payment[xCardNum]")[0].value === '') {
                        self.error("Card Number Required");
                        $('body').trigger('processStop');
                        return false
                    }
                    if (document.getElementsByName("payment[xCVV]")[0].value === '') {
                        self.error("CVV Required");
                        $('body').trigger('processStop');
                        return false
                    }
                    self.placeOrder();
                },
                function () {
                //onError
                    self.error(document.getElementById('ifieldsError').textContent);
                    $('body').trigger('processStop');
                },
                30000
            );
        },

        /**
         * Place order
         */
        placeOrder: function () {
            $('#' + this.selector).trigger('realOrder');
        },

        /**
         * Get jQuery selector
         * @param {String} field
         * @returns {String}
         */
        getSelector: function (field) {
            return '#' + this.code + '_' + field;
        }
    });
});
