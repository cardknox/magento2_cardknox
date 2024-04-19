/**
 * cardknox Apple Pay
 **/
define([
    "jquery",
    "ifields",
    "Magento_Checkout/js/model/quote",
    'CardknoxDevelopment_Cardknox/js/model/shipping-rates',
    'CardknoxDevelopment_Cardknox/js/model/tax'
],function ($,ifields,quote, shippingRates, taxCalculator) {
    'use strict';
    let applePayConfig = window.checkoutConfig.payment.cardknox_apple_pay;
    let applePay = '';
    let lastSelectedShippingMethod = '';

    // Apple pay object
    window.apRequest = {
        merchantInfo: {
            merchantIdentifier: applePayConfig.merchantIdentifier
        },

        buttonOptions: {
            buttonContainer: "ap-container",
            buttonColor: getApButtonColor(applePayConfig),
            buttonType: getApButtonType(applePayConfig)
        },
        totalAmount: null,
        taxAmt: null,
        shippingMethod: null,
        creditType: null,

        _getTransactionInfo: function (shippingMethod, creditType) {
            if (quoteIsVirtual()) {
                try {
                    const apAmt = _getAmount();
                    return {
                        total: {
                                type:  'final',
                                label: 'Total',
                                amount: apAmt.toString(),
                            }
                    };
                } catch (err) {
                    console.error("getTransactionInfo error ", exMsg(err));
                }
            }
            try {
                if (null !== shippingMethod) {
                    this.shippingMethod = shippingMethod
                }

                if (!this.taxAmt) {
                    this.taxAmt = getTax();
                }

                if (!this.shippingMethod) {
                    const shippingOptions = this._getShippingMethods();
                    this.shippingMethod = shippingOptions[0] || {};
                }

                this.creditType = creditType || this.creditType;
                const amt = getSubTotal();
                const lineItems = [
                    {
                        "label": "Subtotal",
                        "type": "final",
                        "amount": amt
                    },
                    this.shippingMethod
                ];

                const discountAmount = this.discountAmt;
                if (discountAmount != 0) {
                    lineItems.push({
                        label: 'Discount',
                        type: 'final',
                        amount: discountAmount
                    });
                }

                lineItems.push({
                    "label": "Estimated Tax",
                    "amount": this.taxAmt,
                    "type": "final"
                });

                let totalAmt = 0;
                lineItems.forEach((item) => {
                    totalAmt += parseFloat(item.amount) || 0;
                });
                totalAmt = roundTo(totalAmt, 2);

                this.totalAmount = totalAmt;
                return {
                    lineItems: lineItems,

                    total: {
                        type:  'final',
                        label: 'Total',
                        amount: totalAmt,
                    }
                };
            } catch (err) {
                console.error("_getTransactionInfo error ", exMsg(err));
                if (isDebugEnv) {
                    alert("_getTransactionInfo error: "+exMsg(err));
                }
            }
        },

        onGetTransactionInfo: function (paymentData) {
            try {
                const totalAmt = _getAmount();

                return {
                    lineItems: [],

                    total: {
                        type:  'final',
                        label: 'Total',
                        amount: totalAmt,
                    }
                };
            } catch (err) {
                console.error("onGetTransactionInfo error ", exMsg(err));
            }
        },

        onGetShippingMethods: function (address) {
            return this._getShippingMethods(address);
        },

        _getShippingMethods: function (address) {
            if (typeof address === 'undefined') {
                return [];
            }

            const result = shippingRates.getRates(address);
            let shippingOptions = [];
            if (result.length) {
                $.each(result, function (idx, item) {
                    const id = item.carrier_code + '__' + item.method_code;

                    shippingOptions.push({
                        identifier: id,
                        label: item.method_title,
                        amount: item.price_incl_tax,
                        detail: item.carrier_title
                    });
                });
            }

            return shippingOptions;
        },

        onShippingContactSelected: function (shippingContact) {
            const self = this;
            return new Promise(function (resolve, reject) {
                try {
                    const address = self._constructAddressFromShippingContact(shippingContact);

                    if (!address) {
                        return reject(new Error('Invalid shipping contact'));
                    }

                    const resp = self._processShippingAddress(address);
                    resolve(resp);
                } catch (err) {
                    console.error("onShippingContactSelected error:", err.message);
                    if (isDebugEnv) {
                        setTimeout(function () { alert("onShippingContactSelected error: " + err.message) }, 100);
                    }
                    reject(new Error("Exception : " + err.message));
                }
            });
        },

        _constructAddressFromShippingContact: function (shippingContact) {
            let countryCode = 'US';
            if (quote.shippingAddress() !== null && quote.shippingAddress() !== undefined) {
                countryCode = quote.shippingAddress().countryId
            }

            const address = {
                countryId: countryCode,
                city: '',
                postcode: '',
                region: ''
            };

            if (shippingContact) {
                if (shippingContact.countryCode) {
                    address.countryId = shippingContact.countryCode;
                }
                if (shippingContact.locality) {
                    address.city = shippingContact.locality;
                }
                if (shippingContact.postalCode) {
                    address.postcode = shippingContact.postalCode;
                }
                if (shippingContact.administrativeArea) {
                    address.region = shippingContact.administrativeArea;
                }
            }

            return address;
        },

        _processShippingAddress: function (address) {
            const self = this;
            let resp = {};

            if (!applePay._validate(address.countryId)) {
                const apErr = {
                    code: APErrorCode.shippingContactInvalid,
                    contactField: APErrorContactField.countryCode,
                    msgMessage: 'Shipping to country not supported, Unable to process the order. Please try again.'
                }

                resp = self._getTransactionInfo();
                resp.shippingMethods = [];
                resp.error = apErr;
            } else {
                const newShippingMethods = self._getShippingMethods(address);
                const taxObj = self._calculateTax(address, newShippingMethods[0]);

                if (taxObj.hasOwnProperty('tax_amount')) {
                    self.taxAmt = taxObj['tax_amount'];
                }
                if (taxObj.hasOwnProperty('base_discount_amount')) {
                    self.discountAmt = taxObj['base_discount_amount'];
                }

                resp = self._getTransactionInfo(newShippingMethods[0]);
                resp.shippingMethods = newShippingMethods;
            }

            return resp;
        },

        _calculateTax: function (address, shippingMethod) {
            return taxCalculator({
                address: address,
                shippingMethod: shippingMethod
            });
        },

        onShippingMethodSelected: function(shippingMethod) {
            const self = this;
            return new Promise(function (resolve, reject) {
                try {
                    lastSelectedShippingMethod = shippingMethod;
                    const resp = self._getTransactionInfo(shippingMethod);
                    resolve(resp);
                } catch (err) {
                    console.error("onShippingMethodSelected error.", exMsg(err));
                    reject(new Error("Exception : " + err.message));
                }
            })
        },

        _validateApplePayMerchant: function () {
            return new Promise((resolve, reject) => {
                try {
                    let xhr = new XMLHttpRequest();
                    xhr.open("POST", "https://api.cardknox.com/applepay/validate");
                    xhr.onload = function () {
                        if (this.status >= 200 && this.status < 300) {
                            console.log("validateApplePayMerchant", JSON.stringify(xhr.response));
                            resolve(xhr.response);
                        } else {
                            console.error("validateApplePayMerchant", JSON.stringify(xhr.response), this.status);
                            reject(new Error("Failed to validate Apple Pay Merchant: " + xhr.statusText));
                        }
                    };
                    xhr.onerror = function () {
                        console.error("validateApplePayMerchant", xhr.statusText, this.status);
                        reject(new Error("Network Error: " + xhr.statusText));
                    };
                    xhr.setRequestHeader("Content-Type", "application/json");
                    xhr.send();
                } catch (err) {
                    // Correctly pass Error object to reject
                    reject(new Error("Exception in validateApplePayMerchant: " + err.message));
                    // Ensure exMsg function exists or use err.message directly
                    setTimeout(function () { console.log("getApplePaySession error: " + err.message) }, 100);
                }
            });
        },

        onValidateMerchant: function() {
            return new Promise((resolve, reject) => {
                try {
                    this._validateApplePayMerchant()
                    .then((_response) => {
                        try {
                            console.log(_response);
                            resolve(_response);
                        } catch (err) {
                            _errorOnValidateMerchant("validateApplePayMerchant exception." + JSON.stringify(err), err);
                        }
                    })
                    .catch((err) => {
                        _errorOnValidateMerchant("validateApplePayMerchant error." + JSON.stringify(err), err);
                    });
                } catch (err) {
                    _errorOnValidateMerchant("onValidateMerchant error." + JSON.stringify(err), err);
                }
            });
        },

        handleAPError: function(err) {
            if (err?.xRefNum) {
                setAPPayload("There was a problem with your order ("+err.xRefNum+")");
            } else {
                setAPPayload("There was a problem with your order ("+exMsg(err)+")");
            }
        },

        onPaymentMethodSelected: function(paymentMethod) {
            const self = this;
            return new Promise((resolve, reject) => {
                try {
                    console.log("paymentMethod", JSON.stringify(paymentMethod));
                    const resp = self._getTransactionInfo(null, null, paymentMethod.type);
                    resolve(resp);
                } catch (err) {
                    console.error("onPaymentMethodSelected error.", exMsg(err));
                    const error = new Error("onPaymentMethodSelected error: " + exMsg(err));
                    error.originalError = err;
                    reject(error);
                }
            })
        },

        onPaymentAuthorize: function(applePayload) {
            const amt = _getAmount();
            return new Promise((resolve, reject) => {
                try {
                    this.authorize(applePayload, amt.toString())
                    .then((response) => {
                        try {
                            console.log(response);
                            const resp = JSON.parse(response);
                            if (!resp)
                                throw new Error("Invalid response: " + response);
                            if (resp.xError) {
                                throw new Error("Error from response: " + JSON.stringify(resp));
                            }
                            resolve(response);
                        } catch (err) {
                            reject(err);
                        }
                    })
                    .catch((err) => {
                        console.error("authorizeAPay error.", JSON.stringify(err));
                        apRequest.handleAPError(err);
                        reject(err);
                    });
                } catch (err) {
                    console.error("onPaymentAuthorize error.", JSON.stringify(err));
                    apRequest.handleAPError(err);
                    reject(err);
                }
            });
        },

        onBeforeProcessPayment: function () {
            return new Promise(function (resolve, reject) {
                try {
                    if (applePay.validate()
                    ) {
                        window.ckApplePay.updateAmount();
                        resolve(iStatus.success);
                    }
                } catch (err) {
                    _errorShowMessage(err, reject);
                }
            });
        },

        authorize: function(applePayload, totalAmount) {
            let appToken = applePayload.token.paymentData.data;
            if (appToken) {
                let xcardnum = btoa(JSON.stringify(applePayload.token.paymentData));
                if (!window.checkoutConfig.isCustomerLoggedIn) {
                    // Check lastname is exist in shipping address from applepay response
                    isExistLastNameShippingAddress(applePayload);
                    // Check lastname is exist in billing address from applepay response
                    isExistLastNameBillingAddress(applePayload);
                }
                return applePay.startPlaceOrder(xcardnum, totalAmount, applePayload, lastSelectedShippingMethod);
            }
        },

        initAP: function() {
            return {
                buttonOptions: this.buttonOptions,
                merchantIdentifier: "merchant.cardknox.com",
                requiredFeatures: [APRequiredFeatures.address_validation],
                requiredBillingContactFields: ['postalAddress', 'name', 'phone', 'email'],
                requiredShippingContactFields: ['postalAddress', 'name', 'phone', 'email'],
                onGetTransactionInfo: "apRequest.onGetTransactionInfo",
                onGetShippingMethods: quoteIsVirtual() ? null : "apRequest.onGetShippingMethods",
                onPaymentMethodSelected: "apRequest.onPaymentMethodSelected",
                onShippingContactSelected: quoteIsVirtual() ? null : "apRequest.onShippingContactSelected",
                onShippingMethodSelected: quoteIsVirtual() ? null : "apRequest.onShippingMethodSelected",
                onValidateMerchant: "apRequest.onValidateMerchant",
                onBeforeProcessPayment: "apRequest.onBeforeProcessPayment",
                onPaymentAuthorize: "apRequest.onPaymentAuthorize",
                onPaymentComplete: "apRequest.onPaymentComplete",
                onAPButtonLoaded: "apRequest.apButtonLoaded",
                isDebug: true
            };
        },
        apButtonLoaded: function (resp) {
            if (!resp) return;

            if (resp.status === iStatus.success) {
                _showHide(this.buttonOptions.buttonContainer, true);
            } else if (resp.reason) {
                $(".applepay-error").html("<div>"+resp.reason+"</div>").show();
                console.log(resp.reason);
            } else if(resp.status == -100){
                console.error("Apple Pay initialization failed. Apple Pay not supported");
            }
        }
    };

    function quoteIsVirtual() {
        return quote.isVirtual();
    }
    function _showHide(elem, toShow) {
        if (typeof(elem) === "string") {
            elem = document.getElementById(elem);
        }
        if (elem) {
            toShow ? elem.classList.remove("hidden") : elem.classList.add("hidden");
        }
    }

    function _errorOnValidateMerchant(consoleErrorMsg, err) {
        console.error(consoleErrorMsg);
        reject(err);
    }

    function _errorShowMessage(err, reject) {
        $(".applepay-error").html("<div> "+err+"</div>").show();
        setTimeout(function () {
            $(".applepay-error").html("").hide();
        }, 4000);
        reject(err);
    }

    function _getAmount () {
        let totals = quote.totals();
        let base_grand_total = (totals || quote)['base_grand_total'];
        return parseFloat(base_grand_total).toFixed(2);
    }
    function isExistLastNameShippingAddress(data) {
        let lastname = data.shippingContact.familyName;
        if (!lastname || lastname.trim().length === 0) {
            $(".applepay-error").html("<div>Please check the shipping address information. Lastname is required. Enter and try again.</div>").show();
            setTimeout(function () {
                $(".applepay-error").html("").hide();
            }, 4000);
            throw new Error("Please check the shipping address information. Lastname is required. Enter and try again.");
        }
    }
    function isExistLastNameBillingAddress(data) {
        let lastname = data.billingContact.familyName;
        if (!lastname || lastname.trim().length === 0) {
            $(".applepay-error").html("<div>Please check the billing address information. Lastname is required. Enter and try again.</div>").show();
            setTimeout(function () {
                $(".applepay-error").html("").hide();
            }, 4000);
            throw new Error("Please check the billing address information. Lastname is required. Enter and try again.");
        }
    }

    function getSubTotal() {
        const totals = quote.totals(),
            base_subtotal = (totals || quote)['base_subtotal'];
        return parseFloat(base_subtotal).toFixed(2);
    }

    function getTax() {
        const totals = quote.totals(),
            tax = (totals || quote)['tax_amount'];
        return parseFloat(tax).toFixed(2);
    }

    function setAPPayload(value) {
        console.log(value);
    }
    function getApButtonColor(applePayConfig) {
        let apButtonColor = '';
        switch(applePayConfig.button) {
            case "black":
                apButtonColor = APButtonColor.black;
            break;
            case "white":
                apButtonColor = APButtonColor.white;
            break;
            case "whiteOutline":
                apButtonColor = APButtonColor.whiteOutline;
                break;
            default:
                apButtonColor = APButtonColor.black;
        }

        return apButtonColor;
    }
    function getApButtonType(applePayConfig) {
        let apButtonType = '';
        switch(applePayConfig.type) {
            case "pay":
                apButtonType = APButtonType.pay;
                break;
            case "buy":
                apButtonType = APButtonType.buy;
                break;
            case "plain":
                apButtonType = APButtonType.plain;
                break;
            case "order":
                apButtonType = APButtonType.order;
                break;
            case "donate":
                apButtonType = APButtonType.donate;
                break;
            case "continue":
                apButtonType = APButtonType.continue;
                break;
            case "checkout":
                apButtonType = APButtonType.checkout;
                break;
            default:
                apButtonType = APButtonType.pay;
        }

        return apButtonType;
    }
    return {
        initApplePay: function (parent) {
            // No parent
            if (!parent) {
                return;
            }
            applePay = parent;
            if (!applePayConfig.merchantIdentifier) {
                $(".applepay-error").html("<div>Please contact support. Failed to initialize Apple Pay. </div>").show();
            } else {
                $('#ap-container').attr('data-ifields-oninit',"window.apRequest.initAP");
                ckApplePay.enableApplePay({
                    initFunction: 'apRequest.initAP',
                    amountField: 'amount'
                });
            }
        }
    };
});
