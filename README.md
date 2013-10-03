EasyBitcoin-PHP
===============

A simple class for making calls to Bitcoin's API using PHP.

Getting Started
---------------
1. Include easybitcoin.php into your PHP script:
	`require_once('easybitcoin.php');`
2. Initialize Bitcoin connection/object:
	`$bitcoin = new Bitcoin('username','password');`
3. Optionally, you can specify a host, port and protocol (HTTP and HTTPS). Default is localhost:8332
	`$bitcoin = new Bitcoin('username','password','host','port','http');`
4. Make calls to bitcoind as methods for your object. Examples:
	`$bitcoin->getinfo();`
	`$bitcoin->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);`
	`$bitcoin->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');`
5. When a call fails, it will return false and put the error message in `$bitcoin->error`

Additional Info
---------------
* Set $bitcoin->full to true and calls will return the result, error message (if any) and request id.
	`$bitcoin->full = false;`
