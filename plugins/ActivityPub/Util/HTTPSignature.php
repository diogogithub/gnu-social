<?php

declare(strict_types = 1);

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

namespace Plugin\ActivityPub\Util;

use App\Entity\Actor;
use DateTime;
use Exception;
use Plugin\ActivityPub\Entity\ActivitypubRsa;

class HTTPSignature
{
    /**
     * Sign a message with an Actor
     *
     * @param Actor       $user        Actor signing
     * @param string      $url         Inbox url
     * @param bool|string $body        Data to sign (optional)
     * @param array       $addlHeaders Additional headers (optional)
     *
     * @throws Exception Attempted to sign something that belongs to an Actor we don't own
     *
     * @return array Headers to be used in request
     */
    public static function sign(Actor $user, string $url, string|bool $body = false, array $addlHeaders = []): array
    {
        $digest = false;
        if ($body) {
            $digest = self::_digest($body);
        }
        $headers           = self::_headersToSign($url, $digest);
        $headers           = array_merge($headers, $addlHeaders);
        $stringToSign      = self::_headersToSigningString($headers);
        $signedHeaders     = implode(' ', array_map('strtolower', array_keys($headers)));
        $actor_private_key = ActivitypubRsa::getByActor($user)->getPrivateKey();
        // Intentionally unhandled exception, we want this to explode if that happens as it would be a bug
        $key = openssl_pkey_get_private($actor_private_key);
        openssl_sign($stringToSign, $signature, $key, \OPENSSL_ALGO_SHA256);
        $signature       = base64_encode($signature);
        $signatureHeader = 'keyId="' . $user->getUri() . '#public-key' . '",headers="' . $signedHeaders . '",algorithm="rsa-sha256",signature="' . $signature . '"';
        unset($headers['(request-target)']);
        $headers['Signature'] = $signatureHeader;

        return $headers;
    }

    /**
     * @param array|string $body array or json string $body
     */
    private static function _digest(array|string $body): string
    {
        if (\is_array($body)) {
            $body = json_encode($body);
        }
        return base64_encode(hash('sha256', $body, true));
    }

    /**
     * @throws Exception
     */
    protected static function _headersToSign(string $url, string|bool $digest = false): array
    {
        $date = new DateTime('UTC');

        $headers = [
            '(request-target)' => 'post ' . parse_url($url, \PHP_URL_PATH),
            'Date'             => $date->format('D, d M Y H:i:s \G\M\T'),
            'Host'             => parse_url($url, \PHP_URL_HOST),
            'Accept'           => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams", application/activity+json, application/json',
            'User-Agent'       => 'GNU social ActivityPub Plugin - ' . GNUSOCIAL_ENGINE_URL,
            'Content-Type'     => 'application/activity+json',
        ];

        if ($digest) {
            $headers['Digest'] = 'SHA-256=' . $digest;
        }

        return $headers;
    }

    private static function _headersToSigningString(array $headers): string
    {
        return implode("\n", array_map(fn ($k, $v) => mb_strtolower($k) . ': ' . $v, array_keys($headers), $headers));
    }

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

        if (!filter_var($signatureData['keyId'], \FILTER_VALIDATE_URL)) {
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

    public static function verify(string $publicKey, array $signatureData, array $inputHeaders, string $path, string $body): array
    {
        // We need this because the used Request headers fields specified by Signature are in lower case.
        $headersContent = array_change_key_case($inputHeaders, \CASE_LOWER);
        $digest         = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $headersToSign  = [];
        foreach (explode(' ', $signatureData['headers']) as $h) {
            if ($h == '(request-target)') {
                $headersToSign[$h] = 'post ' . $path;
            } elseif ($h == 'digest') {
                $headersToSign[$h] = $digest;
            } elseif (\array_key_exists($h, $headersContent)) {
                $headersToSign[$h] = $headersContent[$h];
            }
        }
        $signingString = self::_headersToSigningString($headersToSign);

        $verified = openssl_verify($signingString, base64_decode($signatureData['signature']), $publicKey, \OPENSSL_ALGO_SHA256);

        return [$verified, $signingString];
    }
}
