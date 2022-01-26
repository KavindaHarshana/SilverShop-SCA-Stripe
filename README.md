# SilverShop-SCA-Stripe

The UK will enforce Secure Customer Authentication (SCA) from March 2022.

The Omnipay/Stripe module for Silvershop currently uses the Stripe charges API which does not meet SCA requirements.

This module uses the Stripe payments intent API which meets SCA requirements.

## Requirements
***
* [SilverStripe CMS](https://github.com/silverstripe/silverstripe-cms) 4.*
* [SilverShop Core](https://github.com/silvershop/silvershop-core/) 3.*
* [Omnipay Stripe](https://github.com/thephpleague/omnipay-stripe) 3.*
  

uses [Stripe.js v3](https://stripe.com/docs/stripe-js)

## Installation

composer.json :

```
"kavinda/silvershop-sca-stripe": "dev-main",

 "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/KavindaHarshana/SilverShop-SCA-Stripe.git"
        }
    ],
```