/* tslint:disable: parameters-max-number no-redundant-boolean */
define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'CardknoxDevelopment_Cardknox/js/view/payment/method-renderer/cardknox-apple-pay',
    'ifields',
    'Magento_Checkout/js/model/payment/additional-validators',
    "jquery",
    "ko",
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
], function (
    Component,
    quote,
    cardknoxApplePay,
    ifields,
    additionalValidators,
    $,
    ko,
    redirectOnSuccessAction,
    placeOrderAction,
    fullScreenLoader
) {
    'use strict';
    window.checkoutConfig.reloadOnBillingAddress = true;
    const METHOD_ID = 'cardknox_apple_pay';

    return Component.extend({
        defaults: {
            template: 'CardknoxDevelopment_Cardknox/payment/cardknox-apple-pay-method.html',
            redirectAfterPlaceOrder: true,
            grandTotalAmount: 0,
            paymentMethodNonce: null,
            xAmount: null
        },
        isAllowDuplicateTransaction: ko.observable(false),
        /**
         * @return {exports}
         */
        initialize: function () {
            console.log('js loaded');
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
                    'xPaymentAction': window.checkoutConfig.payment.cardknox_apple_pay.xPaymentAction,
                    'isAllowDuplicateTransaction': this.getAllowDuplicateTransactionApay()
                }
            };
            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            return data;
        },

        initFrame: function () {
            if (/[?&](is)?debug/i.test(window.location.search)){
                setDebugEnv(true);
            }

            cardknoxApplePay.init(this);
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
        getAllowDuplicateTransactionApay: function () {
            let isAllowDuplicateTransactionApay = false;
            if ($('#is_allow_duplicate_transaction_apay').length) {
                if($("#is_allow_duplicate_transaction_apay").prop('checked') == true){
                    isAllowDuplicateTransactionApay = true;
                }
            }
            return isAllowDuplicateTransactionApay;
        },
        /**
             * @return {*}
             */
        getPlaceOrderDeferredObject: function () {
            return $.when(
                placeOrderAction(this.getData(), this.messageContainer)
            );
        },
        /**
         * Place order.
         */
        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

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
                                redirectOnSuccessAction.execute();
                            }
                        }
                    ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).fail(
                        function (response) {
                            self.isPlaceOrderActionAllowed(true);

                            let errorMessage = "Unable to process the order. Please try again.";
                            if (response && response.responseJSON && response.responseJSON.message) {
                                errorMessage = response.responseJSON.message;
                            }
                            self.showPaymentError(errorMessage);
                            if (errorMessage == 'Duplicate Transaction') {
                                self.isAllowDuplicateTransaction(true);
                            } else {
                                self.isAllowDuplicateTransaction(false);
                            }
                        }
                    );;

                return true;
            }

            return false;
        },
        showPaymentError: function (message) {
            $(".applepay-error").html("<div> "+message+" </div>").show();
            setTimeout(function () { 
                $(".applepay-error").html("").hide();
            }, 5000);
            
            fullScreenLoader.stopLoader();
            $('.checkout-cart-index .loading-mask').attr('style','display:none');
        }
    });
});
