define([
    'jquery',
    'mage/utils/wrapper',
    'underscore',
    'CardknoxDevelopment_Cardknox/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'mage/url'
], function ($, wrapper, _, resourceUrlManager, quote, storage, totalsService, errorProcessor, customerData, rateRegistry, urlBuilder) {
    'use strict';

    const _simpleHash = (str) => {
        let hash = 0;
        if (str.length === 0) return hash;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return hash;
    }

    /**
     * Get regions by region code or name
     */
    const getRegionData = (administrativeArea, countryCode) => {
        let serviceUrl, payload;
        payload = {
            region: administrativeArea,
            country_id: countryCode
        };
        serviceUrl = urlBuilder.build('/cardknox/index/countryregion');
        let response = null;
        $.ajax({
            url: serviceUrl,
            type: 'POST',
            async: false,
            data: payload,
            success: function(data){
                response = JSON.stringify(data);
            }
        });

        return response;
    }

    return function (paymentData) {
        let serviceUrl,
            payload,
            address,
            paymentMethod,
            requiredFields = ['countryId', 'region', 'regionId', 'postcode', 'city'],
            newAddress = quote.shippingAddress() ? quote.shippingAddress() : quote.billingAddress(),
            city;

        if (paymentData.address.region) {
            const regionCode = paymentData.address.region,
                countryId = paymentData.address.countryId;

            let regionData = getRegionData(regionCode, countryId);
            regionData = JSON.parse(regionData);
            paymentData.address['regionId'] = regionData.region.region_id;
        }

        newAddress = $.extend({}, newAddress, paymentData.address);

        serviceUrl = resourceUrlManager.getUrlForTaxEstimationForNewAddress(quote);
        address = _.pick(newAddress, requiredFields);
        paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : null;

        city = address.city;

        if (!city) {
            if (quote.isVirtual() && quote.billingAddress()) {
                city = quote.billingAddress().city;
            } else if (quote.shippingAddress()) {
                city = quote.shippingAddress().city;
            }
        }

        if (_.isEmpty(address.countryId) && _.isEmpty(address.region) && _.isEmpty(address.postcode)) {
            return;
        }

        payload = {
            addressInformation: {
                address: address
            }
        };

        if (paymentData.shippingMethod && paymentData.shippingMethod.id) {
            let methodId = paymentData.shippingMethod.id;
            if (methodId) {
                let shippingCode = methodId.split('__');

                payload.addressInformation['shipping_carrier_code'] = shippingCode[0];
                payload.addressInformation['shipping_method_code'] = shippingCode[1];
            }
        }

        if (quote.shippingMethod() && quote.shippingMethod()['method_code']) {
            if (!payload.addressInformation['shipping_carrier_code']) {
                payload.addressInformation['shipping_method_code'] = quote.shippingMethod()['method_code'];
                payload.addressInformation['shipping_carrier_code'] = quote.shippingMethod()['carrier_code'];
            }
        }

        const keyCache = _simpleHash(JSON.stringify(payload)),
            cachedData = rateRegistry.get(keyCache);

        if (cachedData) {
            return cachedData;
        }

        let response = {};
        $.ajax({
            url: urlBuilder.build(serviceUrl),
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            async: false,
            success: function (res) {
                response = res;
                rateRegistry.set(keyCache, response);
            },
            error: function (response) {
                errorProcessor.process(response);
                // scroll to error
                $('html, body').animate({scrollTop: 0}, 500);
            }
        });

        return response;
    }
});
