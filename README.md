magento2-Cardknox
======================

Magento2 extension to allow payments using the [Cardknox](https://www.cardknox.com) payment gateway.


Install
=======

1. Go to Magento2 root folder

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable CardknoxDevelopment_Cardknox --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure Cardknox in Magento Admin under Stores/Configuration/Payment Methods/Cardknox

Copyright © 2018 Cardknox Development Inc. All rights reserved.
See LICENSE for license details.
