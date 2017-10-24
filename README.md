API-client for Express Pay
==========================

This is a small bundle for Symfony, which provides API-client for 
[**Express Pay**] [1]. This system supports pay via ERIP, VISA,
MasterCard, Maestro.

Installing
----------

composer.json:

```json
{
    "require": {
        "itmedia/express-pay-bundle": "^1.0"
    }
}
```
app/AppKernel.php:

```php
public function registerBundles()
{
    $bundles = [
		// ...
		new Itmedia\ExpressPayBundle\ItmediaExpressPayBundle(),
	];
}
```

Configuration
-------------

Firstly you must set the access token. Secondly if you are using signature, 
set 'api_signature' to true and define proper 'api_secret'. Similarly, do
the same with notification options 'notification_signature' and 'notification_secret'.
If you are using payment by card, you must set 'return_url' and 'fail_url'.
For developing and testing you may use this url for 'base_url' option: 'https://sandbox-api.express-pay.by/v1/'.
Other options while still not important, since there is only one version of the API.

```yaml
itmedia_express_pay:
    token: 2c57a2e73f26406cb3ac22f465ee3cb0
    api_signature: false
    api_secret:
    notification_signature: false
    notification_secret: 
    base_url: 'https://api.express-pay.by/v1/'
    version: 1
    return_url: 
    fail_url:
```

Usage
-----

For example, to get a list of invoices, in the controller do:
```php
    $invoicesList = $this->get('itmedia_express_pay.api_client')->getListInvoices();
```

Some useful links:

  * [**API documentation**][2] - Official API documentation for version 1

Bundle released under the MIT license.

Enjoy!

[1]:  https://express-pay.by
[2]:  https://express-pay.by/docs/api/v1