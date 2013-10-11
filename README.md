Slice\Http
==========

A thin PHP Library to perform HTTP requests for Web Services, available request methods are GET, POST, PUT and DELETE, can also send request headers, send GET and POST parameters, send data in RAW format and support for HTTP versions 1.0 and 1.1. 
This library is based on Zend_Http component of Zend Framework 1 and needs a CURL extension installed.

Example
-------

```php
use Slice\Http\Client;

# Initializing Client
$client = new Client();

# Set method
$client->setMethod($client::POST);

# Set URI
$client->setUri('http://localhost/ws/');

# Set GET parameters
$client->setParameterGet('fooGet','barGet');

# Set POST parameters
$client->setParameterPost(array(
	'var1'=>'value1',
	'var2'=>'value2',
));

# Set headers
$client->setHeaders('foo','bar');

# Set HTTP version
$client->setHTTPVersion($client::HTTP_0);

# Performing request
$response = $client->request();
```