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

/**
 * Slice Framework
 *
 * A thin PHP Library for perform HTTP requests using CURL library.
 * This library is based on Zend_Http component of Zend Framework 1.
 * 
 * This is the Abstract Response class for provides an object with HTTP response.
 *
 * @package Slice
 * @subpackage Http
 * @namespace \Slice\Http
 * @author Lucas Mendes de Freitas (devsdmf)
 * @copyright Slice Framework (c) devSDMF Software Development Inc.
 * @link https://github.com/zendframework/zf1/blob/master/library/Zend/Http
 *
 */

abstract class ResponseAbstract
{
	/**
	 * List of all known HTTP response codes - used by responseCodeAsText() to 
	 * translate numeric codes to messages.
	 * 
	 * @var array
	 */
	protected static $messages = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
		
		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		
		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // HTTP 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',
			
		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required', 
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
			
		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded',
	);
	
	/**
	 * The HTTP version (1.0, 1.1)
	 * @var string
	 */
	protected $version;
	
	/**
	 * The HTTP response code
	 * @var integer
	 */
	protected $code;
	
	/**
	 * The HTTP response code as string (e.g. 'Not Found' for 404 or 'Internal Server Error' for 500)
	 * @var string
	 */
	protected $message;
	
	/**
	 * The HTTP response headers array
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * The HTTP response body
	 * @var string
	 */
	protected $body;
	
	/**
	 * The Constructor
	 * 
	 * In most cases, you would use fromString() to parse an HTTP response string 
	 * and create a new Slice\Http\Response object.
     *
     * NOTE: The constructor no longer accepts nulls or empty values for the code and
     * headers and will throw an exception if the passed values do not form a valid HTTP
     * responses.
     *
     * If no message is passed, the message will be guessed according to the response code.
	 * 
	 * @param integer $code Response code (200, 204, ...)
	 * @param array   $headers Headers array
	 * @param string  $body Response body
	 * @param string  $version HTTP version
	 * @param string  $message Response code as text
	 * @throws RuntimeException
	 */
	public function __construct($code, array $headers, $body = null, $version = '1.1', $message = null)
	{
		// Make sure the response code is valid and set it
		if (is_null(self::responseCodeAsText($code))) {
			throw new RuntimeException("{$code} is not a valid HTTP response code.");
		}
		
		$this->code = $code;
		
		foreach ($headers as $name => $value) {
			if (is_int($name)) {
				$header = explode(":", $value, 2);
				if (count($header) != 2) {
					throw new RuntimeException("'{$value}' is not a valid HTTP header");
				}
		
				$name  = trim($header[0]);
				$value = trim($header[1]);
			}
		
			$this->headers[ucwords(strtolower($name))] = $value;
		}
		
		// Set the body
		$this->body = $body;
		
		// Set the HTTP version
		if (! preg_match('|^\d\.\d$|', $version)) {
			throw new RuntimeException("Invalid HTTP response version: $version");
		}
		
		$this->version = $version;
		
		// If we got the response message, set it. Else, set it according to
		// the response code
		if (is_string($message)) {
			$this->message = $message;
		} else {
			$this->message = self::responseCodeAsText($code);
		}
	}
	
	/**
	 * Check whether the response is an error
	 * 
	 * @return boolean
	 */
	public function isError()
	{
		$restype = floor($this->code / 100);
		if ($restype == 4 || $restype == 5) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Check whether the response is successful
	 * 
	 * @return boolean
	 */
	public function isSuccessful()
	{
		$restype = floor($this->code / 100);
		if ($restype == 2 || $restype == 1) { // Shouldn't 3xx count as success as well ???
			return true;
		}
	
		return false;
	}
	
	/**
	 * Check whether the response is a redirection
	 * 
	 * @return boolean
	 */
	public function isRedirect()
	{
		$restype = floor($this->code / 100);
		if ($restype == 3) {
			return true;
		}
	
		return false;
	}
	
	/**
	 * Get the response body as string
	 *
	 * This method returns the body of the HTTP response (the content), as it
	 * should be in it's readable version - that is, after decoding it (if it
	 * was decoded), deflating it (if it was gzip compressed), etc.
	 *
	 * If you want to get the raw body (as transfered on wire) use
	 * $this->getRawBody() instead.
	 *
	 * @return string
	 */
	public function getBody()
	{
		$body = '';
	
		// Decode the body if it was transfer-encoded
		switch (strtolower($this->getHeader('transfer-encoding'))) {
	
			// Handle chunked body
			case 'chunked':
				$body = self::decodeChunkedBody($this->body);
				break;
	
				// No transfer encoding, or unknown encoding extension:
				// return body as is
			default:
				$body = $this->body;
				break;
		}
	
		// Decode any content-encoding (gzip or deflate) if needed
		switch (strtolower($this->getHeader('content-encoding'))) {
	
			// Handle gzip encoding
			case 'gzip':
				$body = self::decodeGzip($body);
				break;
	
				// Handle deflate encoding
			case 'deflate':
				$body = self::decodeDeflate($body);
				break;
	
			default:
				break;
		}
	
		return $body;
	}
	
	/**
	 * Get the raw response body (as transfered "on wire") as string
	 *
	 * If the body is encoded (with Transfer-Encoding, not content-encoding -
	 * IE "chunked" body), gzip compressed, etc. it will not be decoded.
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		return $this->body;
	}
	
	/**
	 * Get the HTTP version of the response
	 * 
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}
	
	/**
	 * Get the HTTP response status code
	 * 
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->code;
	}
	
	/**
	 * Return a message describing the HTTP response code (e.g. "OK", "Not Found", "Moved Permanently")
	 * 
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}
	
	/**
	 * Get the response headers
	 * 
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}
	
	/**
	 * Get a specific header as string, or null if it is not set
	 * 
	 * @param string $header
	 * @return string|array|null
	 */
	public function getHeader($header)
	{
		$header = ucwords(strtolower($header));
		if (! is_string($header) || ! isset($this->headers[$header])) return null;
	
		return $this->headers[$header];
	}
	
	/**
	 * Get all headers as string
	 * 
	 * @param boolean $status_line Whether to return the first status line (IE "HTTP 200 OK")
	 * @param string $br Line breaks (e.g "\n", "\r\n", "<br />")
	 * @return string
	 */
	public function getHeadersAsString($status_line = true, $br = "\n")
	{
		$str = '';
	
		if ($status_line) {
			$str = "HTTP/{$this->version} {$this->code} {$this->message}{$br}";
		}
	
		// Iterate over the headers and stringify them
		foreach ($this->headers as $name => $value)
		{
			if (is_string($value))
				$str .= "{$name}: {$value}{$br}";
	
			elseif (is_array($value)) {
				foreach ($value as $subval) {
					$str .= "{$name}: {$subval}{$br}";
				}
			}
		}
	
		return $str;
	}
	
	/**
	 * Get the entire response as string
	 * 
	 * @param string $br Line breaks (e.g. "\n", "\r\n", "<br />")
	 * @return string
	 */
	public function asString($br = "\n")
	{
		return $this->getHeadersAsString(true, $br) . $br . $this->getRawBody();
	}
	
	/**
	 * Implements magic __toString()
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->asString();
	}
	
	/**
	 * A convenience function that returns a text representation of
	 * HTTP response codes. Returns 'Unknown' for unknown codes.
	 * Returns array of all codes, if $code is not specified.
	 *
	 * Conforms to HTTP/1.1 as defined in RFC 2616 (except for 'Unknown')
	 * See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10 for reference
	 *
	 * @param int $code HTTP response code
	 * @param boolean $http11 Use HTTP version 1.1
	 * @return string
	 */
	public static function responseCodeAsText($code = null, $http11 = true)
	{
		$messages = self::$messages;
		
		if (!$http11) $messages[302] = 'Moved Temporarily';
		
		if ($code === null) {
			return $messages;
		} elseif (isset($messages[$code])) {
			return $messages[$code];
		} else {
			return 'Unknown';
		}
	}
	
	/**
	 * Extract the response code from a response string
	 *
	 * @param string $response_str
	 * @return int
	 */
	public static function extractCode($response_str)
	{
		preg_match("|^HTTP/[\d\.x]+ (\d+)|", $response_str, $m);
		
		if (isset($m[1])) {
			return (int) $m[1];
		} else {
			return false;
		}
	}
	
	/**
	 * Extract the HTTP message from a response
	 *
	 * @param string $response_str
	 * @return string
	 */
	public static function extractMessage($response_str)
	{
		preg_match("|^HTTP/[\d\.x]+ \d+ ([^\r\n]+)|", $response_str, $m);
		
		if (isset($m[1])) {
			return $m[1];
		} else {
			return false;
		}
	}
	
	/**
	 * Extract the HTTP version from a response
	 *
	 * @param string $response_str
	 * @return string
	 */
	public static function extractVersion($response_str)
	{
		preg_match("|^HTTP/([\d\.x]+) \d+|", $response_str, $m);
		
		if (isset($m[1])) {
			return $m[1];
		} else {
			return false;
		}
	}
	
	/**
	 * Extract the headers from a response string
	 *
	 * @param   string $response_str
	 * @return  array
	 */
	public static function extractHeaders($response_str)
	{
		$headers = array();
		
		// First, split body and headers
		$parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
		if (! $parts[0]) return $headers;
		
		// Split headers part to lines
		$lines = explode("\n", $parts[0]);
		unset($parts);
		$last_header = null;
		
		foreach($lines as $line) {
			$line = trim($line, "\r\n");
			if ($line == "") break;
		
			// Locate headers like 'Location: ...' and 'Location:...' (note the missing space)
			if (preg_match("|^([\w-]+):\s*(.+)|", $line, $m)) {
				unset($last_header);
				$h_name = strtolower($m[1]);
				$h_value = $m[2];
		
				if (isset($headers[$h_name])) {
					if (! is_array($headers[$h_name])) {
						$headers[$h_name] = array($headers[$h_name]);
					}
		
					$headers[$h_name][] = $h_value;
				} else {
					$headers[$h_name] = $h_value;
				}
				$last_header = $h_name;
			} elseif (preg_match("|^\s+(.+)$|", $line, $m) && $last_header !== null) {
				if (is_array($headers[$last_header])) {
					end($headers[$last_header]);
					$last_header_key = key($headers[$last_header]);
					$headers[$last_header][$last_header_key] .= $m[1];
				} else {
					$headers[$last_header] .= $m[1];
				}
			}
		}
		
		return $headers;
	}
	
	/**
	 * Extract the body from a response string
	 *
	 * @param string $response_str
	 * @return string
	 */
	public static function extractBody($response_str)
	{
		$parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
		if (isset($parts[1])) {
			return trim($parts[1]);
		}
		return '';
	}
	
	/**
	 * Decode a "chunked" transfer-encoded body and return the decoded text
	 *
	 * @param string $body
	 * @return string
	 */
	public static function decodeChunkedBody($body)
	{
		$decBody = '';
		
		// If mbstring overloads substr and strlen functions, we have to
		// override it's internal encoding
		if (function_exists('mb_internal_encoding') &&
				((int) ini_get('mbstring.func_overload')) & 2) {
		
						$mbIntEnc = mb_internal_encoding();
						mb_internal_encoding('ASCII');
		}
		
		while (trim($body)) {
				if (! preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $m)) {
					throw new RuntimeException("Error parsing body - doesn't seem to be a chunked message");
		}
		
		$length = hexdec(trim($m[1]));
            $cut = strlen($m[0]);
            $decBody .= substr($body, $cut, $length);
		            $body = substr($body, $cut + $length + 2);
		}
		
		if (isset($mbIntEnc)) {
		mb_internal_encoding($mbIntEnc);
		}
		
		return $decBody;
	}
	
	/**
	 * Decode a gzip encoded message (when Content-encoding = gzip)
	 *
	 * Currently requires PHP with zlib support
	 *
	 * @param string $body
	 * @return string
	 */
	public static function decodeGzip($body)
	{
		if (! function_exists('gzinflate')) {
			throw new RuntimeException('zlib extension is required in order to decode "gzip" encoding');
		}
		
		return gzinflate(substr($body, 10));
	}
	
	/**
	 * Decode a zlib deflated message (when Content-encoding = deflate)
	 *
	 * Currently requires PHP with zlib support
	 *
	 * @param string $body
	 * @return string
	 */
	public static function decodeDeflate($body)
	{
		if (! function_exists('gzuncompress')) {
			throw new RuntimeException('zlib extension is required in order to decode "deflate" encoding');
		}
		
		/**
		 * Some servers (IIS ?) send a broken deflate response, without the
		 * RFC-required zlib header.
		 *
		 * We try to detect the zlib header, and if it does not exsit we
		 * teat the body is plain DEFLATE content.
		 *
		 * This method was adapted from PEAR HTTP_Request2 by (c) Alexey Borzov
		 *
		 * @link http://framework.zend.com/issues/browse/ZF-6040
		 */
		$zlibHeader = unpack('n', substr($body, 0, 2));
		if ($zlibHeader[1] % 31 == 0) {
			return gzuncompress($body);
		} else {
			return gzinflate($body);
		}
	}
	
	/**
	 * Create a new Response object from a string
	 *
	 * @param string $response
	 * @return \Slice\Http\AbstractResponse
	 */
	public static function fromString($response)
	{
		$code 	 = self::extractCode($response);
		$headers = self::extractHeaders($response);
		$body 	 = self::extractBody($response);
		$version = self::extractVersion($response);
		$message = self::extractMessage($response);
		
		# FIX FOR MULTIPLE HTTP RESPONSE CODE RETURNED BY SERVER 
		if ($code === 100 && count($headers) === 0 && self::extractCode($body)) {
			return self::fromString($body);
		}
		
		return new static($code, $headers, $body, $version, $message);
	}
	
	public static function getHandler()
	{
		return new static(204,array(),'','1.1');
	}
}