/**
 * cardknox Google Pay for cart page
 **/
define([
    "jquery",
    "ifields",
    "Magento_Checkout/js/model/quote"
],function ($,ifields,quote) {
    'use strict';
    let gPayConfig = window.checkoutConfig.payment.cardknox_google_pay;
    let quoteData = window.checkoutConfig.quoteData;
    let gPay = '';


    // Google pay object
    window.gpRequest = {

        totalAmount: null,

        merchantInfo: {
            merchantName: gPayConfig.merchantName
        },
        buttonOptions: {
            buttonColor: gPayConfig.button ? gPayConfig.button : "default",
            buttonType: gPayConfig.buttonType ? gPayConfig.buttonType : "buy",
            buttonSizeMode: gPayConfig.buttonSizeMode ? gPayConfig.buttonSizeMode : "fill"
        },

        billingParams: {
            emailRequired: !window.checkoutConfig.isCustomerLoggedIn,
            billingAddressRequired: true,
            phoneNumberRequired: true,
            billingAddressFormat: GPBillingAddressFormat.full
        },

        shippingParams: {
            shippingAddressRequired: true,
            phoneNumberRequired: true,
            emailRequired: !window.checkoutConfig.isCustomerLoggedIn,
        },

        onGetTransactionInfo: function (shippingData) {
            return this._getTransactionInfo(shippingData);
        },

        _getTransactionInfo: function (shippingData) {
            let countryCode = null;
            const subTotal = getSubTotal();

            if (quote.shippingAddress() !== null && quote.shippingAddress() !== undefined) {
                countryCode = quote.shippingAddress().countryId
            } else {
                countryCode = 'US';
            }
            let currencyCode = quoteData.base_currency_code.toString();
            const isEnabledShowSummary = gPayConfig.isEnabledGooglePayShowSummary ? gPayConfig.isEnabledGooglePayShowSummary : "";

            if (!isEnabledShowSummary) {
                let grandTotalAmount = getGrandTotalAmount();
                return {
                    displayItems: [
                        {
                            label: "Grandtotal",
                            type: "SUBTOTAL",
                            price: grandTotalAmount.toString(),
                        }
                    ],
                    countryCode: countryCode,
                    currencyCode: currencyCode,
                    totalPriceStatus: "FINAL",
                    totalPrice: grandTotalAmount.toString(),
                    totalPriceLabel: "Total"
                }
            }

            let taxAmount = getTaxAmount();
            let discountAmount = getDiscountAmount();

            let shippingPrice = getShippingPrice();

            const lineItems = [
                {
                    label: isEnabledShowSummary ? 'Subtotal' :'',
                    type: 'SUBTOTAL',
                    price: subTotal.toString(),
                },
                {
                    label: isEnabledShowSummary ? 'Shipping' :'',
                    type: 'LINE_ITEM',
                    price: shippingPrice.toString(),
                }
            ];

            const taxLineItem = {
                label: isEnabledShowSummary ? 'Tax' :'',
                type: 'TAX',
                price: taxAmount.toString(),
            };

            if (this.discountAmt != 0) {
                lineItems.push({
                    label: isEnabledShowSummary ? 'Discount' :'',
                    type: 'LINE_ITEM',
                    price: discountAmount.toString()
                });
            }

            const isEnabledCardknoxGiftcard = window.checkoutConfig.payment.cardknox.isEnabledCardknoxGiftcard;

            if (isEnabledCardknoxGiftcard) {
                let giftcardAmount = getGiftcardAmount();
                if (giftcardAmount > 0) {
                    giftcardAmount = -giftcardAmount;
                    lineItems.push({
                        label: isEnabledShowSummary ? 'Gift Card' :'',
                        type: 'LINE_ITEM',
                        price: giftcardAmount.toString()
                    });
                }
            }
            lineItems.push(taxLineItem);

            let totalAmount = 0;
            lineItems.forEach((item) => {
                totalAmount += parseFloat(item.price) || 0;
            });
            totalAmount = roundTo(totalAmount, 2);

            return {
                displayItems: lineItems,
                countryCode: countryCode,
                currencyCode: currencyCode,
                totalPriceStatus: 'FINAL',
                totalPrice: totalAmount,
                totalPriceLabel: 'GrandTotal'
            }
        },

        onBeforeProcessPayment: function () {
            return new Promise(function (resolve, reject) {
                try {
                    if (gPay.validate() && gPay.additionalValidator()) {
                        if (!quote.isVirtual() && quote.shippingMethod() == null) {
                            let err = 'Please select a shipping method.';
                            $(".gpay-error").html("<div>"+err+" </div>").show();
                            setTimeout(function () {
                                $(".gpay-error").html("").hide();
                            }, 4000);
                            reject(err);
                        }
                        // update amount dynamically
                        window.ckGooglePay.updateAmount();
                        resolve(iStatus.success);
                    }
                } catch (err) {
                    $(".gpay-error").html("<div> "+err+"</div>").show();
                    setTimeout(function () {
                        $(".gpay-error").html("").hide();
                    }, 4000);
                    reject(err);
                }
            });
        },
        onProcessPayment: function (paymentResponse) {
            paymentResponse =  JSON.parse(JSON.stringify(paymentResponse));
            let xAmount  = paymentResponse.transactionInfo.totalPrice;
            if (xAmount <= 0) {
                $(".gpay-error").html("<div> Payment is not authorized. Amount must be greater than 0 </div>").show();
                setTimeout(function () {
                    $(".gpay-error").html("").hide();
                }, 4000);
                throw new Error("Payment is not authorized. Amount must be greater than 0");
            } else {
                let token = btoa(paymentResponse.paymentData.paymentMethodData.tokenizationData.token);
                if (!window.checkoutConfig.isCustomerLoggedIn) {
                    // Check lastname is exist in shipping address from googlepay response
                    isExistLastNameShippingAddress(paymentResponse);
                    // Check lastname is exist in billing address from googlepay response
                    isExistLastNameBillingAddress(paymentResponse);
                }
                return gPay.startPlaceOrder(token, xAmount, paymentResponse);
            }
        },

        onPaymentCanceled: function(respCanceled) {
            $(".gpay-error").html("<div> Payment was canceled </div>").show();
            setTimeout(function () {
                $(".gpay-error").html("").hide();
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

        getShippingParams() {
            let data = {
                emailRequired: this.shippingParams.emailRequired
            };
            return data;
        },

        initGP: function() {
            return {
                merchantInfo: this.merchantInfo,
                buttonOptions: this.buttonOptions,
                environment: this.getGPEnvironment(),
                billingParameters: this.billingParams,
                shippingParameters: this.getShippingParams(),
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
                $(".gpay-error").html("<div>"+resp.reason+"</div>").show();
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

    function getDiscountAmount() {
        const totals = quote.totals(),
            base_discount = (totals || quote)['base_discount_amount'];

        return parseFloat(base_discount).toFixed(2);
    }

    function getSubTotal() {
        const totals = quote.totals(),
            base_subtotal = (totals || quote)['base_subtotal'];
        return parseFloat(base_subtotal).toFixed(2);
    }

    function getTaxAmount() {
        const totals = quote.totals(),
            tax = (totals || quote)['tax_amount'];
        return parseFloat(tax).toFixed(2);
    }

    function getShippingPrice() {
        const totals = quote.totals(),
            shipping_amount = (totals || quote)['shipping_amount'];
        return parseFloat(shipping_amount).toFixed(2);
    }
    function getGiftcardAmount() {
        let totalSegments = quote.totals()['total_segments'];
        let giftcard_amount = 0;
        if (totalSegments && totalSegments.length) {
            // Find the segment with code 'ckgiftcard'
            let giftCardSegment = totalSegments.find(function (segment) {
                return segment.code === 'ckgiftcard';
            });

            if (giftCardSegment) {
                giftcard_amount = giftCardSegment.value;
            }
        }
        return parseFloat(giftcard_amount).toFixed(2);
    }
    function getGrandTotalAmount() {
        let totals = quote.totals();
        let base_grand_total = (totals ? totals : quote)['base_grand_total'];
        return parseFloat(base_grand_total).toFixed(2);
    }
    function isExistLastNameShippingAddress(data) {
        let address = data.paymentData.shippingAddress;
        let addressNameArray = [];
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');
        if (addressNameArray.length == 1 ) {
            $(".gpay-error").html("<div>Please check the shipping address information. Lastname is required. Enter and try again.</div>").show();
            setTimeout(function () {
                $(".gpay-error").html("").hide();
            }, 4000);
            throw new Error("Please check the shipping address information. Lastname is required. Enter and try again.");
        }
    }
    function isExistLastNameBillingAddress(data) {
        let address = data.paymentData.paymentMethodData.info.billingAddress;

        let addressNameArray = [];
            addressNameArray = address.name.replace("[","").replace("]","").split(' ');

        if (addressNameArray.length == 1 ) {
            $(".gpay-error").html("<div>Please check the billing address information. Lastname is required. Enter and try again.</div>").show();
            setTimeout(function () {
                $(".gpay-error").html("").hide();
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
                $(".gpay-error").html("<div>Please contact support. Failed to initialize Google Pay.</div>").show();
            } else {
                $('#igp').attr('data-ifields-oninit',"window.gpRequest.initGP");
                ckGooglePay.enableGooglePay();
            }
        }
    };
});
