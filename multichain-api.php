<?php
/*
MultiChain API
Copyright (c) 2018 baumann.at
V 1.0 (6.10.2018)
V 1.1 (21.11.2018): new property "timeout" for socket connection

Forked from EasyBitcoin-PHP, Copyright (c) 2013 Andrew LeCody
https://github.com/aceat64/EasyMultiChain-PHP

A simple class for making calls to Multichain's API using PHP.

====================

The MIT License (MIT)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

====================

// Initialize MultiChain connection/object
$MultiChain = new MultiChain('username','password','host','port');
// Defaults are:
//	host = localhost
//	port = 2286
//	proto = http

// Check, if initialization worked (i.e. curl is installed)
if (!$MultiChain->initOK) {
	echo($MultiChain->error);
	exit();
}


// If you wish to make an SSL connection you can set an optional CA certificate or leave blank
// This will set the protocol to HTTPS and some CURL flags
$MultiChain->setSSL('/full/path/to/mycertificate.cert');

// Make calls to MultiChaind as methods for your object. Responses are returned as an array.
// Examples:
$MultiChain->getinfo();
$MultiChain->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
$MultiChain->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

// if you called getinfo() at least once, the major version of multichaind is set as integer in 
// $this->versionMajor
$res = $MultiChain->getinfo();
if ($MultiChain->versionMajor == 2) {
// code, which is for new versions only ...
}


// The full response (not usually needed) is stored in $this->response
// while the raw JSON is stored in $this->raw_response

// When a call fails for any reason, it will return FALSE and put the error message in $this->error
// Example:
echo $MultiChain->error;

// The HTTP status code can be found in $this->status and will either be a valid HTTP status code
// or will be 0 if cURL was unable to connect.
// Example:
echo $MultiChain->status;

*/

class MultiChain
{
    // Configuration options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;
    public $initOK;
    public $versionMajor;

    private $id = 0;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     * @param int $timeout
     */
    public function __construct($username, $password, $host = 'localhost', $port = 2286, $url = null, $timeout = 10)
    {
        $this->username      = $username;
        $this->password      = $password;
        $this->host          = $host;
        $this->port          = $port;
        $this->url           = $url;
        $this->timeout       = $timeout;
        $this->versionMajor  = null;

        // Set some defaults
        $this->proto         = 'http';
        $this->CACertificate = null;
        
        if (!function_exists('curl_init')) {
        	$this->error = 'curl not installed';
        	$this->initOK = false;
        } else {
        	$this->initOK = true;
        }	
    }

    /**
     * @param string|null $certificate
     */
    public function setSSL($certificate = null)
    {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    public function __call($method, $params)
    {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;

        // Build the request, it's ok that params might have any empty array
        $request = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id'     => $this->id
        ));

        // Build the cURL session
        $curl    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2, 
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request
        );

        // This prevents users from getting the following warning when open_basedir is set:
        // Warning: curl_setopt() [function.curl-setopt]:
        //   CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        if ($this->proto == 'https') {
            // If the CA Certificate was specified we change CURL to look for it
            if (!empty($this->CACertificate)) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            } else {
                // If not we need to assume the SSL cannot be verified
                // so we set this flag to FALSE to allow the connection
                $options[CURLOPT_SSL_VERIFYPEER] = false;
            }
        }

        curl_setopt_array($curl, $options);

        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, true);

				if ($method == 'getinfo') {
				  if (isset($this->response['result']['version'][0])) {
				  	$this->versionMajor = (integer) $this->response['result']['version'][0];
				  }
				}

        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);

        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if ($this->response['error']) {
            // If MultiChaind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        } elseif ($this->status != 200) {
            // If MultiChaind didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
// extended error codes corresponding to multichain/src/rpc/rpcprotocol.h                    
                case 500:
                    $this->error = 'HTTP_INTERNAL_SERVER_ERROR';
                    break;
                case 503:
                    $this->error = 'HTTP_SERVICE_UNAVAILABLE';
                    break;
            }
        }

        if ($this->error) {
            return false;
        }

        return $this->response['result'];
    }
}
