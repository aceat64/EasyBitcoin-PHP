MultiChain-API-PHP
==================

A simple class for making calls to Multichain's API using PHP.

Forked from EasyBitcoin-PHP, Copyright (c) 2013 Andrew LeCody
https://github.com/aceat64/EasyMultiChain-PHP


Getting Started
---------------
1. Include multichain-api.php into your PHP script:

    ```php
    require_once('multichain-api.php');
    ```
2. Initialize MultiChain connection/object:

    ```php
    $MultiChain = new MultiChain('username','password','localhost','2286');
    ```
    
    Check, if initialization worked (i.e. curl is installed)

    ```php
    if (!$MultiChain->initOK) {
	    echo($MultiChain->error);
	    exit();
    }
    ```

    If you wish to make an SSL connection you can set an optional CA certificate or leave blank
    ```php
    $MultiChain->setSSL('/full/path/to/mycertificate.cert');
    ````

3. Make calls to multichaind as methods for your object. Examples:

    ```php
    $MultiChain->getinfo();
    
    $MultiChain->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
    
    $MultiChain->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');
    ```
    
    If you called 'getinfo()' at least once, the major version of multichaind is set as integer in $this->versionMajor
    
    ```php    
    $res = $MultiChain->getinfo();
    if ($MultiChain->versionMajor == 2) {
      // code, which is for new versions only ...
    } 
    ```

Additional Info
---------------
* When a call fails for any reason, it will return false and put the error message in `$MultiChain->error`

* The HTTP status code can be found in $MultiChain->status and will either be a valid HTTP status code or will be 0 if cURL was unable to connect.

* The full response (not usually needed) is stored in `$MultiChain->response` while the raw JSON is stored in `$MultiChain->raw_response`
