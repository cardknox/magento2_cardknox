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
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/place-order'
], function (VaultComponent,additionalValidators,$,knockout,redirectOnSuccessActionVault,fullScreenLoaderVault,placeOrderActionVault) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'CardknoxDevelopment_Cardknox/payment/cardknox-vault-form'
        },
        isAllowDuplicateTransactionVault: knockout.observable(false),
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
            let data = {
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
            let isAllowDuplicateTransactionCkVault = false;
            if ($('#is_allow_duplicate_transaction_vault').length) {
                if($("#is_allow_duplicate_transaction_vault").prop('checked')){
                    isAllowDuplicateTransactionCkVault = true;
                }
            }
            return isAllowDuplicateTransactionCkVault;
        },
        /**
             * @return {*}
             */
        getPlaceOrderDeferredObject: function () {
            return $.when(
                placeOrderActionVault(this.getData(), this.messageContainer)
            );
        },
        /**
         * Place order.
         */
        placeOrder: function (data, event) {
            let self = this;

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
                                redirectOnSuccessActionVault.execute();
                            }
                        }
                    ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).fail(
                        function (response) {
                            self.isPlaceOrderActionAllowed(true);

                            let error_message = "Unable to process the order. Please try again.";
                            if (response?.responseJSON?.message) {
                                error_message = response.responseJSON.message;
                            }
                            self.showPaymentError(error_message);
                            if (error_message == 'Duplicate Transaction') {
                                self.isAllowDuplicateTransactionVault(true);
                            } else {
                                self.isAllowDuplicateTransactionVault(false);
                            }
                        }
                    );;

                return true;
            }

            return false;
        },
        showPaymentError: function (message) {
            $(".ck-vault-error").html("<div> "+message+" </div>").show();
            setTimeout(function () { 
                $(".ck-vault-error").html("").hide();
            }, 5000);

            fullScreenLoaderVault.stopLoader();
            $('.checkout-cart-index .loading-mask').attr('style','display:none');
        }
    });
});


