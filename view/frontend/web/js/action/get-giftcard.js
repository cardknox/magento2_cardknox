/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'mage/url',
    'CardknoxDevelopment_Cardknox/js/model/giftcard',
    'CardknoxDevelopment_Cardknox/js/model/checkout/giftcard/giftcard-messages',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/totals'
], function ($, ko, url, giftCardAccount, messageContainer, fullScreenLoader, errorProcessor, totals) {
    'use strict';

    return {

        /**
         * * Get provided gift code balance.
         *
         * @param {*} giftCardCode
         */
        check: function (giftCardCode) {
            var self = this;

            // Clear previous messages and start the loader
            messageContainer.clear();
            fullScreenLoader.startLoader();

            $.ajax({
                url: url.build('cardknox/giftcard/checkBalanceStatus'),
                type: 'POST',
                dataType: 'json',
                data: {
                    giftcard_code: giftCardCode
                },
                success: function (response) {
                    if (response.success) {
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
