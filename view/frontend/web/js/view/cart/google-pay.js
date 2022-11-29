define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'ko',
    'Magento_Checkout/js/model/quote',
    'CardknoxDevelopment_Cardknox/js/view/cart/cardknox-google-pay',
    'ifields',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/place-order',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/create-shipping-address',
    'mage/url',
    'Magento_Checkout/js/model/shipping-save-processor/default'
], function(
    $,
    Component,
    ko,
    quote,
    cardknoxGpay,
    ifields,
    additionalValidators,
    placeOrderAction,
    customer,
    createBillingAddress,
    createShippingAddress,
    urlBuilder,
    saveShipping
) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'CardknoxDevelopment_Cardknox/cart/google-pay-btn'
        },
        initialize: function() {
            this._super();
            this.initCardknox();
            this.customerIsLoggedIn();
        },
        initCardknox: function () {
            setAccount(window.checkoutConfig.payment.cardknox_google_pay.xKey, "Magento2", "1.0.14");
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
            var shipping_address_firstname = null;

            if (!this.customerIsLoggedIn()) {
                shipping_address_firstname = quote.shippingAddress().firstname;
            }
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'xCardNum': this.paymentMethodNonce,
                    'xAmount': this.xAmount,
                    'shipping_address_firstname': shipping_address_firstname
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
         * Google pay place order method
         */
        startPlaceOrder: function (nonce, xAmount, paymentResponse) {
            $("body").trigger('processStart');
            if (!this.customerIsLoggedIn()) {
                // Sets shipping and billing address for guest
                this.onPaymentMethodReceived(paymentResponse);
            }
            this.xAmount = xAmount ;
            this.setPaymentMethodNonce(nonce);
            this.isPlaceOrderActionAllowed(true);
            saveShipping.saveShippingInformation();
            this.placeOrder();
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
            var email = data.paymentData.email;
            var address = data.paymentData.shippingAddress;
            var street = address.address1 + " "+address.address2+" "+address.address3;
            // Get region name and id
            regionData = this.getRegionData(address.administrativeArea, address.countryCode);
            regionData = JSON.parse(regionData);
            // Remove country code from telephone
            var telephone = data.paymentData.paymentMethodData.info.billingAddress.phoneNumber;
            telephone = telephone.substring(telephone.indexOf(" ") + 1);

            // Create name array of address
            var addressNameArray = []; 
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');
            var firstname = addressNameArray[0];
            var lastname = null;
            var middlename = null;

            if (addressNameArray.length == 2 ) {
                lastname = addressNameArray[1];
            } else if(addressNameArray.length == 3) {
                lastname = addressNameArray[2];
                middlename = addressNameArray[1];
            }
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
            var regionData = null;
            var email = data.paymentData.email;
            var address = data.paymentData.paymentMethodData.info.billingAddress;
            var street = address.address1 + " "+address.address2+" "+address.address3;
            // Get region name and id
            regionData = this.getRegionData(address.administrativeArea, address.countryCode);
            regionData = JSON.parse(regionData);
            // Remove country code from telephone
            var telephone = address.phoneNumber;
            telephone = telephone.substring(telephone.indexOf(" ") + 1);

            // Create name array of address
            var addressNameArray  = [];
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');
            var firstname = addressNameArray[0];
            var lastname = null;
            var middlename = null;

            if (addressNameArray.length == 2 ) {
                lastname = addressNameArray[1];
            } else if(addressNameArray.length == 3) {
                lastname = addressNameArray[2];
                middlename = addressNameArray[1];
            }

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
                city: address.locality,
                region_id: regionData.region.region_id,
                region_code: regionData.region.code,
                region: regionData.region.name,
                countryId: address.countryCode,
                telephone: address.phoneNumber,
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
        },
    });
});