/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Customer/js/customer-data',
    'jquery-ui-modules/widget',
    'mage/validation'
], function ($, getTotalsAction, customerData) {
    'use strict';

    $.widget('mage.giftCard', {
        /**
         * @private
         */
        _create: function () {
            // Check Balance and giftcard status
            $(this.options.checkStatus).on('click', $.proxy(function (e) {
                e.preventDefault();
                this._processGiftCard(this.options.giftCardStatusUrl , 'checkBalance');
            }, this));
            // Add giftcard code
            $(this.options.addGiftcardCodeBtn).on('click', $.proxy(function (e) {
                e.preventDefault();
                this._processGiftCard(this.options.addGiftCardUrl, 'addGiftCard');
            }, this));
            // Cancel giftcard code
            $(this.options.cancelGiftcardCodeBtn).on('click', $.proxy(function (e) {
                e.preventDefault();
                this._processGiftCard(this.options.cancelGiftCardUrl, 'cancelGiftCard');
            }, this));

            $('#giftcard-code').on('input', $.proxy(function (e) {
                e.preventDefault();
                $(this).val($(this).val()); // Update the input with the new value
            }, this));
        },
        _processGiftCard: function (ajaxUrl, actionType) {
            var giftCardStatusId,
            giftCardSpinnerId,
            messages,
            formElement,
            formData = {},
            captchaReload;
            var self = this;
            if (this.element.validation().valid()) {
                giftCardStatusId = this.options.giftCardStatusId;
                giftCardSpinnerId = $(this.options.giftCardSpinnerId);
                messages = this.options.messages;
                var options = this.options;

                if (this.options.giftCardFormSelector) {
                    formElement = document.querySelector(this.options.giftCardFormSelector);
                } else {
                    formElement = $(this.options.giftCardCodeSelector).closest('form');
                }

                if (formElement) {
                    $(formElement).find('input').each(function () {
                        formData[$(this).attr('name')] = $(this).val();
                    });
                } else {
                    formData['giftcard_code'] = $(this.options.giftCardCodeSelector).val();
                }
                $.ajax({
                    url: ajaxUrl,
                    type: 'post',
                    cache: false,
                    data: formData,

                    /**
                     * Before send.
                     */
                    beforeSend: function () {
                        giftCardSpinnerId.show();
                    },

                    /**
                     * @param {*} response
                     */
                    success: function (response) {
                        $(messages).hide();
                        var messageType = (response.success == false) ? 'error' : 'success';
                        var responseMessageHtml = self._responseMessageHtml(messageType, response.message);
                        $(giftCardStatusId).html(responseMessageHtml);

                        // The totals summary block reloading
                        var deferred = $.Deferred();
                        getTotalsAction([], deferred);

                        var sections = ['cart'];
                        // The mini cart reloading
                        customerData.reload(sections, true);
                        if (response.success == true && actionType == 'addGiftCard') {
                            $(options.addGiftcardCodeBtn).hide();
                            $(options.cancelGiftcardCodeBtn).show();
                            $(options.giftCardCodeSelector).attr("disabled", "disabled");
                        }
                        if (response.success == true && actionType == 'cancelGiftCard') {
                            $(options.addGiftcardCodeBtn).show();
                            $(options.cancelGiftcardCodeBtn).hide();
                            $(options.giftCardFormSelector)[0].reset();
                            $(options.giftCardCodeSelector).removeAttr('disabled');
                            $(formElement).find('input').each(function () {
                                formData[$(this).attr('name')] = $(this).val('');
                            });
                        }
                    },

                    /**
                     * Complete.
                     */
                    complete: function () {
                        giftCardSpinnerId.hide();
                        setTimeout(function () {
                            $(giftCardStatusId).html('');
                        }, 5000);
                    }
                });
            }
        },
        _responseMessageHtml: function (messageType, message) {
            return '<div class="message '+  messageType+'">'+message+'</div>';
        }
    });

    return $.mage.giftCard;
});
