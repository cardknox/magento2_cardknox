define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'CardknoxDevelopment_Cardknox/js/view/payment/method-renderer/cardknox-google-pay',
    'ifields',
    'Magento_Checkout/js/model/payment/additional-validators',
    "jquery",
    'Magento_Checkout/js/action/redirect-on-success',
    "ko",
    'Magento_Checkout/js/action/place-order',
    'CardknoxDevelopment_Cardknox/js/view/payment/cardknox-payment-helper'
], function (
    Component,
    quote,
    cardknoxGpay,
    ifields,
    additionalValidators,
    $,
    redirectOnSuccessActionGP,
    koForGP,
    placeOrderActionGP,
    cardknoxPaymentHelper
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
        isAllowDuplicateTransaction: koForGP.observable(false),
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
            let data = {
                'method': this.getCode(),
                'additional_data': {
                    'xCardNum': this.paymentMethodNonce,
                    'xAmount': this.xAmount,
                    'isSplitCapture': window.checkoutConfig.payment.cardknox_google_pay.isGPaySplitCaptureEnabled,
                    'xPaymentAction': window.checkoutConfig.payment.cardknox_google_pay.xPaymentAction,
                    'isAllowDuplicateTransaction': this.getAllowDuplicateTransactionGpay()
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
        },
        getAllowDuplicateTransactionGpay: function () {
            let isAllowDuplicateTransactionGpay = false;
            if ($('#is_allow_duplicate_transaction_gpay').length) {
                if($("#is_allow_duplicate_transaction_gpay").prop('checked')){
                    isAllowDuplicateTransactionGpay = true;
                }
            }
            return isAllowDuplicateTransactionGpay;
        },
        /**
             * @return {*}
             */
        getPlaceOrderDeferredObject: function () {
            return $.when(
                placeOrderActionGP(this.getData(), this.messageContainer)
            );
        },
        showPaymentError: function (message) {
            $(".gpay-error").html("<div> "+message+" </div>").show();
            setTimeout(function () {
                $(".gpay-error").html("").hide();
            }, 5000);

            fullScreenLoader.stopLoader();
            $('.checkout-cart-index .loading-mask').attr('style','display:none');
        },

        /**
         * Place order with duplicate transaction protection
         */
        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            // Save shipping method before placing order
            cardknoxPaymentHelper.saveShippingMethod();

            if (this.validate() &&
                additionalValidators.validate() &&
                this.isPlaceOrderActionAllowed() === true
            ) {
                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                    .done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessActionGP.execute();
                            }
                        }
                    ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).fail(
                        function (response) {
                            self.isPlaceOrderActionAllowed(true);

                            let error_message = "Unable to process the order. Please try again.";
                            if (response?.responseJSON?.message) {
                                error_message = response.responseJSON.message;
                            }

                            if (error_message.startsWith('Duplicate Transaction')) {
                                self.isAllowDuplicateTransaction(true);
                                cardknoxPaymentHelper.forceStayOnPayment();
                            } else {
                                self.isAllowDuplicateTransaction(false);
                            }
                        }
                    );

                return true;
            }

            return false;
        }
    });
});
