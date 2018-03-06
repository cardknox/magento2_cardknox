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
        'Magento_Ui/js/model/messageList'
    ],
    function (Component, $, v, i,fullScreenLoader,placeOrderAction,messageList) {
        'use strict';

        return Component.extend({
            /**
             * @returns {exports.initialize}
             */
            defaults: {
                template: 'CardknoxDevelopment_Cardknox/payment/cardknox-form'
            },

            initialize: function () {
                this._super();
                this.initCardknox();
                return this;
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
                return data;
            },

            isActive: function () {
                return true;
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            initCardknox: function () {
                var self = this;
                setAccount(window.checkoutConfig.payment.cardknox.tokenKey, "Magento2", "0.1.2");
                var style = {
                    border: '1px solid #adadad',
                    'font-size': '14px',
                    padding: '3px',
                    width: '145px',
                    height: '25px'
                };
                //
                setIfieldStyle('card-number', style);
                setIfieldStyle('cvv', style);
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
                if (this.validate()) {
                    self.isPlaceOrderActionAllowed(false);
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
                        //5 second timeout
                        5000
                    );
                } else {
                }
            },

            /**
             * Show error message
             * @param {String} errorMessage
             */
            showError: function (errorMessage) {
                messageList.addErrorMessage({
                    message: errorMessage
                });
            }
        });
    }
);