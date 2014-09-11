<?php

/**
 * Slice Framework (http://sliceframework.com)
 * Copyright (C) 2013 devSDMF Software Development Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

namespace Slice\Http;

use Slice\Http\Exception\RuntimeException;
use Slice\Http\ResponseAbstract;
use Slice\Http\Response;

/**
 * Slice Framework
 *
 * A thin PHP Library for perform HTTP requests using CURL library.
 * This library is based on Zend_Http component of Zend Framework 1.
 * 
 * This is the Client class to perform HTTP requests for Web Services, 
 * available request methods are GET, POST, PUT and DELETE, can also 
 * send request headers, send GET and POST parameters, send data in RAW 
 * format and support for HTTP versions 1.0 and 1.1. 
 * This library needs the CURL extension installed.
 *
 * @package Slice
 * @subpackage Http
 * @namespace \Slice\Http
 * @author Lucas Mendes de Freitas (devsdmf)
 * @copyright Slice Framework (c) devSDMF Software Development Inc.
 * @link https://github.com/zendframework/zf1/blob/master/library/Zend/Http
 *
 */

class Client
{
	/**
	 * HTTP request methods
	 */
	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	
	/**
	 * HTTP protocol versions
	 */
	const HTTP_0 = CURL_HTTP_VERSION_1_0;
	const HTTP_1 = CURL_HTTP_VERSION_1_1;
	
	/**
	 * Request URI
	 * @var string
	 */
	protected $uri;
	
	/**
	 * Request method to use
	 * @var string
	 */
	protected $method = self::GET;
	
	/**
	 * Associative array of request headers
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * Associative array of GET parameters
	 * @var array
	 */
	protected $paramsGet = array();
	
	/**
	 * Associative array of POST parameters
	 * @var unknown
	 */
	protected $paramsPost = array();
	
	/**
	 * Control var for use raw data
	 * @var boolean
	 */
	protected $useRawData = false;
	
	/**
	 * RAW data to send in request
	 * @var string
	 */
	protected $dataRaw;
	
	/**
	 * Files to send in request
	 * @var array
	 */
	protected $files = array();
	
	/**
	 * HTTP protocol version to use in request
	 * @var integer
	 */
	protected $httpVersion = self::HTTP_1;
	
	/**
	 * Associative array of default CURL options to send in all requests
	 * @var array
	 */
	protected $defaultCurlOpts = array();
	
	/**
	 * Associative array of CURL options
	 * @var array
	 */
	protected $curlOpts = array();
	
	/**
	 * Allowed HTTP methods
	 * @var array
	 */
	protected $allowedMethods = array('GET','POST','PUT','DELETE');
	
	/**
	 * Response object of last request performed.
	 * @var \Slice\Http\Response
	 */
	protected $response;
	
	/**
	 * Instance of Client before of last request 
	 * @var \Slice\Http\Client
	 */
	protected $lastRequest;
	
	/**
	 * Control var for reset object state after request
	 * @var boolean
	 */
	protected $noReset;
	
	/**
	 * A custom response handler instance
	 * @var \Slice\Http\ResponseAbstract
	 */
	protected $responseHandler = null;
	
	/**
	 * The Constructor
	 * 
	 * @param string $uri
	 * @param string $method
	 * @return \Slice\Http\Client
	 */
	public function __construct($uri = null, $method = null)
	{
		if (!is_null($uri)) {
			$this->setUri($uri);
		}
		
		if (!is_null($method)) {
			$this->setMethod($method);
		}
		
		$this->defaultCurlOpts = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
		);
		
