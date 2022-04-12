/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'cardknox_google_pay',
                component: 'CardknoxDevelopment_Cardknox/js/view/payment/method-renderer/cardknox-google-pay-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
