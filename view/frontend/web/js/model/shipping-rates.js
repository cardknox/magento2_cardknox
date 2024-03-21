define([
    'jquery',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'mage/url'
], function ($, resourceUrlManager, quote, rateRegistry, urlBuilder) {
    'use strict';

    return {
        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getRates: function (address) {
            let serviceUrl, payload;

            serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
            payload = this.getPayload(address);

            const keyCache = this.getKeyCache(address),
                cachedData = rateRegistry.get(keyCache);

            if (cachedData) {
                console.log('cachedData: ', cachedData);
                return cachedData;
            }

            let response = {};
            $.ajax({
                url: urlBuilder.build(serviceUrl),
                type: 'POST',
                data: payload,
                contentType: 'application/json',
                async: false,
                success: function (res) {
                    response = res;
                    rateRegistry.set(keyCache, response);
                }
            });

            console.log('response from ajax: ', response);

            return response;
        },

        getKeyCache: function (address) {
            const payload = this.getPayload(address);
            return this._simpleHash(payload);
        },

        getPayload: function (address) {
            return JSON.stringify({
                address: {
                    'city': address.city,
                    'region': address.region,
                    'country_id': address.countryId,
                    'postcode': address.postcode,
                }
            });
        },

        _simpleHash: function(str) {
            let hash = 0;
            if (str.length === 0) return hash;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return hash;
        }
    };
});
