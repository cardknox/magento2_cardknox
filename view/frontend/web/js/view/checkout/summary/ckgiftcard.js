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
            ckgiftcard: window.checkoutConfig.usa_processing_fee,
            totals: quote.getTotals(),
               isDisplayedHandlingfeeTotal : function () {
                   //    return this.ckgiftcard.isEnabled;
                   return true;
            },
            getHandlingfeeTotal : function () {
                var price = 0;
                // if (this.totals() && this.ckgiftcard.isEnabled == true) {
                if (this.totals() && totals.getSegment('ckgiftcard')) {
                    price = totals.getSegment('ckgiftcard').value;
                }           
                return this.getFormattedPrice(price);
            }
        });
    }
);