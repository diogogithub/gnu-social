# HTTP Client
It is sometimes necessary to perform HTTP requests. We simply have a static wrapper
around [Symfony HTTP](https://symfony.com/doc/current/http_client.html#basic-usage).

You can do `App\Core\HTTPClient::{METHOD}(string: $url, array: $options): ResponseInterface`.
The `$options` are elaborated in [Symfony Doc](https://symfony.com/doc/current/http_client.html#configuring-curlhttpclient-options).

Please note that the HTTP Client is [lazy](https://en.wikipedia.org/wiki/Lazy_evaluation),
which makes it very important to bear in mind [Network Errors](https://symfony.com/doc/current/http_client.html#dealing-with-network-errors),

An example where this behaviour has to be considered:

```php
if (Common::isValidHttpUrl($url)) {
    $head = HTTPClient::head($url);
    // This must come before getInfo given that Symfony HTTPClient is lazy (thus forcing curl exec)
    try {
        $headers = $head->getHeaders();
    } catch (ClientException $e) {
        throw new InvalidArgumentException(previous: $e);
    }
    $url      = $head->getInfo('url'); // The last effective url (after getHeaders, so it follows redirects)
    $url_hash = hash(self::URLHASH_ALGO, $url);
} else {
    throw new InvalidArgumentException();
}
```

What you can take from Responses is specified [here](https://symfony.com/doc/current/http_client.html#processing-responses).