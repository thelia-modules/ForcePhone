# Force Phone

This module sets the mobile phone number, the home phone nuumner or both as required in the customer and address forms.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is ForcePhone.
* Activate it in your Thelia administration panel

### Composer

Add it in your main Thelia composer.json file

```
composer require thelia/force-phone-module:~1.0
```

## Usage

Activate the module and the home phone number becomes required in the customer and address forms.
Go to module configuration to select which phone numbers should be required.

Affected pages :
- register
- create address
- update address

## Other

Be sure to set proper translations for phone inputs' labels.

You can find translation for the mandatory input in your administration panel:
` Configuration --> Translation --> Modules --> Set the phone input mandatory for the customer --> Core files `

Translation for the second phone input is in:
` Configuration --> Translation --> Thelia core `
