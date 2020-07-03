# Magento 2 Automater

 The integration of Automater with the Magento platform allows you to automatically handle sales and send codes or files to Clients. The cost of handling each transaction is 1% of its value, but not less than 5 cents. Credits for store transactions are not charged - only commission is charged.

## 1. How to install


## ✓ Install via composer (recommend)
Run the following command in Magento 2 root folder:

```
composer require automater-pl/magento-2
```

## ✓ Install ready-to-paste package

- Go to the main magento directory and install the Automater SDK from: https://github.com/automater-pl/rest-php-sdk
- Download the latest version from release page or master archive.
- In the main magento directory you will find the `app` directory and then the `code` in it. In the `code` directory create an `Automater` directory and then another with the same name in it. The directory path should look like this:

`app/code/Automater/Automater`

- Copy the contents of the previously downloaded package to this directory.

## 2. How to activate

Run the following command in Magento 2 root folder:

```
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```
