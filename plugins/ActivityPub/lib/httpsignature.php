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
 * @author    Aaron Parecki <aaron@parecki.com>
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link      https://github.com/aaronpk/Nautilus/blob/master/app/ActivityPub/HTTPSignature.php
 */

class HttpSignature
{
    public static function sign($user, $url, $body = false, $addlHeaders = [])
    {
        $digest = false;
        if ($body) {
            $digest = self::_digest($body);
        }
        $headers = self::_headersToSign($url, $digest);
        $headers = array_merge($headers, $addlHeaders);
        $stringToSign = self::_headersToSigningString($headers);
        $signedHeaders = implode(' ', array_map('strtolower', array_keys($headers)));
        $actor_private_key = new Activitypub_rsa();
        $actor_private_key = $actor_private_key->get_private_key($user);
        $key = openssl_pkey_get_private($actor_private_key);
        openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);
        $signatureHeader = 'keyId="' . ActivityPubPlugin::actor_uri($user).'#public-key' . '",headers="' . $signedHeaders . '",algorithm="rsa-sha256",signature="' . $signature . '"';
        unset($headers['(request-target)']);
        $headers['Signature'] = $signatureHeader;

        return self::_headersToCurlArray($headers);
    }

    private static function _digest($body)
    {
        if (is_array($body)) {
            $body = json_encode($body);
        }
        return base64_encode(hash('sha256', $body, true));
    }

    protected static function _headersToSign($url, $digest = false)
    {
        $date = new DateTime('UTC');

        $headers = [
            '(request-target)' => 'post ' . parse_url($url, PHP_URL_PATH),
            'Date' => $date->format('D, d M Y H:i:s \G\M\T'),
            'Host' => parse_url($url, PHP_URL_HOST),
            'Accept' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams", application/activity+json, application/json',
            'User-Agent'   => 'GNU social ActivityPub Plugin - https://gnu.io/social',
            'Content-Type' => 'application/activity+json'
        ];

        if ($digest) {
            $headers['Digest'] = 'SHA-256=' . $digest;
        }

        return $headers;
    }

    private static function _headersToSigningString($headers)
    {
        return implode("\n", array_map(function ($k, $v) {
            return strtolower($k) . ': ' . $v;
        }, array_keys($headers), $headers));
    }

    private static function _headersToCurlArray($headers)
    {
        return array_map(function ($k, $v) {
            return "$k: $v";
        }, array_keys($headers), $headers);
    }

    public static function parseSignatureHeader($signature)
    {
        $parts = explode(',', $signature);
        $signatureData = [];

        foreach ($parts as $part) {
            if (preg_match('/(.+)="(.+)"/', $part, $match)) {
                $signatureData[$match[1]] = $match[2];
            }
        }

        if (!isset($signatureData['keyId'])) {
            return [
                'error' => 'No keyId was found in the signature header. Found: ' . implode(', ', array_keys($signatureData))
            ];
        }

        if (!filter_var($signatureData['keyId'], FILTER_VALIDATE_URL)) {
            return [
                'error' => 'keyId is not a URL: ' . $signatureData['keyId']
            ];
        }

        if (!isset($signatureData['headers']) || !isset($signatureData['signature'])) {
            return [
                'error' => 'Signature is missing headers or signature parts'
            ];
        }

        return $signatureData;
    }

    public static function verify($publicKey, $signatureData, $inputHeaders, $path, $body)
    {
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $headersToSign = [];
        foreach (explode(' ', $signatureData['headers']) as $h) {
            if ($h == '(request-target)') {
                $headersToSign[$h] = 'post ' . $path;
            } elseif ($h == 'digest') {
                $headersToSign[$h] = $digest;
            } elseif (isset($inputHeaders[$h][0])) {
                $headersToSign[$h] = $inputHeaders[$h];
            }
        }
        $signingString = self::_headersToSigningString($headersToSign);

        $verified = openssl_verify($signingString, base64_decode($signatureData['signature']), $publicKey, OPENSSL_ALGO_SHA256);

        return [$verified, $signingString];
    }
}
