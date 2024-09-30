/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'mage/url',
    'CardknoxDevelopment_Cardknox/js/model/giftcard',
    'CardknoxDevelopment_Cardknox/js/model/payment/giftcard-messages',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/action/recollect-shipping-rates',
    'Magento_Checkout/js/model/totals'
], function ($, ko, url, giftCardAccount, messageContainer, customerData, defaultTotal, fullScreenLoader, errorProcessor, getPaymentInformationAction, recollectShippingRates, totals) {
    'use strict';

    return {

        /**
         * * Cancel provided gift code.
         * 
         * @param {*} ckgiftCardCode
         * @param {Boolean}isCkGiftCardApplied
         */
        cancel: function (ckGiftCardCode, isCkGiftCardApplied) {
            var self = this;
            messageContainer.clear();
            fullScreenLoader.startLoader();

            $.ajax({
                url: url.build('cardknox/giftcard/cancelGiftCard'), 
                type: 'POST',
                dataType: 'json',
                data: {
                    giftcard_code: ckGiftCardCode
                },
                success: function (response) {
                    var deferred;
                    if (response.success) {
                        deferred = $.Deferred();
                        isCkGiftCardApplied(false);

                        // Refresh totals after applying the gift card
                        defaultTotal.estimateTotals();

                        // Reload the minicart
                        customerData.reload(['cart'], true);

                        recollectShippingRates();
                        totals.isLoading(true);
                        getPaymentInformationAction(deferred);

                        fullScreenLoader.stopLoader();
                        messageContainer.addSuccessMessage({
                            'message': response.message
                        });
                    } else {
                        fullScreenLoader.stopLoader();
                        messageContainer.addErrorMessage({
                            'message': response.message
                        });
                    }
                },
                error: function () {
                    fullScreenLoader.stopLoader();
                    errorProcessor.process(response.message, messageContainer);
                }
            });
        }
    };
});
