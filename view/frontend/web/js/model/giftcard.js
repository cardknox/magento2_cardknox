/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'domReady!'
], function (ko) {
    'use strict';

    var ckGiftCardCode = ko.observable(null),
        isCkGiftCardApplied = ko.observable(false),
        addButtonVisible = ko.observable(true),
        cancelButtonVisible = ko.observable(false);

    return {
        ckGiftCardCode: ckGiftCardCode,
        isCkGiftCardApplied: isCkGiftCardApplied,
        addButtonVisible: addButtonVisible,
        cancelButtonVisible: cancelButtonVisible,

        /**
         * @return {*}
         */
        getCkGiftCardCode: function () {
            return ckGiftCardCode;
        },

        /**
         * @return {Boolean}
         */
        getIsCkGiftCardApplied: function () {
            return isCkGiftCardApplied;
        },
    };
});
