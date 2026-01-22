/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * Shared helper functions for Cardknox payment methods
 */
define([
    'Magento_Checkout/js/model/step-navigator'
], function (stepNavigator) {
    'use strict';

    return {
        /**
         * Check if cart has only virtual products
         * @returns {boolean}
         */
        isVirtualQuote: function () {
            return window.checkoutConfig.quoteData &&
                   (window.checkoutConfig.quoteData.is_virtual === '1' ||
                    window.checkoutConfig.quoteData.is_virtual === 1 ||
                    window.checkoutConfig.quoteData.is_virtual === true);
        },

        /**
         * Save shipping method before placing order
         */
        saveShippingMethod: function () {
            if (window.checkoutConfig.selectedShippingMethod) {
                window.cardknoxSavedShippingMethod = window.checkoutConfig.selectedShippingMethod;
            }

            // For virtual products, set a dummy shipping method to prevent redirect to shipping step on error
            if (this.isVirtualQuote() && !window.checkoutConfig.selectedShippingMethod) {
                window.checkoutConfig.selectedShippingMethod = 'virtual';
            }
        },

        /**
         * Force stay on payment step after duplicate transaction error
         * This prevents Magento from redirecting to shipping step
         */
        forceStayOnPayment: function () {
            var self = this;
            var isVirtual = this.isVirtualQuote();

            // Restore shipping method if it was cleared
            if (!window.checkoutConfig.selectedShippingMethod && window.cardknoxSavedShippingMethod) {
                window.checkoutConfig.selectedShippingMethod = window.cardknoxSavedShippingMethod;
            }

            // Find the payment step and force it to be visible
            var steps = stepNavigator.steps();
            steps.forEach(function (step) {
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

                // Monitor and prevent any navigation away from payment for 3 seconds
                var protectionInterval = setInterval(function () {
                    var currentHash = window.location.hash.replace('#', '');
                    if (currentHash !== 'payment') {
                        steps.forEach(function (step) {
                            if (step.code === 'payment') {
                                step.isVisible(true);
                            } else {
                                step.isVisible(false);
                            }
                        });
                        window.history.replaceState(null, null, targetUrl);
                    }
                }, 50);

                setTimeout(function () {
                    clearInterval(protectionInterval);
                }, 3000);
            } else {
                // For virtual products, just ensure payment is visible
                var protectionInterval = setInterval(function () {
                    steps.forEach(function (step) {
                        if (step.code === 'payment') {
                            step.isVisible(true);
                        } else {
                            step.isVisible(false);
                        }
                    });
                }, 50);

                setTimeout(function () {
                    clearInterval(protectionInterval);
                }, 3000);
            }
        }
    };
});
