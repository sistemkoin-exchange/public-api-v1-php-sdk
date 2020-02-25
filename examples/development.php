<?php
/**
*
* @author Bilal ATLI
* Date: 14.11.2019
* Time: 10:40
* E-mail : <bilal@sistemkoin.com>, <ytbilalatli@gmail.com>
* Phone : +90 0-542-433-09-19
* Original Filename : development.php
*/

use STK\SistemkoinSDK;

require 'vendor/autoload.php';

$apiKey = 'TEST_API_KEY';
$apiSecret = 'API_SECRET_KEY';

$sdk = new SistemkoinSDK($apiKey, $apiSecret);

dd($sdk->getUserData());