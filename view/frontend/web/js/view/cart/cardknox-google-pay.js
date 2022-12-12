/**
 * cardknox Google Pay for cart page
 **/
define(["jquery","ifields","Magento_Checkout/js/model/quote"],function (jQuery,ifields,quote) {
    'use strict';
    var gPayConfig = window.checkoutConfig.payment.cardknox_google_pay;
    var quoteData = window.checkoutConfig.quoteData;
    var gPay = '';

    // Google pay object
    window.gpRequest = {
        merchantInfo: {
            merchantName: gPayConfig.merchantName
        },
        buttonOptions: {
            buttonColor: gPayConfig.button ? gPayConfig.button : "default",
            buttonType: GPButtonType.buy,
            buttonSizeMode: GPButtonSizeMode.full
        },
        
        billingParams: {
            emailRequired: window.checkoutConfig.isCustomerLoggedIn == false ? true : false,
            billingAddressRequired: window.checkoutConfig.isCustomerLoggedIn == false ? true : false,
            phoneNumberRequired: window.checkoutConfig.isCustomerLoggedIn == false ? true : false,
            billingAddressFormat: GPBillingAddressFormat.full
        },
        shippingParams: {
            phoneNumberRequired: window.checkoutConfig.isCustomerLoggedIn == false ? true : false,
            emailRequired: window.checkoutConfig.isCustomerLoggedIn == false ? true : false,
        },
        onGetTransactionInfo: function () {
            let amt = getAmount();
            let countryCode = null;
            if(quote.shippingAddress() !== null && quote.shippingAddress() !== undefined ){
                countryCode = quote.shippingAddress().countryId
            } else {
                countryCode = "US"
            }
            return {
                displayItems: [
                    {
                        label: "Grandtotal",
                        type: "SUBTOTAL",
                        price: amt.toString(),
                    }
                ],
                countryCode: countryCode,
                currencyCode: quoteData.base_currency_code.toString(),
                totalPriceStatus: "FINAL",
                totalPrice: amt.toString(),
                totalPriceLabel: "Total"
            }
        },    
        onBeforeProcessPayment: function () {
            return new Promise(function (resolve, reject) {
                try {
                    if (gPay.validate() &&
                        gPay.additionalValidator()
                    ) {
                        // Check shipping method is selected or not in cart summary
                        if ( quote.shippingMethod() !== null && quote.shippingMethod() !== undefined) {
                            // update amount dynamically
                            window.ckGooglePay.updateAmount();
                            resolve(iStatus.success);
                        } else {
                            var err = 'Please select a shipping method.';
                            jQuery(".gpay-error").html("<div>"+err+" </div>").show();
                            setTimeout(function () { 
                                jQuery(".gpay-error").html("").hide();
                            }, 4000);
                            reject(err);
                        }
                    }
                } catch (err) {
                    jQuery(".gpay-error").html("<div> "+err+"</div>").show();
                    setTimeout(function () { 
                        jQuery(".gpay-error").html("").hide();
                    }, 4000);
                    reject(err);
                }
            });
        },
        onProcessPayment: function (paymentResponse) {
            paymentResponse =  JSON.parse(JSON.stringify(paymentResponse));
            var xAmount  = paymentResponse.transactionInfo.totalPrice;
            if (xAmount <= 0) {
                jQuery(".gpay-error").html("<div> Payment is not authorized. Amount must be greater than 0 </div>").show();
                setTimeout(function () { 
                    jQuery(".gpay-error").html("").hide();
                }, 4000);
                throw new Error("Payment is not authorized. Amount must be greater than 0");
            } else {
                var token = btoa(paymentResponse.paymentData.paymentMethodData.tokenizationData.token);
                if (window.checkoutConfig.isCustomerLoggedIn == false) {
                    // Check lastname is exist in shipping address from googlepay response
                    isExistLastNameShippingAddress(paymentResponse);
                    // Check lastname is exist in billing address from googlepay response
                    isExistLastNameBillingAddress(paymentResponse);
                }
                return gPay.startPlaceOrder(token, xAmount, paymentResponse);
            }
        },

        onPaymentCanceled: function(respCanceled) {
            jQuery(".gpay-error").html("<div> Payment was canceled </div>").show();
            setTimeout(function () { 
                jQuery(".gpay-error").html("").hide();
            }, 4000);
        },
        handleResponse: function (resp) {
            const respObj = JSON.parse(resp);
            if (respObj) {
                if (respObj.xError) {
                    setTimeout(function () { console.log(`There was a problem with your order (${respObj.xRefNum})!`) }, 500);
                } else
                    setTimeout(function () { console.log(`Thank you for your order (${respObj.xRefNum})!`) }, 500);
            }
        },
        getGPEnvironment: function () {
            return gPayConfig.GPEnvironment ? gPayConfig.GPEnvironment : "TEST";
        },
        initGP: function() {
            return {
                merchantInfo: this.merchantInfo,
                buttonOptions: this.buttonOptions,
                environment: this.getGPEnvironment(),
                billingParameters: this.billingParams,
                shippingParameters: {
                    emailRequired: this.shippingParams.emailRequired,
                },
                onGetTransactionInfo: "gpRequest.onGetTransactionInfo",
                onBeforeProcessPayment: "gpRequest.onBeforeProcessPayment",
                onProcessPayment: "gpRequest.onProcessPayment",
                onPaymentCanceled: "gpRequest.onPaymentCanceled",
                onGPButtonLoaded: "gpRequest.gpButtonLoaded",
                isDebug: isDebugEnv
            };
        },
        gpButtonLoaded: function(resp) {
            if (!resp) return;
            if (resp.status === iStatus.success) {
                showHide("divGpay", true);
            } else if (resp.reason) {
                jQuery(".gpay-error").html("<div>"+resp.reason+"</div>").show();
            }
        },
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
    function isExistLastNameShippingAddress(data) {
        var address = data.paymentData.shippingAddress;
        var addressNameArray = []; 
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');
        if (addressNameArray.length == 1 ) {
            jQuery(".gpay-error").html("<div>Please check the shipping address information. Lastname is required. Enter and try again.</div>").show();
            setTimeout(function () { 
                jQuery(".gpay-error").html("").hide();
            }, 4000);
            throw new Error("Please check the shipping address information. Lastname is required. Enter and try again.");
        }
    }
    function isExistLastNameBillingAddress(data) {
        var address = data.paymentData.paymentMethodData.info.billingAddress;

        var addressNameArray = []; 
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');

        if (addressNameArray.length == 1 ) {
            jQuery(".gpay-error").html("<div>Please check the billing address information. Lastname is required. Enter and try again.</div>").show();
            setTimeout(function () { 
                jQuery(".gpay-error").html("").hide();
            }, 4000);
            throw new Error("Please check the billing address information. Lastname is required. Enter and try again.");
        }
    }
    return {
        init: function (parent) {
            // No parent
            if (!parent) {
                return;
            }
            gPay = parent;
            if (gPayConfig.merchantName == "" || gPayConfig.merchantName == null || gPayConfig.merchantName.length == 0) {
                jQuery(".gpay-error").html("<div>Please contact support. Failed to initialize Google Pay.</div>").show();
            } else {
                jQuery('#igp').attr('data-ifields-oninit',"window.gpRequest.initGP");
                ckGooglePay.enableGooglePay();
            }
        }
    };
});