# Mellat bank online payment - درگاه پرداخت بانک ملت به زبان PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dpsoft/mellat.svg?style=flat-square)](https://packagist.org/packages/dpsoft/mellat)
[![Total Downloads](https://img.shields.io/packagist/dt/dpsoft/mellat.svg?style=flat-square)](https://packagist.org/packages/dpsoft/mellat)

Mellat bank transaction library based on php soap extension.

## Installation

You can install the package via composer:

```bash
composer require dpsoft/mellat
```

## Usage
1- Request transaction and redirect to bank:
```php
try{
    $mellat = new \DpSoft\Mellat($terminalId, $userName, $userPassword);
    $response = $mellat->request($amount);
    
    //save $response info like token($response['token']) and orderId($response['order_id']) then redirect to bank
    echo "redirecting to bank...";
    $response->redirectToBank();
}catch(\Throwable $e){
    echo "error: ".$e->getMessage();
}
```
2- Handle bank response:
```php
try{
    $mellat = new \DpSoft\Mellat($terminalId, $userName, $userPassword);
    $response = $mellat->verify();
    
    //successful payment. save $response info like reference id($response['reference_id'])
    echo "successful payment.Thanks...";
}catch(\Throwable $e){
    echo "error: ".$e->getMessage();
}
```
### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email daneshpajouhan.ac.ir@gmail.com instead of using the issue tracker.

## Credits

- [Dpsoft.ir](https://github.com/dpsoft)
- [All Contributors](../../contributors)

## License

The GNU GPLv3. Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
