/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'ifields',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/model/messageList',
        'Magento_Vault/js/view/payment/vault-enabler',
        'ko',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url'
    ],
    function (
        Component,
        $,
        v,
        i,
        fullScreenLoader,
        placeOrderAction,
        messageList,
        VaultEnabler,
        ko,
        redirectOnSuccessAction,
        additionalValidators,
        urlBuilder
    ) {
        'use strict';

        let is3DSCallInProgress = false;
        let is3DSVerificationInProgress = false;

        function handle3DSResults(
            actionCode,
            xCavv,
            xEciFlag,
            xRefNum,
            xAuthenticateStatus,
            xSignatureVerification
        ) {
            if (is3DSCallInProgress) {
                return;
            }

            // Validate that we have required data before proceeding
            if (!xRefNum || !actionCode) {
                return;
            }

            // Only proceed if the user has completed authentication (actionCode should be SUCCESS, FAILURE, ERROR, or NOACTION)
            if (actionCode !== 'SUCCESS' && actionCode !== 'FAILURE' && actionCode !== 'ERROR' && actionCode !== 'NOACTION') {
                return;
            }

            // Set the flag to true to indicate the call is in progress
            is3DSCallInProgress = true;

            const postData = {
                xRefNum: xRefNum,
                xCavv: xCavv,
                xEci: xEciFlag,
                x3dsAuthenticationStatus: xAuthenticateStatus,
                x3dsSignatureVerificationStatus: xSignatureVerification,
                x3dsActionCode: actionCode,
                x3dsError: ck3DS.error,
            };

            // Show loader initially
            fullScreenLoader.startLoader();

            // Monitor the 3DS popup
            const popupElement = document.getElementById("Cardinal-ElementContainer");
            if (popupElement) {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.attributeName === "style") {
                            const isHidden = window.getComputedStyle(popupElement).display === "none";
                            if (isHidden) {
                                fullScreenLoader.startLoader();
                            }
                        }
                    });
                });

                observer.observe(popupElement, { attributes: true });
            }

            $.ajax({
                type: "post",
                dataType: "json",
                url: urlBuilder.build('cardknox/index/VerifyThreeDS'),
                data: postData,
                success: function (resp) {
                    if (!resp.success) {
                        fullScreenLoader.stopLoader();
                        if (resp.redirect) {
                            window.location.href = resp.redirect;
                        }
                    } else {
                        if (resp.redirect) {
                            window.location.href = resp.redirect;
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    fullScreenLoader.stopLoader();
                    console.error("3DS verification error:", textStatus, errorThrown);

                    // Display error message to user
                    var errorMsg = "An unexpected error occurred during 3DS verification. Please try again.";
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMsg = jqXHR.responseJSON.message;
                    }
                    messageList.addErrorMessage({
                        message: errorMsg
                    });
                },
                complete: function () {
                    // Reset the flags and hide the loader once the AJAX call is complete
                    is3DSCallInProgress = false;
                    is3DSVerificationInProgress = false;
                    fullScreenLoader.stopLoader();

                    // Re-enable hash change events after 3DS completes
                    $(window).off('hashchange.prevent3DS');
                    $(window).off('popstate.prevent3DS');

                    // Clear the hash monitor interval
                    if (window.threeDSHashMonitor) {
                        clearInterval(window.threeDSHashMonitor);
                        window.threeDSHashMonitor = null;
                    }

                    // Restore original is_virtual value
                    if (window.checkoutConfig && window.checkoutConfig.quoteData && typeof window.originalIsVirtual !== 'undefined') {
                        window.checkoutConfig.quoteData.is_virtual = window.originalIsVirtual;
                        window.originalIsVirtual = undefined;
                    }
                }
            });
        }

        function urlEncodedToJson(data) {
            return JSON.parse(
            '{"' +
                decodeURI(data)
                .replace(/"/g, '\\"')
                .replace(/&/g, '","')
                .replace(/=/g, '":"') +
                '"}'
            );
        }

        return Component.extend({
            cardNumberIsValid: ko.observable(false),
            cvvIsValid: ko.observable(false),
            xCardNumberLength:  ko.observable(false),
            xCvvLength:  ko.observable(false),
            /**
             * @returns {exports.initialize}
             */
            initialize: function () {

                this._super();
                this.initCardknox();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                return this;
            },
            defaults: {
                template: 'CardknoxDevelopment_Cardknox/payment/cardknox-form'
            },
            isAllowDuplicateTransaction: ko.observable(false),
            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            getCode: function () {
                return 'cardknox';
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
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'xCVV': document.getElementsByName("xCVV")[0].value,
                        'xCardNum': document.getElementsByName("xCardNum")[0].value,
                        'isSplitCapture': window.checkoutConfig.payment.cardknox.isCCSplitCaptureEnabled,
                        'xPaymentAction': window.checkoutConfig.payment.cardknox.xPaymentAction,
                        'isAllowDuplicateTransaction': this.getAllowDuplicateTransactionCC()
                    }
                };
                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
                this.vaultEnabler.visitAdditionalData(data);
                return data;
            },
            isActive: function () {
                return true;
            },
            validate: function () {
                var self = this;
                var $form = $('#' + self.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            defaultStyle: {
                border: '2px solid #adadad',
                padding: '3px',
                width: '145px',
                height: '25px'
            },
            validStyle: {
                border: '2px solid green',
                padding: '3px',
                width: '145px',
                height: '25px'
            },
            invalidStyle: {
                border: '2px solid red',
                padding: '3px',
                width: '145px',
                height: '25px'
            },
            validateCardIfPresent: function(data) {
                return data.cardNumberFormattedLength <= 0 || data.cardNumberIsValid ? true : false;
            },
            validateCVVIfPresent: function(data) {
               return data.issuer === 'unknown' || data.cvvLength <= 0 || data.cvvIsValid ? true : false;
            },
            validateCVVLengthIfPresent: function(data) {
                if (data.issuer === 'unknown' || data.cvvLength <= 0) {
                    return true;
                } else if (data.issuer === 'amex'){
                    return data.cvvLength === 4 ? true : false;
                } else {
                    return data.cvvLength === 3 ? true : false;
                }
            },
            isEnabledReCaptcha: function () {
                return window.checkoutConfig.payment.cardknox.isEnabledReCaptcha == 1;
            },
            getSiteKeyV2: function () {
                return window.checkoutConfig.payment.cardknox.googleReCaptchaSiteKey;
            },
            onloadCallbackV2: function () {
                var cardknox_recaptcha_widget;
                setTimeout(function(){
                    if ($('#cardknox_recaptcha').length) {
                        // If cardknox recaptcha enabled
                        if (window.checkoutConfig.payment.cardknox.isEnabledReCaptcha == '1') {
                            cardknox_recaptcha_widget = grecaptcha.render('cardknox_recaptcha', {
                                'sitekey': window.checkoutConfig.payment.cardknox.googleReCaptchaSiteKey,
                                'callback': function (response) {
                                    $('#cardknox_recaptcha .g-recaptcha-response').val(response);
                                },
                                'expired-callback': function () {
                                    $('#cardknox_recaptcha .g-recaptcha-response').val('');
                                }
                            });
                        }
                    }
                }, 2000);
                return cardknox_recaptcha_widget;
            },
            initCardknox: function () {
                var self = this;
                enableLogging();
                enableBlockNonNumericInput()
                /*
                 * [Optional]
                 * Use enableAutoFormatting(separator) to automatically format the card number field making it easier to read
                 * The function contains an optional parameter to set the separator used between the card number chunks (Default is a single space)
                 */
                enableAutoFormatting();

                /*
                 * [Required]
                 * Set your account data using setAccount(ifieldKey, yourSoftwareName, yourSoftwareVersion).
                 */
                setAccount(window.checkoutConfig.payment.cardknox.tokenKey, "Magento2", "1.2.81");

                /*
                 * [Optional]
                 * You can customize the iFields by passing in the appropriate css as JSON using setIfieldStyle(ifieldName, style)
                 */
                setIfieldStyle('card-number', self.defaultStyle);
                setIfieldStyle('cvv', self.defaultStyle);
                /**
                 * For google recaptcha
                */

                // If cardknox recaptcha enabled
                var isEnabledGoogleReCaptcha = this.isEnabledReCaptcha();

                if (isEnabledGoogleReCaptcha == true) {
                    var selectRecaptchaSource = window.checkoutConfig.payment.cardknox.selectRecaptchaSource;
                    var recaptchaApiJs = null;
                    if (selectRecaptchaSource == "google.com") {
                        recaptchaApiJs = 'https://www.google.com/recaptcha/api.js';
                        require([recaptchaApiJs + '?onload=onloadCallbackV2&render=explicit']);
                    }
                    this.onloadCallbackV2();
                }
                /*
                 * [Optional]
                 * Use addIfieldCallback(event, callback) to set callbacks for when the event is triggered inside the ifield
                 * The callback function receives a single parameter with data about the state of the ifields
                 * The data returned can be seen by using alert(JSON.stringify(data));
                 * The available events are ['input', 'click', 'focus', 'dblclick', 'change', 'blur', 'keypress', 'issuerupdated']
                 * ('issuerupdated' is fired when the CVV ifield is updated with card issuer)
                 *
                 * The below example shows a use case for this, where you want to visually alert the user regarding the validity of the card number, cvv and ach ifields
                 * Cvv styling should be updated on 'issuerupdated' event also as validity will change based on issuer
                 */
                addIfieldCallback('input', function(data) {
                    if (data.ifieldValueChanged) {
                        let dataIssuer = data.issuer,
                            dataLastIfieldChanged = data.lastIfieldChanged,
                            dataCvvLength = data.cvvLength,
                            dataCardNumberFormattedLength = data.cardNumberFormattedLength;
                        self.cardNumberIsValid(self.validateCardIfPresent(data));
                        self.cvvIsValid(self.validateCVVIfPresent(data));
                        self.xCardNumberLength(data.cardNumberLength);
                        self.xCvvLength(dataCvvLength);
                        setIfieldStyle('card-number', dataCardNumberFormattedLength <= 0 ? self.defaultStyle : data.cardNumberIsValid ? self.validStyle : self.invalidStyle);

                        if (dataLastIfieldChanged === 'cvv'){
                            setIfieldStyle('cvv', dataIssuer === 'unknown' || dataCvvLength <= 0 ? self.defaultStyle : data.cvvIsValid ? self.validStyle : self.invalidStyle);
                        } else if (dataLastIfieldChanged === 'card-number') {
                            if (dataIssuer === 'unknown' || dataCvvLength <= 0) {
                                setIfieldStyle('cvv', self.defaultStyle);
                            } else if (dataIssuer === 'amex'){
                                setIfieldStyle('cvv', dataCvvLength === 4 ? self.validStyle : self.invalidStyle);
                            } else {
                                setIfieldStyle('cvv', dataCvvLength === 3 ? self.validStyle : self.invalidStyle);
                            }
                        }
                    }
                });

                addIfieldCallback('issuerupdated', function (data) {
                    setIfieldStyle('cvv', data.issuer === 'unknown' || data.cvvLength <= 0 ? self.defaultStyle : data.cvvIsValid ? self.validStyle : self.invalidStyle);
                    let issuerUpdatedData = data,
                        issuerUpdatedDataCvvLength = issuerUpdatedData.cvvLength;
                    self.cardNumberIsValid(self.validateCardIfPresent(issuerUpdatedData));
                    self.cvvIsValid(self.validateCVVIfPresent(issuerUpdatedData));
                    self.xCardNumberLength(issuerUpdatedData.cardNumberLength);
                    self.xCvvLength(issuerUpdatedDataCvvLength);
                });

                let checkCardLoaded = setInterval(function() {
                    clearInterval(checkCardLoaded);
                    focusIfield('card-number');
                }, 1000);
                let isEnabledThreeDSEnabled = window.checkoutConfig.payment.cardknox.isEnabledThreeDSEnabled;
                let threeDSEnvironment = window.checkoutConfig.payment.cardknox.ThreeDSEnvironment ?? "staging";
                if (isEnabledThreeDSEnabled == true) {
                    // enable3DS("staging", handle3DSResults);
                    enable3DS(threeDSEnvironment, handle3DSResults);
                } else {
                    enable3DS(threeDSEnvironment,null);
                }
            },
            /**
             * Prepare data to place order
             * @param {Object} data
             */
            PlaceOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this;
                var isEnabledGoogleReCaptcha = this.isEnabledReCaptcha();
                if (isEnabledGoogleReCaptcha == true){
                    var captchResponse = $('#cardknox_recaptcha .g-recaptcha-response').val();
                    if(captchResponse.length == 0 ){
                        $(".recaptcha-error").show();
                        return;
                    } else {
                        $(".recaptcha-error").hide();
                    }
                }
                if (self.validate()) {
                    self.isPlaceOrderActionAllowed(false);
                    let errorMessage = '';
                    let isCardNumberEmpty = !self.xCardNumberLength();
                    let isCvvEmpty = !self.xCvvLength();
                    if (isCardNumberEmpty && isCvvEmpty) {
                        errorMessage = "Card number and CVV are required";
                    } else if (isCardNumberEmpty) {
                        errorMessage = "Card number is required";
                    } else if (isCvvEmpty) {
                        errorMessage = "CVV is required";
                    }

                    if (errorMessage.length > 0 ) {
                        self.showError(errorMessage);
                        self.isPlaceOrderActionAllowed(true);
                        return false;
                    }
                    if (!self.cardNumberIsValid() || !self.cvvIsValid()) {
                        let cardNumberErrorMessage = !this.cardNumberIsValid() ? "Invalid card" : "";
                        let cvvErrorMessage = !this.cvvIsValid() ? "Invalid CVV" : "";
                        let isValidErrorMessage = '';
                        if (cardNumberErrorMessage.length > 0 && cvvErrorMessage.length > 0){
                            isValidErrorMessage = cardNumberErrorMessage + ' and ' +cvvErrorMessage;
                        } else if (cardNumberErrorMessage.length > 0 && cvvErrorMessage.length == 0) {
                            isValidErrorMessage = cardNumberErrorMessage;
                        } else if (cardNumberErrorMessage.length == 0 && cvvErrorMessage.length > 0) {
                            isValidErrorMessage = cvvErrorMessage;
                        }
                        if (isValidErrorMessage.length > 0 ) {
                            self.showError(isValidErrorMessage);
                        }
                        self.isPlaceOrderActionAllowed(true);
                        return false;
                    }
                    getTokens(
                        function () {
                            //onSuccess
                            //perform your own validation here...
                            self.isPlaceOrderActionAllowed(true);

                            /**
                             * Validation
                             * Place Order action
                             * Display Allow duplicate transaction checkbox if getting an error = Duplicate Transaction
                             */
                            if (self.validate() &&
                                additionalValidators.validate() &&
                                self.isPlaceOrderActionAllowed() === true
                            ) {
                                self.isPlaceOrderActionAllowed(false);
                                self.getPlaceOrderDeferredObject()
                                    .done(
                                        function () {
                                            self.afterPlaceOrder();

                                            if (self.redirectAfterPlaceOrder) {
                                                redirectOnSuccessAction.execute();
                                            }
                                        }
                                    ).always(
                                        function () {
                                            // Don't re-enable button if 3DS verification is in progress
                                            if (!is3DSVerificationInProgress) {
                                                self.isPlaceOrderActionAllowed(true);
                                            }
                                        }
                                    ).fail(
                                        function (response) {
                                            const message = response.responseJSON.message;
                                            const regex = /xResult=V&xStatus=Verify&xError=&xErrorCode=00000&xRefNum=/;

                                            if (regex.test(message)) {
                                                is3DSVerificationInProgress = true;
                                                // Hide all error messages
                                                $('[data-role="checkout-messages"]').css('cssText', 'display: none !important');
                                                $('.message.error').hide();
                                                $('.messages').hide();
                                                // Clear the message container
                                                self.messageContainer.clear();

                                                // Prevent checkout navigation during 3DS
                                                // Only apply hash protection if using standard Magento checkout (not OneStep/custom)
                                                var isStandardCheckout = window.location.pathname.indexOf('/checkout/') !== -1 &&
                                                                        (window.location.hash.indexOf('#shipping') !== -1 ||
                                                                         window.location.hash.indexOf('#payment') !== -1);

                                                if (isStandardCheckout) {
                                                    // Save current URL state
                                                    var originalHash = window.location.hash || '#payment';

                                                    // Prevent hash changes during 3DS - use both hashchange and history
                                                    var preventHashChange = function(e) {
                                                        if (window.location.hash !== originalHash) {
                                                            if (e && e.preventDefault) {
                                                                e.preventDefault();
                                                            }
                                                            // Force the hash back immediately
                                                            history.replaceState(null, null, originalHash);
                                                        }
                                                    };

                                                    // Listen to both hashchange and popstate
                                                    $(window).on('hashchange.prevent3DS', preventHashChange);
                                                    $(window).on('popstate.prevent3DS', preventHashChange);

                                                    // Also prevent Magento checkout from changing steps
                                                    if (window.checkoutConfig && window.checkoutConfig.quoteData) {
                                                        // Store original value to restore later
                                                        window.originalIsVirtual = window.checkoutConfig.quoteData.is_virtual;
                                                        window.checkoutConfig.quoteData.is_virtual = false;
                                                    }

                                                    // Monitor for hash changes with interval as backup
                                                    var hashMonitor = setInterval(function() {
                                                        if (is3DSVerificationInProgress && window.location.hash !== originalHash) {
                                                            history.replaceState(null, null, originalHash);
                                                        }
                                                    }, 100);

                                                    // Store the interval ID so we can clear it later
                                                    window.threeDSHashMonitor = hashMonitor;
                                                }

                                                verify3DS(urlEncodedToJson(message));
                                                // Don't process any further error handling - wait for 3DS completion
                                                return;
                                            }
                                            self.isPlaceOrderActionAllowed(true);
                                            var error = response.responseJSON.message;
                                            if (error == 'Duplicate Transaction') {
                                                self.isAllowDuplicateTransaction(true);
                                            } else {
                                                self.isAllowDuplicateTransaction(false);
                                            }
                                        }
                                    );

                                return true;
                            }
                        },
                        function () {
                            //onError
                            self.showError(document.getElementById('ifieldsError').textContent);
                            self.isPlaceOrderActionAllowed(true);
                        },
                        //30 second timeout
                        30000
                    );
                    return false
                } else {
                    return false
                }
            },
            /**
             * Show error message
             * @param {String} errorMessage
             */
            showError: function (errorMessage) {
                let statusElement = document.getElementById('transaction-status');
                statusElement.innerHTML = errorMessage;
                statusElement.style.color = "red";
                statusElement.focus();
            },
            /**
             * @returns {Bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },
            /**
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
            },
            /**
             * @return {*}
             */
            getPlaceOrderDeferredObject: function () {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },

            additionalValidator: function () {
                return additionalValidators.validate();
            },
            getAllowDuplicateTransactionCC: function () {
                var isAllowDuplicateTransactionCC = false;
                if ($('#is_allow_duplicate_transaction_cc').length) {
                    if($("#is_allow_duplicate_transaction_cc").prop('checked') == true){
                        isAllowDuplicateTransactionCC = true;
                    }
                }
                return isAllowDuplicateTransactionCC;
            }
        });
    }
);
