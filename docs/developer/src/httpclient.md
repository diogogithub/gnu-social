# HTTP Client
It is sometimes necessary to perform HTTP requests. We simply have a static wrapper
around [Symfony HTTP](https://symfony.com/doc/current/http_client.html#basic-usage).

You can do `App\Core\HTTPClient::{METHOD}(string: $url, array: $options): ResponseInterface`.
The `$options` are elaborated in [Symfony Doc](https://symfony.com/doc/current/http_client.html#configuring-curlhttpclient-options).

Please note that the HTTP Client is [lazy](https://en.wikipedia.org/wiki/Lazy_evaluation),
which makes it very important to bear in mind [Network Errors](https://symfony.com/doc/current/http_client.html#dealing-with-network-errors),

What you can take from Responses is specified [here](https://symfony.com/doc/current/http_client.html#processing-responses).