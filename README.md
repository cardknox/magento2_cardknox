magento2-Cardknox
======================

Magento2 extension to allow payments using the [Cardknox](https://www.cardknox.com) payment gateway.

Supports Magento 2.1, 2.2, 2.3, 2.4

For Magento 2.1.0-2.1.3 use [releases/magento-2.1.0-3](https://github.com/Cardknox/magento2_cardknox/tree/releases/magento-2.1.0-3)


Install
=======

1. Go to Magento2 root folder

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable CardknoxDevelopment_Cardknox --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure Cardknox in Magento Admin under Stores/Configuration/Payment Methods/Cardknox

Copyright © 2024 Cardknox Development Inc. All rights reserved.
See LICENSE for license details.
