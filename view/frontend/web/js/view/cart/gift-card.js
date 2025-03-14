define([
    'jquery',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'CardknoxDevelopment_Cardknox/js/model/giftcard',
    'jquery-ui-modules/widget',
    'mage/validation'
], function ($, defaultTotal, cartCache, giftcardModel) {
    'use strict';

    $.widget('mage.giftCard', {
        options: {
            messageTimeout: 5000
        },

        /**
         * Initialize widget
         * @private
         */
        _create: function () {
            this._bindEvents();
            this._reloadCartAndTotals();
        },

        /**
         * Bind all relevant events
         * @private
         */
        _bindEvents: function () {
            $(this.options.checkStatus).on('click', $.proxy(this._handleCheckStatus, this));
            $(this.options.addGiftcardCodeBtn).on('click', $.proxy(this._handleAddGiftCard, this));
            $(this.options.cancelGiftcardCodeBtn).on('click', $.proxy(this._handleCancelGiftCard, this));

            $('#giftcard-code').on('input', function () {
                $(this).val($(this).val()); // Reapply value to input field
            });
        },

        /**
         * Handle checking gift card balance/status
         * @private
         */
        _handleCheckStatus: function (e) {
            e.preventDefault();
            this._processGiftCard(this.options.giftCardStatusUrl, 'checkBalance');
        },

        /**
         * Handle adding gift card
         * @private
         */
        _handleAddGiftCard: function (e) {
            e.preventDefault();
            this._processGiftCard(this.options.addGiftCardUrl, 'addGiftCard');
        },

        /**
         * Handle cancelling gift card
         * @private
         */
        _handleCancelGiftCard: function (e) {
            e.preventDefault();
            this._processGiftCard(this.options.cancelGiftCardUrl, 'cancelGiftCard');
        },

        /**
         * Process the gift card action (add/cancel/check)
         * @param {string} ajaxUrl
         * @param {string} actionType
         * @private
         */
        _processGiftCard: function (ajaxUrl, actionType) {
            let self = this;
            let formElement = this._getFormElement();
            let formData = this._collectFormData(formElement);

            if (!this.element.validation().valid()) {
                return;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'post',
                cache: false,
                data: formData,

                beforeSend: function () {
                    $(self.options.giftCardSpinnerId).show();
                },

                success: function (response) {
                    self._handleResponse(response, actionType, formElement);
                },

                complete: function () {
                    $(self.options.giftCardSpinnerId).hide();
                    setTimeout(function () {
                        $(self.options.giftCardStatusId).html('');
                    }, self.options.messageTimeout);
                }
            });
        },

        /**
         * Handle the response from the server
         * @param {Object} response
         * @param {string} actionType
         * @param {HTMLElement} formElement
         * @private
         */
        _handleResponse: function (response, actionType, formElement) {
            let messageType = response.success ? 'success' : 'error';
            let responseMessageHtml = this._responseMessageHtml(messageType, response.message);
            $(this.options.giftCardStatusId).html(responseMessageHtml);

            this._reloadCartAndTotals();

            if (response.success) {
                this._toggleGiftCardButtons(actionType, formElement);
            }
        },

        /**
         * Reload the mini cart and totals summary
         * @private
         */
        _reloadCartAndTotals: function () {
            cartCache.set('totals',null);
            defaultTotal.estimateTotals();
        },

        /**
         * Toggle the visibility of add/cancel buttons based on action type
         * @param {string} actionType
         * @param {HTMLElement} formElement
         * @private
         */
        _toggleGiftCardButtons: function (actionType, formElement) {
            let ckgiftCardCode = $('#giftcard-code').val();
            if (actionType === 'addGiftCard') {
                $(this.options.addGiftcardCodeBtn).hide();
                $(this.options.cancelGiftcardCodeBtn).show();
                $(this.options.giftCardCodeSelector).attr("disabled", "disabled");
            } else if (actionType === 'cancelGiftCard') {
                $(this.options.addGiftcardCodeBtn).show();
                $(this.options.cancelGiftcardCodeBtn).hide();
                $(this.options.giftCardFormSelector)[0].reset();
                $(this.options.giftCardCodeSelector).removeAttr('disabled');
                $(formElement).find('input').val(''); // Reset form fields
            }
            giftcardModel.ckGiftCardCode(ckgiftCardCode);
        },

        /**
         * Collect form data for AJAX request
         * @param {HTMLElement} formElement
         * @return {Object} formData
         * @private
         */
        _collectFormData: function (formElement) {
            let formData = {};
            let self = this;
            let selectedMethod = $('input[type="radio"][name^="ko_unique_"]:checked').val();

            if (formElement) {
                $(formElement).find('input').each(function () {
                    formData[$(this).attr('name')] = $(this).val();
                });
            } else {
                formData['giftcard_code'] = $(self.options.giftCardCodeSelector).val();
            }
            formData['is_cart_page'] = 1;
            formData['selected_method'] = selectedMethod;

            return formData;
        },

        /**
         * Get the form element (fallback to gift card input's closest form if no form selector is provided)
         * @return {HTMLElement|null}
         * @private
         */
        _getFormElement: function () {
            return this.options.giftCardFormSelector
                ? document.querySelector(this.options.giftCardFormSelector)
                : $(this.options.giftCardCodeSelector).closest('form');
        },

        /**
         * Create HTML for displaying response message
         * @param {string} messageType
         * @param {string} message
         * @return {string}
         * @private
         */
        _responseMessageHtml: function (messageType, message) {
            return '<div class="message ' + messageType + '">' + message + '</div>';
        }
    });

    return $.mage.giftCard;
});
