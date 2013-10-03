<?php
/*
EasyBitcoin-PHP

A simple class for making calls to Bitcoin's API using PHP.
https://github.com/aceat64/EasyBitcoin-PHP

====================

The MIT License (MIT)

Copyright (c) 2013 Andrew LeCody

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

// Initialize Bitcoin connection/object
$bitcoin = new Bitcoin('username','password');

// Optionally, you can specify a host, port and protocol (HTTP and HTTPS). Default is localhost:8332
$bitcoin = new Bitcoin('username','password','host','port','http');

// Set $bitcoin->full to true and calls will return the result, error message (if any) and request id.
$bitcoin->full = false;

// Make calls to bitcoind as methods for your object. Examples:
$bitcoin->getinfo();
$bitcoin->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
$bitcoin->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

// When a call fails, it will return false and put the error message in $bitcoin->error
echo $bitcoin->error;

*/

class Bitcoin {
	public $username;
	public $password;
	public $host;
	public $port;
	public $url;
	public $full = false;
	public $error = null;
	private $id = 0;

	function __construct($username, $password, $host = 'localhost', $port = 8332, $proto = 'http', $url = null) {
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
		$this->url = $url;
	}

	function __call($method, $params) {
		$this->error = null;

		$url = "{$this->proto}://{$this->username}:{$this->password}@{$this->host}:{$this->port}/{$this->url}";

		// If no parameters are passed, this will be an empty array
		$params = array_values($params);

		// The ID should be unique for each call
		$this->id++;

		// Build the request, it's ok that params might have any empty array
		$request = json_encode(array(
			'method' => $method,
			'params' => $params,
			'id' => $this->id,
		));

		// Build the cURL session
		$curl = curl_init($url);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_HTTPHEADER => array('Content-type: application/json'),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $request,
		);
		curl_setopt_array($curl, $options);

		// Execute the request and decode to an array
		$response = json_decode(curl_exec($curl),true);

		// If the status is not 200, something is wrong
		$status = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		if ($status != 200) {
			$this->error = $status;
			return false;
		}

		curl_close($curl);

		if ($this->full) {
			return $response;
		} else {
			if ($response['error'] === null) {
				return $response['result'];
			} else {
				// An error occurred
				$this->error = $response['error'];
				return false;
			}
		}
	}
}
