define([
    'jquery',
    'ko',
    'uiComponent',
    'CardknoxDevelopment_Cardknox/js/action/get-giftcard',
    'CardknoxDevelopment_Cardknox/js/action/add-giftcard',
    'CardknoxDevelopment_Cardknox/js/action/cancel-giftcard',
    'CardknoxDevelopment_Cardknox/js/model/giftcard',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote'
],function ($, ko, Component, getGiftCardAction, addGiftCardAction, cancelGiftCardAction, giftCardAccount, customerData, quote)
{
    'use strict';

    let checkoutCkGiftCard = customerData.get('checkout_ckgiftcard');
    let { ckgiftcard_code: ckGiftCardCodeSession } = checkoutCkGiftCard();
    let ckGiftCardCode = giftCardAccount.getCkGiftCardCode();
    let isCkGiftCardApplied = giftCardAccount.getIsCkGiftCardApplied();
    let quoteGiftCardCode = window.checkoutConfig.quoteData.ckgiftcard_code;
    let cardknoxGiftcardText = window.checkoutConfig.payment.cardknox.cardknoxGiftcardText;

    // Check if quoteGiftCardCode is defined and matches ckGiftCardCodeSession
    if (quoteGiftCardCode !== null && ckGiftCardCodeSession === quoteGiftCardCode) {
        ckGiftCardCode(ckGiftCardCodeSession);
        isCkGiftCardApplied(true);
    }


    return Component.extend({

        defaults: {
            template: 'CardknoxDevelopment_Cardknox/checkout/giftcard',
            cardknoxGiftcardValue: cardknoxGiftcardText,
        },
        isLoading: getGiftCardAction.isLoading,
        giftCardAccount: giftCardAccount,

        ckGiftCardCode: ckGiftCardCode,
        isCkGiftCardApplied: isCkGiftCardApplied,

        /**
          @returns {}
         */
        initialize: function () {
            this._super();
            var grandTotal = quote.totals().grand_total;
            console.log("grandTotal :- " + grandTotal);
            $('#cancel-gift-card').trigger('click');
            if (this.isDisplayedCKGiftcard() === false) {
                var sections = ['cart'];
                customerData.reload(sections, true);
            }
        },

        /**
         * Apply gift card
         */
        isDisplayedCKGiftcard: function(value) {
            return window.checkoutConfig.payment.cardknox.isEnabledCardknoxGiftcard;
        },

        /**
         * Check gift card account balance
         */
        checkBalance: function() {
            if (this.validate()) {
                getGiftCardAction.check(this.ckGiftCardCode());
            }
        },

        /**
         * Apply gift card
         */
        addGiftCard: function(value) {
            if (this.validate()) {
                addGiftCardAction.add(this.ckGiftCardCode(), isCkGiftCardApplied);
            }
        },

        /**
         * Cancel gift card
         */
        cancelGiftCard: function(value) {
            if (this.validate()) {
                cancelGiftCardAction.cancel(this.ckGiftCardCode(), isCkGiftCardApplied);
            }
        },

        /**
         * Validate gift card form
         *
         * @returns {boolean}
         */
        validate: function() {
            var form = '#ckgiftcard-form';
            return $(form).validation() && $(form).validation('isValid');
        },
    });
});
