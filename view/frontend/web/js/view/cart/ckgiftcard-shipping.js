define([
    'ko',
    'uiComponent',
    'jquery',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'mage/url'
], function (
    ko,
    Component,
    $,
    _,
    quote,
    urlBuilder
) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();

            // Subscribe to changes in the shipping method
            this.subscribeToShippingMethod();

            return this;
        },

        subscribeToShippingMethod: function () {
            quote.shippingMethod.subscribe(this.handleShippingMethodChange.bind(this));
        },

        handleShippingMethodChange: function (newShippingMethod) {
            if (this.isValidShippingMethod(newShippingMethod)) {
                this.updateGiftCard(newShippingMethod);
            }
        },

        isValidShippingMethod: function (shippingMethod) {
            return shippingMethod && shippingMethod.carrier_code;
        },

        updateGiftCard: function (newShippingMethod) {
            const self = this;

            $.ajax({
                url: urlBuilder.build('cardknox/giftcard/validategiftcard'),
                type: 'POST',
                data: { quote_data: newShippingMethod },
                success: function (data) {
                    if (data.success) {
                        self.handleSuccessfulValidation();
                    }
                },
                error: function (xhr) {
                    console.error('Error validating gift card:', xhr.statusText, xhr.responseText);
                }
            });
        },

        handleSuccessfulValidation: function () {
            $('#giftcard-code-cancle-btn').trigger('click');
        }
    });
});
