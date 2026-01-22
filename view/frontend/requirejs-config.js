/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE for license details.
 */

var config = {
    map: {
        '*': {
            giftCard: 'CardknoxDevelopment_Cardknox/js/view/cart/gift-card'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/step-navigator': {
                'CardknoxDevelopment_Cardknox/js/model/step-navigator-mixin': true
            }
        }
    }
};
