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
    'Magento_Checkout/js/model/step-navigator'
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
    stepNavigator
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
         * Force stay on payment step after duplicate transaction error
         * This prevents Magento from redirecting to shipping step
         */
        forceStayOnPayment: function () {
            var self = this;
            var isVirtual = window.checkoutConfig.quoteData &&
                           (window.checkoutConfig.quoteData.is_virtual === '1' ||
                            window.checkoutConfig.quoteData.is_virtual === 1 ||
                            window.checkoutConfig.quoteData.is_virtual === true);

            // Store the current shipping method to prevent hasShippingMethod() from returning false
            if (!window.checkoutConfig.selectedShippingMethod && window.cardknoxSavedShippingMethod) {
                window.checkoutConfig.selectedShippingMethod = window.cardknoxSavedShippingMethod;
            }

            // Find the payment step and force it to be visible
            var steps = stepNavigator.steps();
            steps.forEach(function(step) {
                if (step.code === 'payment') {
                    step.isVisible(true);
                } else {
                    step.isVisible(false);
                }
            });

            // For virtual products, don't change the hash - just keep payment visible
            // For non-virtual products, set hash to payment
            if (!isVirtual) {
                var baseUrl = window.location.origin + window.location.pathname;
                var targetUrl = baseUrl + '#payment';

                if (window.location.hash !== '#payment') {
                    window.history.replaceState(null, null, targetUrl);
                }

                // Monitor and prevent any navigation away from payment for 3 seconds (non-virtual only)
                var protectionInterval = setInterval(function() {
                    var currentHash = window.location.hash.replace('#', '');
                    if (currentHash !== 'payment') {
                        steps.forEach(function(step) {
                            if (step.code === 'payment') {
                                step.isVisible(true);
                            } else {
                                step.isVisible(false);
                            }
                        });
                        window.history.replaceState(null, null, targetUrl);
                    }
                }, 50);

                setTimeout(function() {
                    clearInterval(protectionInterval);
                }, 3000);
            } else {
                // For virtual products, just ensure payment is visible and prevent any navigation
                var protectionInterval = setInterval(function() {
                    steps.forEach(function(step) {
                        if (step.code === 'payment') {
                            step.isVisible(true);
                        } else {
                            step.isVisible(false);
                        }
                    });
                }, 50);

                setTimeout(function() {
                    clearInterval(protectionInterval);
                }, 3000);
            }
        },

        /**
         * Override placeOrder to save shipping method before attempting order
         */
        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            // Save shipping method before placing order
            if (window.checkoutConfig.selectedShippingMethod) {
                window.cardknoxSavedShippingMethod = window.checkoutConfig.selectedShippingMethod;
            }

            // For virtual products, set a dummy shipping method to prevent redirect to shipping step on error
            // This is needed because Magento's payment.js navigate() checks hasShippingMethod()
            var isVirtual = window.checkoutConfig.quoteData &&
                           (window.checkoutConfig.quoteData.is_virtual === '1' ||
                            window.checkoutConfig.quoteData.is_virtual === 1 ||
                            window.checkoutConfig.quoteData.is_virtual === true);

            if (isVirtual && !window.checkoutConfig.selectedShippingMethod) {
                window.checkoutConfig.selectedShippingMethod = 'virtual';
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
                                // Prevent redirect to shipping section on duplicate transaction error
                                // Force payment step to be visible and stay on payment
                                self.forceStayOnPayment();
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