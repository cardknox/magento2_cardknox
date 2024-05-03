define([
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mageUtils'
], function (customer, urlBuilder, utils) {
    'use strict';

    return {
        /**
         * @param {Object} quote
         * @return {*}
         */
        getUrlForTaxEstimationForNewAddress: function (quote) {
            const params = this.getCheckoutMethod() === 'guest' ? //eslint-disable-line eqeqeq
                {
                    cartId: quote.getQuoteId()
                } : {},
                urls = {
                    'guest': '/guest-carts/:cartId/wallet-tax-calculate',
                    'customer': '/carts/mine/wallet-tax-calculate'
                };

            return this.getUrl(urls, params);
        },

        /**
         * Get url for service.
         *
         * @param {*} urls
         * @param {*} urlParams
         * @return {String|*}
         */
        getUrl: function (urls, urlParams) {
            let url;

            if (utils.isEmpty(urls)) {
                return 'Provided service call does not exist.';
            }

            if (!utils.isEmpty(urls['default'])) {
                url = urls['default'];
            } else {
                url = urls[this.getCheckoutMethod()];
            }

            return urlBuilder.createUrl(url, urlParams);
        },

        /**
         * @return {String}
         */
        getCheckoutMethod: function () {
            return customer.isLoggedIn() ? 'customer' : 'guest';
        }
    };
});
