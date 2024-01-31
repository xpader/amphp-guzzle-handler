# Amphp Http Client Guzzle Handler

Make guzzle non-blocking with amphp.

```php
use Amp\Http\Client\HttpClientBuilder;
use AmpGuzzle\HttpClientHandler;
use GuzzleHttp\Client;

$httpClient = HttpClientBuilder::buildDefault();

$handler = new HttpClientHandler($httpClient);

$client = new Client([
    'handler' => $handler
]);

$response = $client->post('https://www.google.com/');

print_r($response->getBody()->getContents());
```
