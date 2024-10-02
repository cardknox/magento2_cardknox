/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals',
    'ko'
], function (Component, totals, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'CardknoxDevelopment_Cardknox/summary/giftcard'
        },

        isDisplayed: function () {
            // return this.getGiftCardAmount();
            return window.checkoutConfig.payment.cardknox.isEnabledCardknoxGiftcard;
        },

        getGiftCardAmount: function () {
            var totalSegments = totals.getSegment('ckgiftcard'); // This should match your total segment key
            return totalSegments ? totalSegments.value : 0;
        },

        formatPrice: function (price) {
            return this.getFormattedPrice(price);
        },
    });
});
