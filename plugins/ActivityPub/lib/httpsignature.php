<?php
/**
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @category  Network
 * @package   Nautilus
 *
 * @author    Aaron Parecki <aaron@parecki.com>
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *
 * @see      https://github.com/aaronpk/Nautilus/blob/master/app/ActivityPub/HTTPSignature.php
 */
class httpsignature
{
    /**
     * Sign a message with an Actor
     *
     * @param Profile     $user        Actor signing
     * @param string      $url         Inbox url
     * @param bool|string $body        Data to sign (optional)
     * @param array       $addlHeaders Additional headers (optional)
     *
     * @throws Exception Attempted to sign something that belongs to an Actor we don't own
     *
     * @return array Headers to be used in curl
     */
    public static function sign(Profile $user, string $url, $body = false, array $addlHeaders = []): array
    {
        $digest = false;
        if ($body) {
            $digest = self::_digest($body);
        }
        $headers           = self::_headersToSign($url, $digest);
        $headers           = array_merge($headers, $addlHeaders);
        $stringToSign      = self::_headersToSigningString($headers);
        $signedHeaders     = implode(' ', array_map('strtolower', array_keys($headers)));
        $actor_private_key = new Activitypub_rsa();
        // Intentionally unhandled exception, we want this to explode if that happens as it would be a bug
        $actor_private_key = $actor_private_key->get_private_key($user);
        $key               = openssl_pkey_get_private($actor_private_key);
        openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256);
        $signature       = base64_encode($signature);
        $signatureHeader = 'keyId="' . $user->getUri() . '#public-key' . '",headers="' . $signedHeaders . '",algorithm="rsa-sha256",signature="' . $signature . '"';
        unset($headers['(request-target)']);
        $headers['Signature'] = $signatureHeader;

        return self::_headersToCurlArray($headers);
    }

    /**
     * @param mixed $body
     *
     * @return string
     */
    private static function _digest($body): string
    {
        if (is_array($body)) {
            $body = json_encode($body);
        }
        return base64_encode(hash('sha256', $body, true));
    }

    /**
     * @param string $url
     * @param mixed  $digest
     *
     * @throws Exception
     *
     * @return array
     */
    protected static function _headersToSign(string $url, $digest = false): array
    {
        $date = new DateTime('UTC');

        $headers = [
            '(request-target)' => 'post ' . parse_url($url, PHP_URL_PATH),
            'Date'             => $date->format('D, d M Y H:i:s \G\M\T'),
            'Host'             => parse_url($url, PHP_URL_HOST),
            'Accept'           => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams", application/activity+json, application/json',
            'User-Agent'       => 'GNU social ActivityPub Plugin - ' . GNUSOCIAL_ENGINE_URL,
            'Content-Type'     => 'application/activity+json',
        ];

        if ($digest) {
            $headers['Digest'] = 'SHA-256=' . $digest;
        }

        return $headers;
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    private static function _headersToSigningString(array $headers): string
    {
        return implode("\n", array_map(function ($k, $v) {
            return strtolower($k) . ': ' . $v;
        }, array_keys($headers), $headers));
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    private static function _headersToCurlArray(array $headers): array
    {
        return array_map(function ($k, $v) {
            return "{$k}: {$v}";
        }, array_keys($headers), $headers);
    }

    /**
     * @param string $signature
     *
     * @return array
     */
    public static function parseSignatureHeader(string $signature): array
    {
        $parts         = explode(',', $signature);
        $signatureData = [];

        foreach ($parts as $part) {
            if (preg_match('/(.+)="(.+)"/', $part, $match)) {
                $signatureData[$match[1]] = $match[2];
            }
        }

        if (!isset($signatureData['keyId'])) {
            return [
                'error' => 'No keyId was found in the signature header. Found: ' . implode(', ', array_keys($signatureData)),
            ];
        }

        if (!filter_var($signatureData['keyId'], FILTER_VALIDATE_URL)) {
            return [
                'error' => 'keyId is not a URL: ' . $signatureData['keyId'],
            ];
        }

        if (!isset($signatureData['headers']) || !isset($signatureData['signature'])) {
            return [
                'error' => 'Signature is missing headers or signature parts',
            ];
        }

        return $signatureData;
    }

    /**
     * @param $publicKey
     * @param $signatureData
     * @param $inputHeaders
     * @param $path
     * @param $body
     *
     * @return array
     */
    public static function verify($publicKey, $signatureData, $inputHeaders, $path, $body): array
    {
        // We need this because the used Request headers fields specified by Signature are in lower case.
        $headersContent = array_change_key_case($inputHeaders, CASE_LOWER);
        $digest         = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $headersToSign  = [];
        foreach (explode(' ', $signatureData['headers']) as $h) {
            if ($h == '(request-target)') {
                $headersToSign[$h] = 'post ' . $path;
            } elseif ($h == 'digest') {
                $headersToSign[$h] = $digest;
            } elseif (isset($headersContent[$h][0])) {
                $headersToSign[$h] = $headersContent[$h];
            }
        }
        $signingString = self::_headersToSigningString($headersToSign);

        $verified = openssl_verify($signingString, base64_decode($signatureData['signature']), $publicKey, OPENSSL_ALGO_SHA256);

        return [$verified, $signingString];
    }
}
