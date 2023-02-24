define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'CardknoxDevelopment_Cardknox/js/view/payment/method-renderer/cardknox-google-pay',
    'ifields',
    'Magento_Checkout/js/model/payment/additional-validators',
    "jquery",
    "ko"
], function (
    Component,
    quote,
    cardknoxGpay,
    ifields,
    additionalValidators,
    $,
    ko
) {
    'use strict';
    window.checkoutConfig.reloadOnBillingAddress = true;
    const METHOD_ID = 'cardknox_google_pay';
        
    return Component.extend({
        defaults: {
            template: 'CardknoxDevelopment_Cardknox/payment/cardknox-google-pay-method.html',
            redirectAfterPlaceOrder: true,
            grandTotalAmount: 0,
            paymentMethodNonce: null,
            xAmount: null
        },

        /**
         * @return {exports}
         */
        initialize: function () {
            this._super();

            return this;
        },

        /**
         * Google pay place order method
         */
        startPlaceOrder: function (nonce, xAmount) {
            this.xAmount = xAmount ;
            this.setPaymentMethodNonce(nonce);
            this.isPlaceOrderActionAllowed(true);
            this.placeOrder();
        },

        /**
         * Save nonce
         */
        setPaymentMethodNonce: function (nonce) {
            this.paymentMethodNonce = nonce;
        },

        getCode: function () {
            return METHOD_ID;
        },

        /**
         * Get data
         *
         * @returns {Object}
         */
        getData: function () {
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'xCardNum': this.paymentMethodNonce,
                    'xAmount': this.xAmount,
                    'isSplitCapture': window.checkoutConfig.payment.cardknox_google_pay.isGPaySplitCaptureEnabled,
                    'xPaymentAction': window.checkoutConfig.payment.cardknox_google_pay.xPaymentAction
                }
            };
            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            return data;
        },

        initFrame: function () {        
            if (/[?&](is)?debug/i.test(window.location.search)){
                setDebugEnv(true);
            }

            cardknoxGpay.init(this);
        },
        /**
         * @return {Boolean}
         */
         validate: function () {
            return true;
        },
        
        additionalValidator: function () {
            return additionalValidators.validate();
        }
    });
});