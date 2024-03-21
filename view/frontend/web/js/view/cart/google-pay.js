define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/model/full-screen-loader',
    'CardknoxDevelopment_Cardknox/js/view/cart/cardknox-google-pay',
    'ifields',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/place-order',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/create-shipping-address',
    'mage/url',
    'Magento_Checkout/js/model/shipping-save-processor/default',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Customer/js/customer-data'
], function(
    $,
    Component,
    ko,
    quote,
    redirectOnSuccessAction,
    fullScreenLoader,
    cardknoxGpay,
    ifields,
    additionalValidators,
    placeOrderAction,
    customer,
    createBillingAddress,
    createShippingAddress,
    urlBuilder,
    saveShipping,
    selectShippingMethodAction,
    customerData
) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'CardknoxDevelopment_Cardknox/cart/google-pay-btn'
        },
        initialize: function() {
            this._super();
            this.customerIsLoggedIn();
        },

        customerIsLoggedIn: function () {
            return customer.isLoggedIn();
        },
        initFrame: function () {
            if (/[?&](is)?debug/i.test(window.location.search)){
                setDebugEnv(true);
            }

            cardknoxGpay.init(this);
        },
        isActive: function () {
            return window.checkoutConfig.payment.cardknox_google_pay.isActive;
        },
        /** Returns send check to info */
        getMailingAddress: function () {
            return window.checkoutConfig.payment.checkmo.mailingAddress;
        },
        getCode: function () {
            return 'cardknox_google_pay';
        },
        /**
         * Get data
         *
         * @returns {Object}
         */
        getData: function () {
            let shipping_address_firstname = null;

            if (!quote.isVirtual()) {
                if (!this.customerIsLoggedIn()) {
                    shipping_address_firstname = quote.shippingAddress().firstname;
                }
            } else {
                shipping_address_firstname = quote.billingAddress().firstname;
            }

            let data = {
                'method': this.getCode(),
                'additional_data': {
                    'xCardNum': this.paymentMethodNonce,
                    'xAmount': this.xAmount,
                    'shipping_address_firstname': shipping_address_firstname,
                    'isSplitCapture': window.checkoutConfig.payment.cardknox_google_pay.isGPaySplitCaptureEnabled,
                    'xPaymentAction': window.checkoutConfig.payment.cardknox_google_pay.xPaymentAction
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            return data;
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

                            let error_message = "Unable to process the order. Please try again.";
                            if (response?.responseJSON?.message) {
                                error_message = response.responseJSON.message;
                            }
                            self.showPaymentError(error_message);
                        }
                    );;

                return true;
            }

            return false;
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
         * @return {*}
         */
        getPlaceOrderDeferredObject: function () {
            return $.when(
                placeOrderAction(this.getData(), this.messageContainer)
            );
        },

        /**
         * Google pay place order method
         */
        startPlaceOrder: function (nonce, xAmount, paymentResponse) {
            $("body").trigger('processStart');

            this.xAmount = xAmount ;
            this.setShippingBillingAddress(paymentResponse);
            this.setPaymentMethodNonce(nonce);
            this.isPlaceOrderActionAllowed(true);
            if (!quote.isVirtual()) {
                this._setShippingMethod(paymentResponse);
                saveShipping.saveShippingInformation();
            }

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
        setShippingBillingAddress: function (payload) {
            if (quote.isVirtual()) {
                this.setBillingAddress(payload);
            } else {
                this.setShippingAddress(payload);
                this.setBillingAddress(payload);
            }
        },

        /**
         * Sets shipping address for quote
         */
        setShippingAddress: function (data) {
            let regionData = null;
            let email = data.paymentData.email;
            let address = data.paymentData.shippingAddress;
            let street = address.address1 + " "+address.address2+" "+address.address3;
            // Get region name and id
            regionData = this.getRegionData(address.administrativeArea, address.countryCode);
            regionData = JSON.parse(regionData);
            // Remove country code from telephone
            let telephone = data.paymentData.paymentMethodData.info.billingAddress.phoneNumber;
            telephone = telephone.substring(telephone.indexOf(" ") + 1);

            // Create name array of address
            let addressNameArray = [];
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');
            let firstname = addressNameArray[0];
            let lastname = null;
            let middlename = null;

            if (addressNameArray.length == 2 ) {
                lastname = addressNameArray[1];
            } else if(addressNameArray.length == 3) {
                lastname = addressNameArray[2];
                middlename = addressNameArray[1];
            }
            let shippingAddress = {
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
                city: address.locality,
                region_id: regionData.region.region_id,
                region_code: regionData.region.code,
                region: regionData.region.name,
                countryId: address.countryCode,
                telephone: telephone,
                postcode: address.postalCode
            };
            shippingAddress = createShippingAddress(shippingAddress);
            quote.shippingAddress(shippingAddress);
            quote.customer_firstname = address.name;
        },

        /**
         * Sets billing address for quote
         */
        setBillingAddress: function (data) {
            let regionData = null;
            let email = data.paymentData.email;
            let address = data.paymentData.paymentMethodData.info.billingAddress;
            let street = address.address1 + " "+address.address2+" "+address.address3;
            // Get region name and id
            regionData = this.getRegionData(address.administrativeArea, address.countryCode);
            regionData = JSON.parse(regionData);
            // Remove country code from telephone
            let telephone = address.phoneNumber;
            telephone = telephone.substring(telephone.indexOf(" ") + 1);

            // Create name array of address
            let addressNameArray  = [];
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');
            let firstname = addressNameArray[0];
            let lastname = null;
            let middlename = null;

            if (addressNameArray.length == 2 ) {
                lastname = addressNameArray[1];
            } else if(addressNameArray.length == 3) {
                lastname = addressNameArray[2];
                middlename = addressNameArray[1];
            }

            let billingAddress = {
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
                city: address.locality,
                region_id: regionData.region.region_id,
                region_code: regionData.region.code,
                region: regionData.region.name,
                countryId: address.countryCode,
                telephone: telephone,
                postcode: address.postalCode
            };
            billingAddress = createBillingAddress(billingAddress);
            quote.billingAddress(billingAddress);
            quote.guestEmail = email;
        },

        /**
         * Get regions by region code or name
         */
        getRegionData: function (administrativeArea, countryCode) {
            let serviceUrl, payload;
            payload = {
                region: administrativeArea,
                country_id: countryCode
            };
            serviceUrl = urlBuilder.build('/cardknox/index/countryregion');
            let response = null;
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
        },

        _setShippingMethod: function (paymentResponse) {
            let shippingOptionDataRes = paymentResponse.paymentData;
            if (shippingOptionDataRes.hasOwnProperty('shippingOptionData')) {
                    if (typeof shippingOptionDataRes.shippingOptionData.id !== 'undefined' ) {
                    let fullShippigName = shippingOptionDataRes.shippingOptionData.id;
                    let shippingMethodArray = fullShippigName.split("__");
                    let shippingCarrierCode = shippingMethodArray[0];
                    let shippingMethodCode = shippingMethodArray[1];

                    // Create the shipping method object
                    let shippingMethod = {
                        'carrier_code': shippingCarrierCode,
                        'method_code': shippingMethodCode
                    };

                    selectShippingMethodAction(shippingMethod);
                }
            }
        },
    });
});
