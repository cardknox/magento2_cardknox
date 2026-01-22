/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
define([
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Checkout/js/model/payment/additional-validators',
    "jquery",
    "ko",
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/step-navigator'
], function (VaultComponent,additionalValidators,$,knockout,redirectOnSuccessActionVault,fullScreenLoaderVault,placeOrderActionVault,stepNavigator) {
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
            if ($("#is_allow_duplicate_transaction_"+this.index).length) {
                if($("#is_allow_duplicate_transaction_"+this.index).prop('checked')){
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

            // Save shipping method before placing order
            if (window.checkoutConfig.selectedShippingMethod) {
                window.cardknoxSavedShippingMethod = window.checkoutConfig.selectedShippingMethod;
            }

            // For virtual products, set a dummy shipping method to prevent redirect to shipping step on error
            // This is needed because Magento's payment.js navigate() checks hasShippingMethod()
            var isVirtual = window.checkoutConfig.quoteData &&
                           (window.checkoutConfig.quoteData.is_virtual === '1' ||
                            window.checkoutConfig.quoteData.is_virtual === 1 ||
                            window.checkoutConfig.quoteData.is_virtual === true);

            if (isVirtual && !window.checkoutConfig.selectedShippingMethod) {
                window.checkoutConfig.selectedShippingMethod = 'virtual';
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
                            if (error_message.startsWith('Duplicate Transaction')) {
                                self.isAllowDuplicateTransactionVault(true);
                                // Prevent redirect to shipping section on duplicate transaction error
                                // Force payment step to be visible and stay on payment
                                self.forceStayOnPayment();
                            } else {
                                self.isAllowDuplicateTransactionVault(false);
                            }
                        }
                    );

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
        },
        /**
             * @returns {String}
             */
        getIdAllowDuplicateTransaction: function () {
            return "is_allow_duplicate_transaction_"+this.index;
        },

        /**
         * Force stay on payment step after duplicate transaction error
         * This prevents Magento from redirecting to shipping step
         */
        forceStayOnPayment: function () {
            var self = this;
            var isVirtual = window.checkoutConfig.quoteData &&
                           (window.checkoutConfig.quoteData.is_virtual === '1' ||
                            window.checkoutConfig.quoteData.is_virtual === 1 ||
                            window.checkoutConfig.quoteData.is_virtual === true);

            // Store the current shipping method to prevent hasShippingMethod() from returning false
            if (!window.checkoutConfig.selectedShippingMethod && window.cardknoxSavedShippingMethod) {
                window.checkoutConfig.selectedShippingMethod = window.cardknoxSavedShippingMethod;
            }

            // Find the payment step and force it to be visible
            var steps = stepNavigator.steps();
            steps.forEach(function(step) {
                if (step.code === 'payment') {
                    step.isVisible(true);
                } else {
                    step.isVisible(false);
                }
            });

            // For virtual products, don't change the hash - just keep payment visible
            // For non-virtual products, set hash to payment
            if (!isVirtual) {
                var baseUrl = window.location.origin + window.location.pathname;
                var targetUrl = baseUrl + '#payment';

                if (window.location.hash !== '#payment') {
                    window.history.replaceState(null, null, targetUrl);
                }

                // Monitor and prevent any navigation away from payment for 3 seconds (non-virtual only)
                var protectionInterval = setInterval(function() {
                    var currentHash = window.location.hash.replace('#', '');
                    if (currentHash !== 'payment') {
                        steps.forEach(function(step) {
                            if (step.code === 'payment') {
                                step.isVisible(true);
                            } else {
                                step.isVisible(false);
                            }
                        });
                        window.history.replaceState(null, null, targetUrl);
                    }
                }, 50);

                setTimeout(function() {
                    clearInterval(protectionInterval);
                }, 3000);
            } else {
                // For virtual products, just ensure payment is visible and prevent any navigation
                var protectionInterval = setInterval(function() {
                    steps.forEach(function(step) {
                        if (step.code === 'payment') {
                            step.isVisible(true);
                        } else {
                            step.isVisible(false);
                        }
                    });
                }, 50);

                setTimeout(function() {
                    clearInterval(protectionInterval);
                }, 3000);
            }
        }
    });
});
