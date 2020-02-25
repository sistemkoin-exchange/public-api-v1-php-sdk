## Installation

- Clone this repository ```git clone https://github.com/sistemkoin-exchange/public-api-v1-php-sdk.git```
- Install composer packages ```composer install```
- Create composer autoloader ```composer dump-autoload -o```

OR

- Install php-sdk composer package ```composer require sistemkoin/public-api-v1-php-sdk```

## Create Requests

- Import composer autoloader ```require 'vendor/autoload.php';```
- Setup your environment
```
use STK\SistemkoinSDK;

require 'vendor/autoload.php';

$apiKey = 'YOUR_API_KEY';
$apiSecret = 'YOUR_API_SECRET';
```
- Create SDK Class 
```
$sdk = new SistemkoinSDK($apiKey, $apiSecret);
```
- Make request
```
dd($sdk->getUserData());
```

###Proxy - Watch Requests
If u want to use proxy, add proxy parameters in guzzlehttp/client options.

- *src/SistemkoinSDK.php*
```
$clientOptions = array_merge($clientOptions, [
    'proxy'  => '{proxy_server}:{proxy_port}',
]);
```
