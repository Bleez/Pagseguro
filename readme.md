# Bleez Pagseguro

Modulo de pagseguro para magento 2

## Como instalar

### Via Composer

```sh
composer require bleez/pagseguro
php bin/magento module:enable --clear-static-content Bleez_Pagseguro
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy //ou php bin/magento setup:static-content:deploy pt_BR
```

## Features

* Opção para pagamento no pagseguro
* Checkout Transparente
* Cartão de Credito, Debito e Boleto
