/**
 * Copyright © 2024 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'uiComponent',
    'domReady!'
], function ($, Class) {
    'use strict';
    return Class.extend({
        defaults: {
            $selector: null,
            selector: 'edit_form',
            $container: null,
            publicHash: '',
            container: ''
        },

        initialize: function () {
            this._super();
            this.initVault();
            return this;
        },

        initVault: function () {
            var self = this;

            // Wait for DOM to be ready
            $(function () {
                self.$container = $('#' + self.container);
                self.$selector = $('#' + self.selector);

                if (self.$container.length === 0) {
                    console.warn('Cardknox Vault: Container not found:', self.container);
                    return;
                }

                self.initEventHandlers();
                self.setInitialPaymentDetails();
            });
        },

        getCode: function () {
            return this.code;
        },

        initEventHandlers: function () {
            var self = this;
            var radioButton = this.$container.find('[name="payment[token_switcher]"]');

            radioButton.on('click', function () {
                self.setPaymentDetails();
            });
        },

        /**
         * Set payment details on initial load if this vault token is selected
         */
        setInitialPaymentDetails: function () {
            var radioButton = this.$container.find('[name="payment[token_switcher]"]');

            // If this radio button is checked on load, set the public_hash
            if (radioButton.is(':checked')) {
                this.setPaymentDetails();
            }
        },

        /**
         * Set public hash value to the hidden input field
         */
        setPaymentDetails: function () {
            var publicHashInput = $('[name="payment[public_hash]"]');

            if (publicHashInput.length && this.publicHash) {
                publicHashInput.val(this.publicHash);
            }
        }
    });
});
