define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'CardknoxDevelopment_Cardknox/js/view/cart/cardknox-apple-pay',
    'ifields',
    'Magento_Checkout/js/model/payment/additional-validators',
    "jquery",
    "ko",
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/action/place-order',
    'Magento_Customer/js/model/customer',
    'mage/url',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/model/shipping-save-processor/default'
], function (
    Component,
    quote,
    cardknoxApplePay,
    ifields,
    additionalValidators,
    $,
    koForAP,
    fullScreenLoaderAP,
    redirectOnSuccessActionAP,
    placeOrderActionAP,
    customer,
    urlBuilder,
    createShippingAddress,
    createBillingAddress,
    saveShipping
) {
    'use strict';
    window.checkoutConfig.reloadOnBillingAddress = true;
    const METHOD_ID = 'cardknox_apple_pay';

    return Component.extend({
        defaults: {
            // template: 'CardknoxDevelopment_Cardknox/cart/apple-pay-btn',
            redirectAfterPlaceOrder: true,
            grandTotalAmount: 0,
            paymentMethodNonce: null,
            xAmount: null
        },
        isAllowDuplicateTransaction: koForAP.observable(false),
        /**
         * @return {exports}
         */
        initialize: function () {
            this._super();
            this.customerIsLoggedIn();

            return this;
        },

        customerIsLoggedIn: function () {
            return customer.isLoggedIn();
        },

        /**
         * Apple pay place order method
         */
        startPlaceOrder: function (nonce, xAmount, applePayload) {
            $("body").trigger('processStart');
            if (!this.customerIsLoggedIn()) {
                // Sets shipping and billing address for guest
                this.onPaymentMethodReceived(applePayload);
            }
            this.xAmount = xAmount ;
            this.setPaymentMethodNonce(nonce);
            this.isPlaceOrderActionAllowed(true);
            saveShipping.saveShippingInformation();
            
            // Getting All Response Then call PlaceOrder function
            setTimeout(() => {
                this.placeOrder();
            }, 1000);
        },

        /**
         * Save nonce
         */
        setPaymentMethodNonce: function (nonce) {
            this.paymentMethodNonce = nonce;
        },

        /**
         * Sets shipping and billing address
         *
         * @param {Object} payload
         */
        onPaymentMethodReceived: function (payload) {
            this.setShippingAddress(payload);
            this.setBillingAddress(payload);
        },

        /**
         * Sets shipping address for quote
         */
        setShippingAddress: function (data) {
            var regionData = null;
            var email = data.shippingContact.emailAddress;
            var street = data.shippingContact.addressLines.filter(function(line) {
                return line; // This will remove any falsy values: undefined, null, "", 0, false, NaN
            }).join(" ");

            // Get region name and id
            regionData = this.getRegionData(data.shippingContact.administrativeArea, data.shippingContact.countryCode);
            regionData = JSON.parse(regionData);
            // Remove country code from telephone
            var telephone = data.shippingContact.phoneNumber;
            telephone = telephone.substring(telephone.indexOf(" ") + 1);

            // Create name array of address
            var firstname = data.shippingContact.givenName;
            var lastname = data.shippingContact.familyName;
            var middlename = null;

            var shippingAddress = {
                firstname: firstname,
                lastname: lastname,
                middlename: middlename,
                company:null,
                prefix:null,
                suffix:null,
                vat_id:null,
                fax:null,
                save_in_address_book:null,
                customerId:null,
                same_as_billing:0,
                extension_attributes: [],
                custom_attributes: [],
                email: email,
                street: [street],
                city: data.shippingContact.locality,
                region_id: regionData.region.region_id,
                region_code: regionData.region.code,
                region: regionData.region.name,
                countryId: data.shippingContact.countryCode,
                telephone: telephone,
                postcode: data.shippingContact.postalCode
            };
            shippingAddress = createShippingAddress(shippingAddress);
            quote.shippingAddress(shippingAddress);
            quote.customer_firstname = data.shippingContact.givenName;
        },

        /**
         * Sets billing address for quote
         */
        setBillingAddress: function (data) {
            var regionData = null;
            var email = data.shippingContact.emailAddress;
            var street = data.billingContact.addressLines.filter(function(line) {
                return line; // This will remove any falsy values: undefined, null, "", 0, false, NaN
            }).join(" ");
            // Get region name and id
            regionData = this.getRegionData(data.billingContact.administrativeArea, data.billingContact.countryCode);
            regionData = JSON.parse(regionData);
            // Remove country code from telephone
            var telephone = data.shippingContact.phoneNumber;
            telephone = telephone.substring(telephone.indexOf(" ") + 1);

            // Create name array of address
            var firstname = data.billingContact.givenName;
            var lastname = data.billingContact.familyName;
            var middlename = null;

            var billingAddress = {
                firstname: firstname,
                lastname: lastname,
                middlename: middlename,
                company:null,
                prefix:null,
                suffix:null,
                vat_id:null,
                fax:null,
                customerId:null,
                save_in_address_book:null,
                extension_attributes: [],
                custom_attributes: [],
                email: email,
                street: [street],
                city: data.billingContact.locality,
                region_id: regionData.region.region_id,
                region_code: regionData.region.code,
                region: regionData.region.name,
                countryId: data.billingContact.countryCode,
                telephone: telephone,
                postcode: data.billingContact.postalCode
            };
            billingAddress = createBillingAddress(billingAddress);
            quote.billingAddress(billingAddress);
            quote.guestEmail = email;
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

        isSupportedApplePay: function () {
            return window.ApplePaySession && ApplePaySession.canMakePayments();
        },
    
        /**
         * @return {Boolean}
         */
         validate: function () {
            return true;
        },

        /**
         * Additional Validator
         */
        additionalValidator: function () {
            return additionalValidators.validate();
        },
    
        /**
         * Get Allow Duplicate Transaction Apay
         */
        getAllowDuplicateTransactionApay: function () {
            let isAllowDuplicateTransactionApay = false;
            if ($('#is_allow_duplicate_transaction_apay').length) {
                if($("#is_allow_duplicate_transaction_apay").prop('checked')){
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
                placeOrderActionAP(this.getData(), this.messageContainer)
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
                                redirectOnSuccessActionAP.execute();
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
                            if (response?.responseJSON?.message) {
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

        /**
         * Show Payment Error
         */
        showPaymentError: function (message) {
            $(".applepay-error").html("<div> "+message+" </div>").show();
            setTimeout(function () { 
                $(".applepay-error").html("").hide();
            }, 5000);
            
            fullScreenLoaderAP.stopLoader();
            $('.checkout-cart-index .loading-mask').attr('style','display:none');
        },

        /**
         * Get regions by region code or name
         */
        getRegionData: function (administrativeArea, countryCode) {
            var serviceUrl, payload;
            payload = {
                region: administrativeArea,
                country_id: countryCode
            };
            serviceUrl = urlBuilder.build('/cardknox/index/countryregion');
            var response = null;
            $.ajax({
                url: serviceUrl,
                type: "POST",
                async: false,
                data: payload,
                success: function(data){
                    response = JSON.stringify(data);
                }
            });
            return response;
        }
    });
});
