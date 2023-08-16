/**
 * cardknox Apple Pay
 **/
define(["jquery","ifields","Magento_Checkout/js/model/quote"],function (jQuery,ifields,quote) {
    'use strict';
    var applePayConfig = window.checkoutConfig.payment.cardknox_apple_pay;
    var quoteData = window.checkoutConfig.quoteData;
    var applePay = '';

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

        getTransactionInfo: function () {
            try {
                const amt = getAmount();
                return {
                    total: {
                            type:  'final',
                            label: 'Total',
                            amount: amt.toString(),
                        }
                };                        
            } catch (err) {
                console.error("getTransactionInfo error ", exMsg(err));
            }
        },
        onGetTransactionInfo: function () {
            try {
                return this.getTransactionInfo();
            } catch (err) {
                console.error("onGetTransactionInfo error ", exMsg(err));
            }
        },
        validateApplePayMerchant: function () {
            return new Promise((resolve, reject) => {
                try {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "https://api.cardknox.com/applepay/validate");
                    xhr.onload = function () {
                        if (this.status >= 200 && this.status < 300) {
                            console.log("validateApplePayMerchant", JSON.stringify(xhr.response));
                            resolve(xhr.response);
                        } else {
                            console.error("validateApplePayMerchant", JSON.stringify(xhr.response), this.status);
                            reject({
                                status: this.status,
                                statusText: xhr.response
                            });
                        }
                    };
                    xhr.onerror = function () {
                        console.error("validateApplePayMerchant", xhr.statusText, this.status);
                        reject({
                            status: this.status,
                            statusText: xhr.statusText
                        });
                    };
                    xhr.setRequestHeader("Content-Type", "application/json");
                    xhr.send();
                } catch (err) {
                    setTimeout(function () { console.log("getApplePaySession error: " + exMsg(err)) }, 100);
                }
            });
        },
        onValidateMerchant: function() {
            return new Promise((resolve, reject) => {
                try {
                    this.validateApplePayMerchant()
                    .then((response) => {
                        try {
                            console.log(response);
                            resolve(response);
                        } catch (err) {
                            console.error("validateApplePayMerchant exception.", JSON.stringify(err));
                            reject(err);
                        }
                    })
                    .catch((err) => {
                        console.error("validateApplePayMerchant error.", JSON.stringify(err));
                        reject(err);
                    });
                } catch (err) {
                    console.error("onValidateMerchant error.", JSON.stringify(err));
                    reject(err);
                }
            });
        },
        handleAPError: function(err) {
            if (err && err.xRefNum) {
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
                    const resp = self.getTransactionInfo(null, null, paymentMethod.type);
                    resolve(resp);                            
                } catch (err) {
                    const apErr = {
                        code: "-102",
                        contactField: "",
                        message: exMsg(err)
                    }
                    console.error("onPaymentMethodSelected error.", exMsg(err));
                    reject({errors: [err]});
                }
            })                
        },
        onPaymentAuthorize: function(applePayload) {
            const amt = getAmount();
            return new Promise((resolve, reject) => {
                try {
                    this.authorize(applePayload, amt.toString())
                    .then((response) => {
                        try {
                            console.log(response);
                            const resp = JSON.parse(response);
                            if (!resp)
                                throw "Invalid response: "+ response;
                            if (resp.xError) {
                                throw resp;
                            }
                            resolve(response);
                        } catch (err) {
                            throw err;
                            // reject(err);
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
        authorize: function(applePayload, totalAmount) {
            console.log(applePayload)
            var appToken = applePayload.token.paymentData.data;
            if (appToken) {
                var xcardnum = btoa(JSON.stringify(applePayload.token.paymentData))
                return applePay.startPlaceOrder(xcardnum, totalAmount);
            }
        },
        initAP: function() {
            return {
                buttonOptions: this.buttonOptions,
                merchantIdentifier: "merchant.cardknox.com",
                requiredBillingContactFields: ['postalAddress', 'name', 'phone', 'email'],
                requiredShippingContactFields: ['postalAddress', 'name', 'phone', 'email'],
                onGetTransactionInfo: "apRequest.onGetTransactionInfo",
                onGetShippingMethods: "apRequest.onGetShippingMethods",
                onPaymentMethodSelected: "apRequest.onPaymentMethodSelected",
                onValidateMerchant: "apRequest.onValidateMerchant",
                onPaymentAuthorize: "apRequest.onPaymentAuthorize",
                onPaymentComplete: "apRequest.onPaymentComplete",
                onAPButtonLoaded: "apRequest.apButtonLoaded",
                isDebug: true
            };
        },
        apButtonLoaded: function (resp) {
            if (!resp) return;
            
            if (resp.status === iStatus.success) {
                showHide(this.buttonOptions.buttonContainer, true);
                // showHide("lbAPPayload", true);
            } else if (resp.reason) {
                jQuery(".applepay-error").html("<div>"+resp.reason+"</div>").show();
                console.log(resp.reason);
            } else if(resp.status == -100){
                jQuery(".applepay-error").html("<div> Apple Pay initialization failed Apple Pay not supported</div>").show();
            }
        }
    };

    function showHide(elem, toShow) {
        if (typeof(elem) === "string") {
            elem = document.getElementById(elem);
        }
        if (elem) {
            toShow ? elem.classList.remove("hidden") : elem.classList.add("hidden");
        }
    }
    function getAmount () {
        var totals = quote.totals();
        var base_grand_total = (totals ? totals : quote)['base_grand_total'];
        return parseFloat(base_grand_total).toFixed(2);
    }
    function setAPPayload(value) {
        console.log(value);
    }
    function getApButtonColor(applePayConfig) {
        var apButtonColor = APButtonColor.black;
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
        var apButtonType = APButtonType.pay;
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
        init: function (parent) {
            // No parent
            if (!parent) {
                return;
            }
            applePay = parent;
            if (applePayConfig.merchantIdentifier == "" || applePayConfig.merchantIdentifier == null || applePayConfig.merchantIdentifier.length == 0) {
                jQuery(".applepay-error").html("<div>Please contact support. Failed to initialize Apple Pay. </div>").show();
            } else {
                jQuery('#ap-container').attr('data-ifields-oninit',"window.apRequest.initAP");
                ckApplePay.enableApplePay({
                    initFunction: 'apRequest.initAP',
                    amountField: 'amount'
                });
            }
        }
    };
});
