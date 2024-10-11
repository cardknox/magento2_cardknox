/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

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
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/payment/method-list'
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
         * Apply provided gift code.
         *
         * @param {String} ckgiftCardCode
         * @param {Function} isCkGiftCardApplied
         */
        add: function (ckgiftCardCode, isCkGiftCardApplied) {
            const self = this;
            messageContainer.clear();
            fullScreenLoader.startLoader();

            $.ajax({
                url: url.build('cardknox/giftcard/addGiftCard'),
                type: 'POST',
                dataType: 'json',
                data: { giftcard_code: ckgiftCardCode },
                success: function (response) {
                    self.handleResponse(response, isCkGiftCardApplied);
                },
                error: function () {
                    fullScreenLoader.stopLoader();
                    errorProcessor.process(__('An error occurred while applying the gift card.'), messageContainer);
                }
            });
        },

        /**
         * Handle AJAX response.
         *
         * @param {Object} response
         * @param {Function} isCkGiftCardApplied
         */
        handleResponse: function (response, isCkGiftCardApplied) {
            fullScreenLoader.stopLoader();
            if (response.success) {
                isCkGiftCardApplied(true);
                defaultTotal.estimateTotals();
                customerData.reload(['cart'], true);
                recollectShippingRates();
                totals.isLoading(true);

                var deferred = $.Deferred();
                getPaymentInformationAction(deferred);
                messageContainer.addSuccessMessage({ 'message': response.message });
            } else {
                messageContainer.addErrorMessage({ 'message': response.message });
            }
        }
    };
});
