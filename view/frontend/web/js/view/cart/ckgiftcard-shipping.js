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
            this.previousShippingMethod = null;
            // Subscribe to changes in the shipping method
            this.subscribeToShippingMethod();

            return this;
        },

        subscribeToShippingMethod: function () {
            quote.shippingMethod.subscribe(this.handleShippingMethodChange.bind(this));
        },

        handleShippingMethodChange: function (newShippingMethod) {
            if (!this.isValidShippingMethod(newShippingMethod)) {
                return;
            }

            let selectedShippingMethodCode = newShippingMethod.method_code;
            let selectedShippingCarrierCode = newShippingMethod.carrier_code;
            let selectedShippingMethod = selectedShippingCarrierCode + '_' + selectedShippingMethodCode
            // Check if the new shipping method is different from the previous one
            if (this.previousShippingMethod !== selectedShippingMethod) {
                this.updateGiftCard(newShippingMethod, selectedShippingMethod);
            }

            // Update previous shipping method
            this.previousShippingMethod = selectedShippingMethod;
        },

        isValidShippingMethod: function (shippingMethod) {
            return shippingMethod && shippingMethod.carrier_code;
        },

        updateGiftCard: function (newShippingMethod, selectedShippingMethod) {
            const self = this;
            const url = urlBuilder.build('cardknox/giftcard/validategiftcard');

            // Use native XMLHttpRequest to avoid jQuery mixin recursion issues
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            self.handleSuccessfulValidation();
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                } else {
                    console.error('Error validating gift card:', xhr.statusText, xhr.responseText);
                }
            };

            xhr.onerror = function () {
                console.error('Error validating gift card:', xhr.statusText, xhr.responseText);
            };

            // Serialize nested object data for PHP to parse as array
            const formData = this.serializeObject({
                quote_data: newShippingMethod,
                selected_shipping_method: selectedShippingMethod
            });

            xhr.send(formData);
        },

        /**
         * Serialize nested objects into application/x-www-form-urlencoded format
         * that PHP can parse into nested arrays
         */
        serializeObject: function (obj, prefix) {
            const params = [];

            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    const paramKey = prefix ? prefix + '[' + key + ']' : key;
                    const value = obj[key];

                    if (value !== null && typeof value === 'object') {
                        // Recursively serialize nested objects
                        params.push(this.serializeObject(value, paramKey));
                    } else {
                        // Encode simple values
                        params.push(encodeURIComponent(paramKey) + '=' + encodeURIComponent(value));
                    }
                }
            }

            return params.join('&');
        },

        handleSuccessfulValidation: function () {
            $('#giftcard-code-cancel-btn').trigger('click');
            if ($('#cancel-gift-card').length) {
                $('#cancel-gift-card').trigger('click');
            }
        }
    });
});
