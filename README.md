EasyGulden-PHP
===============

A simple class for making calls to Gulden's API using PHP.

Getting Started
---------------
1. Include easygulden.php into your PHP script:

    ```php
    require_once('easygulden.php');
    ```
2. Initialize Gulden connection/object:

    ```php
    $gulden = new Gulden('username','password');
    ```

    Optionally, you can specify a host, port. Default is HTTP on localhost port 8332.

    ```php
    $gulden = new Gulden('username','password','localhost','8332');
    ```

    If you wish to make an SSL connection you can set an optional CA certificate or leave blank
    ```php
    $gulden->setSSL('/full/path/to/mycertificate.cert');
    ````

3. Make calls to guldend as methods for your object. Examples:

    ```php
    $gulden->getinfo();
    
    $gulden->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
    
    $gulden->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');
    ```

Additional Info
---------------
* When a call fails for any reason, it will return false and put the error message in `$gulden->error`

* The HTTP status code can be found in $gulden->status and will either be a valid HTTP status code or will be 0 if cURL was unable to connect.

* The full response (not usually needed) is stored in `$gulden->response` while the raw JSON is stored in `$gulden->raw_response`
