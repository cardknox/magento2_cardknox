Sola Payments Gateway for Adobe Commerce (Magento 2)
=====================================================

- Adobe Commerce (Magento 2) extension to allow payments using the Sola Payments Gateway (formerly Cardknox).

- Supports Magento 2.1, 2.2, 2.3, 2.4

- For Magento 2.1.0-2.1.3 use [releases/magento-2.1.0-3](https://github.com/Cardknox/magento2_cardknox/tree/releases/magento-2.1.0-3)

## Installation

If you need help installing this module, please contact [Sola support](gatewaysupport@solapayments.com)

There are two methods to install the Sola Payments Gateway module:

### Method 1: Install using Composer

1. Place an order for the module at [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com/cardknox-cardknox.html/)
2. Open your terminal
3. Go to your Magento root directory
4. Run the following command:

    ```bash
    composer require cardknox/cardknox
    ```

### Method 2: Manual Installation (Without Composer)

If you cannot use Composer, you can manually install the plugin:

1. Download the latest version of the module from the [GitHub repository](https://github.com/Cardknox/magento2_cardknox)
2. Extract the zip file to `<magento_root>/app/code/CardknoxDevelopment/Cardknox`

## Enable the Module

After installation, run the following commands in your terminal:

```bash
php bin/magento module:enable CardknoxDevelopment_Cardknox --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

Optionally, flush the cache:

```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

## Configuration

Enable and configure the Sola Payments Gateway in the Magento Admin under:
**Stores** > **Configuration** > **Payment Methods** > **Sola Payments**

## Uninstall the Module

If you want to uninstall the module, run the following commands:

```bash
php bin/magento module:disable CardknoxDevelopment_Cardknox
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

Optionally, flush the cache:

```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

## Support

For support, please contact [Sola support](gatewaysupport@solapayments.com)

---

Copyright © 2024 Cardknox Development Inc. All rights reserved.
See LICENSE for license details.
