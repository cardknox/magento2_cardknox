/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * Mixin to prevent redirect to shipping step for virtual products on payment error
 */
define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (stepNavigator) {
        /**
         * Check if cart has only virtual products
         * @returns {boolean}
         */
        const isVirtualQuote = function () {
            const quoteData = globalThis.checkoutConfig?.quoteData;
            return quoteData?.is_virtual === '1' ||
                   quoteData?.is_virtual === 1 ||
                   quoteData?.is_virtual === true;
        };

        /**
         * Override setHash to prevent setting shipping hash for virtual products
         */
        stepNavigator.setHash = wrapper.wrap(stepNavigator.setHash, function (originalSetHash, hash) {
            // For virtual products, don't allow setting hash to 'shipping'
            if (isVirtualQuote() && hash === 'shipping') {
                // Keep on payment step instead
                return;
            }
            return originalSetHash(hash);
        });

        /**
         * Override handleHash to prevent redirect to noroute for virtual products
         */
        const originalHandleHash = stepNavigator.handleHash;
        stepNavigator.handleHash = function () {
            const hashString = globalThis.location.hash.replace('#', '');

            // For virtual products, if hash is 'shipping', clear it and stay on current page
            if (isVirtualQuote() && hashString === 'shipping') {
                // Remove the invalid hash without triggering navigation
                globalThis.history?.replaceState(null, null, globalThis.location.pathname + globalThis.location.search);
                return false;
            }

            return originalHandleHash.call(this);
        };

        return stepNavigator;
    };
});
