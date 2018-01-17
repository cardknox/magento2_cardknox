/**
 * Copyright © 2018 Cardknox Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
var config = {
    urlArgs: "v=" + (new Date()).getTime(),
    paths: {
        ifields: 'https://cdn.cardknox.com/ifields/ifields.min'

    },

    shim: {
        ifields: {
            exports: 'ifields'
        }
    }
};