		return $this;
	}
	
	/**
	 * Set the URI to perform the request
	 * 
	 * @param string $uri
	 * @throws RuntimeException
	 * @return \Slice\Http\Client
	 */
	public function setUri($uri)
	{
		if (filter_var($uri, FILTER_VALIDATE_URL)) {
			$this->uri = $uri;
		} else {
			throw new RuntimeException('Invalid URI setted.');
		}
		
		return $this;
	}
	
	/**
	 * Get the current URI
	 * 
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}
	
	/**
	 * Set the request method to use
	 * 
	 * @param string $method Recommended use of Client class constants
	 * @throws RuntimeException
	 * @return \Slice\Http\Client
	 */
	public function setMethod($method = self::GET)
	{
		if (in_array($method, $this->allowedMethods)) {
			$this->method = $method;
		} else {
			throw new RuntimeException('Invalid method setted.');
		}
		
		return $this;
	}
	
	/**
	 * Get the current method
	 * 
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	/**
	 * Set the headers to send in request
	 * 
	 * @param string|array $name The header name or an associative array with mutiples headers
	 * @param mixed $value
	 * @return \Slice\Http\Client
	 */
	public function setHeaders($name, $value = null)
	{
		//Verifying if is array
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				if (is_string($k)) {
					$this->setHeaders($k, $v);
				} else {
					$this->setHeaders($v, null);
				}
			}
		} else {
			// Verifying if is a string without $value parameter
			if (is_null($value) && (strpos($name, ':') > 0)) {
				list($name, $value) = explode(':', $name, 2);
			}
			
			if (is_null($value) || $value === false) {
				unset($this->headers[$name]);
			} else {
				if (is_string($value)) {
					$value = trim($value);
				}
				$this->headers[$name] = $value;
			}
		}
		
		return $this;
	}
	
	/**
	 * Get the header
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function getHeader($key)
	{
		if (isset($this->headers[$key])) {
			return $this->headers[$key];
		} else {
			return null;
		}
	}
	
	/**
	 * Set a GET parameter for the request
	 * 
	 * @param string|array $name Parameter name or an associative array with multiple parameters
	 * @param mixed $value
	 * @return \Slice\Http\Client
	 */
	public function setParameterGet($name, $value = null)
	{
		// Verifying if is array
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->setParameterGet($k,$v);
			}
		} else {
			$parray = &$this->paramsGet;
				
			if (is_null($value)) {
				if (isset($parray[$name])) unset($parray[$name]);
			} else {
				$parray[$name] = $value;
			}
		}
	
		return $this;
	}
	
	/**
	 * Set a POST parameter for the request
	 * 
	 * @param string|array $name Parameter name or an associative array with multiples parameters
	 * @param string $value
	 * @return \Slice\Http\Client
	 */
	public function setParameterPost($name, $value = null)
	{
		// Verifying if is array
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->setParameterPost($k,$v);
			}
		} else {
			$parray = &$this->paramsPost;
			
			if (is_null($value)) {
				if (isset($parray[$name])) unset($parray[$name]);
			} else {
				$parray[$name] = $value;
			}
		}
		
		return $this;
	}
	
	/**
	 * Set true or false to use RAW data in request
	 * 
	 * @param boolean $bool
	 * @throws RuntimeException
	 * @return \Slice\Http\Client
	 */
	public function rawData($bool = false)
	{
		if (is_bool($bool)) {
			$this->useRawData = $bool;
		} else {
			throw new RuntimeException('Invalid parameter type, expected boolean, got ' . gettype($bool));
		}
		
		return $this;
	}
	
	/**
	 * Set the RAW data for the request
	 * 
	 * @param string $data
	 * @throws RuntimeException
	 * @return \Slice\Http\Client
	 */
	public function setRawData($data)
	{
		if (is_string($data)) {
			$this->rawData(true);
			$this->dataRaw = $data;
		} else {
			throw new RuntimeException('Invalid RAW data setted.');
		}
		
		return $this;
	}
	
	/**
	 * Set files for send in request
	 * 
	 * @param string|array $file
	 * @param string $name
	 * @return \Slice\Http\Client
	 */
	public function setFiles($file, $name = null)
	{
		// Verifying if is array
		if (is_array($file)) {
			foreach ($file as $k => $v) {
				$this->setFiles($v,$k);
			}
		} else {
			$parray = &$this->files;
			
			if (file_exists($file)) {
				if (is_null($file)) {
					if (isset($parray[$name])) unset($parray[$name]);
				} else {
					if (is_null($name) || is_integer($name)) {
						$count = count($parray);
						$name = 'file_contents_' . $count;
					}
						
					$parray[$name] = $file;
				}
			} else {
				throw new RuntimeException('Invalid file, file not exists.');
			}	
		}
		
		return $this;
	}
	
	/**
	 * Get the files
	 * 
	 * @return array
	 */
	public function getFiles()
	{
		return $this->files;
	}
	
	/**
	 * Set HTTP protocol version for request
	 * 
	 * @param integer $version
	 * @throws RuntimeException
	 * @return \Slice\Http\Client
	 */
	public function setHTTPVersion($version = self::HTTP_1)
	{
		if ($version == self::HTTP_0 || $version == self::HTTP_1) {
			$this->httpVersion = $version;
		} else {
			throw new RuntimeException('Invalid HTTP version setted.');
		}
		
		return $this;
	} 
	
	/**
	 * Set custom CURL options for CURL handle
	 * 
	 * @param mixed $opt The option value in CURL or an associative array with multiples options
	 * @param mixed $value The value of parameter
	 * @return \Slice\Http\Client
	 */
	public function setCurlOpts($opt, $value = null)
	{
		// Verifying if is array
		if (is_array($opt)) {
			foreach ($opt as $k => $v) {
				$this->setCurlOpts($k,$v);
			}
		} else {
			$this->curlOpts[$opt] = $value;
		}
		
		return $this;
	}
	
	/**
	 * Get the response of request
	 * 
	 * @return \Slice\Http\Response
	 */
	public function getResponse()
	{
		return $this->response;
	}
	
	/**
	 * Get the instance of Client before last request
	 * 
	 * @return \Slice\Http\Client
	 */
	public function getLastRequest()
	{
		return $this->lastRequest;
	}
	
	/**
	 * Set true or false for reset the Client state after request
	 * 
	 * @param boolean $bool
	 * @throws RuntimeException
	 */
	public function noReset($bool = false)
	{
		if (is_bool($bool)) {
			$this->noReset = $bool;
		} else {
			throw new RuntimeException('Invalid parameter type setted, expected boolean, got ' . gettype($bool));
		}
	}
	
	/**
	 * Set a custom response handler class
	 * 
	 * @param ResponseAbstract $response
	 */
	public function setResponseHandler(ResponseAbstract $response)
	{
		$this->responseHandler = $response;
	}
	
	/**
	 * Send the HTTP request and return an HTTP response object
	 * 
	 * @param string $method
	 * @throws RuntimeException
	 * @return \Slice\Http\Response
	 */
	public function request($method = null)
	{
		# Verifying if was set method.
		if (!is_null($method)) $this->setMethod($method);
		
		# Verifying if URI is setted.
		if (is_null($this->uri)) {
			throw new RuntimeException('URI must be set before calling request method.');
		}
		
		# Setting default CURL options
		$this->setCurlOpts($this->defaultCurlOpts);
		
		# Verifying and preparing request for specified method.
		switch ($this->method) {
			case 'GET' : 
				// Specific routines for GET request.
				break;
			case 'POST' :
				$this->setCurlOpts(CURLOPT_POST, true); 
				
				if ($this->useRawData) {
					$this->setCurlOpts(CURLOPT_POSTFIELDS, $this->dataRaw);
				} else {
					// Verifying if has files to send
					if (count($this->files) > 0) {
						foreach ($this->files as $k => $v) {
							// Verifying if PHP version is over 5.4
							if (PHP_VERSION_ID < 50500) {
								$this->setParameterPost($k, "@$v");
							} else {
								$cFile = new CURLFile($v,mime_content_type($v),$k);
								$this->setParameterPost($k, $cFile);
							}
						} 
					}
					$this->setCurlOpts(CURLOPT_POSTFIELDS, $this->paramsPost);
				}
				break;
			case 'PUT' : 
				$this->setCurlOpts(CURLOPT_CUSTOMREQUEST, "PUT");
				if ($this->useRawData) {
					$this->setCurlOpts(CURLOPT_POSTFIELDS, $this->dataRaw);
				} else {
					$data = http_build_query($this->paramsPost);
					$this->setCurlOpts(CURLOPT_POSTFIELDS, $data);
				}
				break;
			case 'DELETE' : 
				$this->setCurlOpts(CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
		}
		
		# Rendering GET parameters into a query string.
		$queryString = (count($this->paramsGet) > 0) ? '?' . http_build_query($this->paramsGet) : '';
		
		# Preparing and setting URI in CURL options
		$uri = $this->uri . $queryString;
		$this->setCurlOpts(CURLOPT_URL, $uri);
		
		# Parsing headers
		$headers = array();
		foreach ($this->headers as $key => $value) {
			$headers[] = $key . ': ' . $value;
		}
		$this->setCurlOpts(CURLOPT_HTTPHEADER, $headers);
		
		# Setting remaining options in CURL
		$this->setCurlOpts(array(
			CURLOPT_HTTP_VERSION=>$this->httpVersion,
		));
		
		# Initializing Request
		$ch = curl_init();
		curl_setopt_array($ch, $this->curlOpts);
		
		# Performing Request
		$response = curl_exec($ch);
		
		# Verifying if request was successfully executed.
		if (!$response) {
			throw new RuntimeException('cURL Request Error: ' . curl_errno($ch));
		}
		
		# Closing handle
		curl_close($ch);
		
		# Verifying if a custom response handler was set
		if (!is_null($this->responseHandler)) {
			$this->response = $this->responseHandler->fromString($response);
		} else {
			$this->response = Response::fromString($response);
		}
		
		# Saving last instance in Client
		$this->lastRequest = clone $this;
		
		# Verifying noReset control var
		if (!$this->noReset) {
			$this->reset();
		}
		
		return $this->response;
	}
	
	/**
	 * Magic method for clone the instance of Client
	 */
	public function __clone(){}
	
	/**
	 * Reset the Client state
	 * 
	 * @return \Slice\Http\Client
	 */
	public function reset()
	{
		$this->uri 		   = null;
		$this->method 	   = self::GET;
		$this->headers 	   = array();
		$this->paramsGet   = array();
		$this->paramsPost  = array();
		$this->useRawData  = false;
		$this->dataRaw 	   = null;
		$this->httpVersion = self::HTTP_1;
		$this->curlOpts    = array();
		
		return $this;
	}
}