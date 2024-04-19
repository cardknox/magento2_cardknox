/**
 * cardknox Google Pay for cart page
 **/
define([
    "jquery",
    "ifields",
    "Magento_Checkout/js/model/quote",
    "CardknoxDevelopment_Cardknox/js/model/shipping-rates",
    "CardknoxDevelopment_Cardknox/js/model/tax",
    "Magento_Catalog/js/price-utils"
],function ($,ifields,quote, shippingRates, taxCalculator, utils) {
    'use strict';
    let gPayConfig = window.checkoutConfig.payment.cardknox_google_pay;
    let quoteData = window.checkoutConfig.quoteData;
    let gPay = '';

    const roundTo = (total, digits) => {
        return parseFloat(total).toFixed(digits);
    }

    // Google pay object
    window.gpRequest = {

        totalAmount: null,
        taxAmt: null,
        discountAmt: 0,
        shippingMethod: null,
        _shippingOptions: [],

        merchantInfo: {
            merchantName: gPayConfig.merchantName
        },
        buttonOptions: {
            buttonColor: gPayConfig.button ? gPayConfig.button : "default",
            buttonType: gPayConfig.buttonType ? gPayConfig.buttonType : "buy",
            buttonSizeMode: GPButtonSizeMode.fill
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
            onGetShippingCosts: function (shippingData) {
                if (typeof shippingData !== 'undefined') {
                    logDebug({
                        label: "onGetShippingCosts",
                        data: shippingData
                    });
                }

                const address = {
                    countryId: shippingData.shippingAddress.countryCode,
                    city: shippingData.shippingAddress.locality,
                    postcode: shippingData.shippingAddress.postalCode,
                    region: shippingData.shippingAddress.administrativeArea
                }

                const shippingCosts = {};
                const result = shippingRates.getRates(address);

                if (result.length) {
                    $.each(result, function (idx, item) {
                        const k = item.carrier_code + '__' + item.method_code;
                        shippingCosts[k] = item.price_incl_tax.toString();
                    });
                }

                return shippingCosts;
            },

            onGetShippingOptions: function (shippingData) {
                return gpRequest._getShippingOptions(shippingData);
            }
        },

        onGetTransactionInfo: function (shippingData) {
            return this._getTransactionInfo(shippingData);
        },

        _getShippingOptions: function (shippingData) {
            const _self = this;

            if (typeof shippingData !== 'undefined') {
                logDebug({
                    label: "onGetShippingOptions",
                    data: shippingData
                });
            }
            let selectedOptionId = '';

            if (shippingData && shippingData.shippingOptionData && shippingData.shippingOptionData.id !== 'shipping_option_unselected') {
                selectedOptionId = shippingData.shippingOptionData.id;
            }
            const shippingOptions = {
                defaultSelectedOptionId: selectedOptionId,
                shippingOptions: []
            };

            if (!shippingData || !shippingData.shippingAddress) {
                return shippingOptions;
            }
            const address = {
                countryId: shippingData.shippingAddress.countryCode,
                city: shippingData.shippingAddress.locality,
                postcode: shippingData.shippingAddress.postalCode,
                region: shippingData.shippingAddress.administrativeArea
            }

            const result = shippingRates.getRates(address);

            this._shippingOptions = [];

            if (result.length) {
                $.each(result, function (idx, item) {
                    const id = item.carrier_code + '__' + item.method_code;

                    if (!selectedOptionId && idx === 0) {
                        selectedOptionId = id;
                    }

                    const option = {
                        id: id,
                        label: item.method_title + ' - ' + utils.formatPrice(item.price_incl_tax),
                        description: item.carrier_title
                    };

                    shippingOptions.shippingOptions.push(option);

                    if (selectedOptionId === id) {
                        _self.shippingMethod = option;
                    }

                    _self._shippingOptions.push($.extend({}, option, {amount: item.price_incl_tax}));
                });
            }
            shippingOptions['defaultSelectedOptionId'] = selectedOptionId;
            return shippingOptions;
        },

        _getTransactionInfo: function (shippingData) {
            let countryCode = null;
            const subTotal = getSubTotal();

            if (quote.shippingAddress() !== null && quote.shippingAddress() !== undefined) {
                countryCode = quote.shippingAddress().countryId
            } else {
                countryCode = 'US';
            }

            this.taxAmt = getTax();
            this.discountAmt = getDiscount();

            if (typeof shippingData !== 'undefined' && shippingData.hasOwnProperty('shippingAddress')) {

                // get shipping
                this._getShippingOptions(shippingData);

                if (shippingData.shippingOptionData && shippingData.shippingOptionData.id === 'shipping_option_unselected') {
                    this.shippingMethod = this._shippingOptions[0];
                } else {
                    this.shippingMethod = _.find(this._shippingOptions, function (item) {
                        return item.id === shippingData.shippingOptionData.id;
                    })
                }

                const address = {
                    countryId: shippingData.shippingAddress.countryCode,
                    city: shippingData.shippingAddress.locality,
                    postcode: shippingData.shippingAddress.postalCode,
                    region: shippingData.shippingAddress.administrativeArea
                }

                // calculate tax
                const taxObj = taxCalculator(
                    {
                        address: address,
                        shippingMethod: this.shippingMethod
                    }
                );

                if (taxObj.hasOwnProperty('tax_amount')) {
                    this.taxAmt = taxObj['tax_amount'];
                }

                if (taxObj.hasOwnProperty('base_discount_amount')) {
                    this.discountAmt = taxObj['base_discount_amount'];
                }
            }

            let shippingPrice = getShippingPrice();
            if (null !== this.shippingMethod && typeof this.shippingMethod === 'object') {
                shippingPrice = parseFloat(this.shippingMethod['amount']) || 0;
            }

            const lineItems = [
                {
                    label: 'Subtotal',
                    type: 'SUBTOTAL',
                    price: subTotal.toString(),
                },
                {
                    label: 'Shipping',
                    type: 'LINE_ITEM',
                    price: shippingPrice.toString(),
                }
            ];

            const taxLineItem = {
                label: 'Tax',
                type: 'TAX',
                price: this.taxAmt.toString(),
            };

            if (this.discountAmt != 0) {
                lineItems.push({
                    label: 'Discount',
                    type: 'LINE_ITEM',
                    price: this.discountAmt.toString()
                });
            }

            lineItems.push(taxLineItem);

            let totalAmt = 0;
            lineItems.forEach((item) => {
                totalAmt += parseFloat(item.price) || 0;
            });
            totalAmt = roundTo(totalAmt, 2);

            return {
                displayItems: lineItems,
                countryCode: countryCode,
                currencyCode: quoteData.base_currency_code.toString(),
                totalPriceStatus: 'FINAL',
                totalPrice: totalAmt,
                totalPriceLabel: 'Total'
            }
        },

        onBeforeProcessPayment: function () {
            return new Promise(function (resolve, reject) {
                try {
                    if (gPay.validate()) {
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

        getShippingCostAndOptions() {
            let data = {
                emailRequired: this.shippingParams.emailRequired
            };

            if (!quote.isVirtual()) {
                data.onGetShippingCosts = "gpRequest.shippingParams.onGetShippingCosts";
                data.onGetShippingOptions = "gpRequest.shippingParams.onGetShippingOptions";
            }
            return data;
        },

        initGP: function() {
            return {
                merchantInfo: this.merchantInfo,
                buttonOptions: this.buttonOptions,
                environment: this.getGPEnvironment(),
                billingParameters: this.billingParams,
                shippingParameters: this.getShippingCostAndOptions(),
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

    const logDebug = (data) => {
        console.log('payment info: ', data);
    }

    function showHide(elem, toShow) {
        if (typeof(elem) === "string") {
            elem = document.getElementById(elem);
        }
        if (elem) {
            toShow ? elem.classList.remove("hidden") : elem.classList.add("hidden");
        }
    }

    function getDiscount() {
        const totals = quote.totals(),
            base_discount = (totals || quote)['base_discount_amount'];

        return parseFloat(base_discount).toFixed(2);
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

    function getShippingPrice() {
        const totals = quote.totals(),
            tax = (totals || quote)['shipping_amount'];
        return parseFloat(tax).toFixed(2);
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
