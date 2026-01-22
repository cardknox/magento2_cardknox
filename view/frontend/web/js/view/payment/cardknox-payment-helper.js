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

    /**
     * Set payment step visible and hide others
     * @param {Array} steps
     */
    const setPaymentStepVisible = function (steps) {
        steps.forEach(function (step) {
            if (step.code === 'payment') {
                step.isVisible(true);
            } else {
                step.isVisible(false);
            }
        });
    };

    return {
        /**
         * Check if cart has only virtual products
         * @returns {boolean}
         */
        isVirtualQuote: function () {
            const quoteData = globalThis.checkoutConfig?.quoteData;
            return quoteData?.is_virtual === '1' ||
                   quoteData?.is_virtual === 1 ||
                   quoteData?.is_virtual === true;
        },

        /**
         * Save shipping method before placing order
         */
        saveShippingMethod: function () {
            if (globalThis.checkoutConfig?.selectedShippingMethod) {
                globalThis.cardknoxSavedShippingMethod = globalThis.checkoutConfig.selectedShippingMethod;
            }

            // For virtual products, set a dummy shipping method to prevent redirect to shipping step on error
            if (this.isVirtualQuote() && !globalThis.checkoutConfig?.selectedShippingMethod) {
                globalThis.checkoutConfig.selectedShippingMethod = 'virtual';
            }
        },

        /**
         * Force stay on payment step after duplicate transaction error
         * This prevents Magento from redirecting to shipping step
         */
        forceStayOnPayment: function () {
            const isVirtual = this.isVirtualQuote();

            // Restore shipping method if it was cleared
            if (!globalThis.checkoutConfig?.selectedShippingMethod && globalThis.cardknoxSavedShippingMethod) {
                globalThis.checkoutConfig.selectedShippingMethod = globalThis.cardknoxSavedShippingMethod;
            }

            // Find the payment step and force it to be visible
            const steps = stepNavigator.steps();
            setPaymentStepVisible(steps);

            let protectionInterval;

            // For virtual products, don't change the hash - just keep payment visible
            // For non-virtual products, set hash to payment
            if (!isVirtual) {
                const baseUrl = globalThis.location.origin + globalThis.location.pathname;
                const targetUrl = baseUrl + '#payment';

                if (globalThis.location.hash !== '#payment') {
                    globalThis.history.replaceState(null, null, targetUrl);
                }

                // Monitor and prevent any navigation away from payment for 3 seconds
                protectionInterval = setInterval(function () {
                    const currentHash = globalThis.location.hash.replace('#', '');
                    if (currentHash !== 'payment') {
                        setPaymentStepVisible(steps);
                        globalThis.history.replaceState(null, null, targetUrl);
                    }
                }, 50);
            } else {
                // For virtual products, just ensure payment is visible
                protectionInterval = setInterval(function () {
                    setPaymentStepVisible(steps);
                }, 50);
            }

            setTimeout(function () {
                clearInterval(protectionInterval);
            }, 3000);
        }
    };
});
