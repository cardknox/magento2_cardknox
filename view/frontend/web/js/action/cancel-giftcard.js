define([
    'jquery',
    'mage/url',
    'CardknoxDevelopment_Cardknox/js/model/checkout/giftcard/giftcard-messages',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/action/recollect-shipping-rates',
    'Magento_Checkout/js/model/totals'
], function (
    $,
    url,
    messageContainer,
    customerData,
    defaultTotal,
    fullScreenLoader,
    errorProcessor,
    getPaymentInformationAction,
    recollectShippingRates,
    totals
) {
    'use strict';

    return {
        /**
         * Cancel provided gift code.
         *
         * @param {string} ckGiftCardCode
         * @param {function} isCkGiftCardApplied
         */
        cancel: function (ckGiftCardCode, isCkGiftCardApplied) {
            messageContainer.clear();
            fullScreenLoader.startLoader();

            $.ajax({
                url: url.build('cardknox/giftcard/cancelGiftCard'),
                type: 'POST',
                dataType: 'json',
                data: { giftcard_code: ckGiftCardCode },
                success: this.handleSuccessResponse.bind(this, isCkGiftCardApplied),
                error: this.handleErrorResponse.bind(this)
            });
        },

        /**
         * Handle a successful response from the server.
         *
         * @param {function} isCkGiftCardApplied
         * @param {object} response
         */
        handleSuccessResponse: function (isCkGiftCardApplied, response) {
            fullScreenLoader.stopLoader();

            if (response.success) {
                isCkGiftCardApplied(false);
                this.refreshCart();
                messageContainer.addSuccessMessage({ 'message': response.message });
            } else {
                messageContainer.addErrorMessage({ 'message': response.message });
            }
        },

        /**
         * Handle an error response from the server.
         *
         * @param {object} xhr
         */
        handleErrorResponse: function (xhr) {
            fullScreenLoader.stopLoader();
            errorProcessor.process(xhr.statusText, messageContainer);
        },

        /**
         * Refresh the cart totals and minicart.
         */
        refreshCart: function () {
            var deferred = $.Deferred();
            defaultTotal.estimateTotals();
            customerData.reload(['cart'], true);
            recollectShippingRates();
            totals.isLoading(true);
            getPaymentInformationAction(deferred);
        }
    };
});
