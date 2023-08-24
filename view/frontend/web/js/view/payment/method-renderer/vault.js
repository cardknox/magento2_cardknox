/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
define([
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Checkout/js/model/payment/additional-validators',
    "jquery",
    "ko",
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    VaultComponent,
    additionalValidators,
    $,
    ko,
    redirectOnSuccessAction,
    placeOrderAction,
    fullScreenLoader
) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'CardknoxDevelopment_Cardknox/payment/cardknox-vault-form'
        },
        isAllowDuplicateTransaction: ko.observable(false),
        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * Get public hash
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },
        /**
         * @returns {*}
         */
        getData: function () {
            var data = {
                method: this.getCode()
            };

            data['additional_data'] = {};
            data['additional_data']['public_hash'] = this.getToken();
            data['additional_data']['isAllowDuplicateTransaction'] = this.getAllowDuplicateTransactionVault();

            return data;
        },
        /**
             * @return {Boolean}
             */
        validate: function () {
            return true;
        },

        additionalValidator: function () {
            return additionalValidators.validate();
        },
        getAllowDuplicateTransactionVault: function () {
            var isAllowDuplicateTransactionApay = false;
            if ($('#is_allow_duplicate_transaction_vault').length) {
                if($("#is_allow_duplicate_transaction_vault").prop('checked') == true){
                    isAllowDuplicateTransactionApay = true;
                } else {
                    isAllowDuplicateTransactionApay = false;
                }
            }
            return isAllowDuplicateTransactionApay;
        },
        /**
             * @return {*}
             */
        getPlaceOrderDeferredObject: function () {
            return $.when(
                placeOrderAction(this.getData(), this.messageContainer)
            );
        },
        /**
         * Place order.
         */
        placeOrder: function (data, event) {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() &&
                additionalValidators.validate() &&
                this.isPlaceOrderActionAllowed() === true
            ) {
                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                    .done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).fail(
                        function (response) {
                            self.isPlaceOrderActionAllowed(true);

                            var error_message = "Unable to process the order. Please try again.";
                            if (response && response.responseJSON && response.responseJSON.message) {
                                error_message = response.responseJSON.message;
                            }
                            self.showPaymentError(error_message);
                            if (error_message == 'Duplicate Transaction') {
                                self.isAllowDuplicateTransaction(true);
                            } else {
                                self.isAllowDuplicateTransaction(false);
                            }
                        }
                    );;

                return true;
            }

            return false;
        },
        showPaymentError: function (message) {
            $(".applepay-error").html("<div> "+message+" </div>").show();
            setTimeout(function () { 
                $(".applepay-error").html("").hide();
            }, 5000);

            fullScreenLoader.stopLoader();
            $('.checkout-cart-index .loading-mask').attr('style','display:none');
        }
    });
});


