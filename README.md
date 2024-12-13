# M2E Pro

M2E Pro is an award-winning multi-channel solution for eCommerce businesses that integrates Adobe Commerce (Magento) stores with the world’s largest marketplaces, such as eBay, Amazon, and Walmart.

With 17+ years of experience, M2E Pro offers robust tools tailored to business needs. The extension provides inventory and order synchronization across all eBay, Amazon and Walmart marketplaces. Today, M2E Pro is a part of the [M2E Cloud](https://m2ecloud.com/) ecosystem.

## Installation

1. Install Composer Installer.

2. Provide the Composer Installer as a dependence on the composer.json file of your project. Use the command:

```shell
composer require m2epro/magento2-extension
```

3. To complete the installation, run the commands:

```shell
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

## Setup

1. Once M2E Pro is installed, you’ll see an additional tab in your Adobe Commerce (Magento) admin panel — eBay/Amazon/Walmart.

2. Click on the tab and choose the Channel(s) you want to connect.

3. Follow the short step-by-step wizard to provide the general settings.

Check out our [user documentation](https://docs-m2.m2epro.com/) for more information about the extension.
