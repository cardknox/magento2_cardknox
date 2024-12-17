define(
    [
        'jquery',
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils'
    ],
    function ($,Component,quote,totals,priceUtils) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'CardknoxDevelopment_Cardknox/checkout/summary/ckgiftcard'
            },
            ckgiftcard: window.checkoutConfig.payment.cardknox.isEnabledCardknoxGiftcard,
            totals: quote.getTotals(),

            isDisplayedCardknoxGiftcard: function () {
                return window.checkoutConfig.payment.cardknox.isEnabledCardknoxGiftcard;
            },

            getHandlingfeeTotal: function () {
                let price = 0;
                let isEnabledCardknoxGiftcard = window.checkoutConfig.payment.cardknox.isEnabledCardknoxGiftcard;
                if (this.totals() && isEnabledCardknoxGiftcard) {
                    if (this.totals() && totals.getSegment('ckgiftcard')) {
                        price = totals.getSegment('ckgiftcard').value;
                    }
                }
                return price;
            },

            getFormattedHandlingfeeTotal: function () {
                let price = -this.getHandlingfeeTotal();
                return this.getFormattedPrice(price);
            }
        });
    }
);
