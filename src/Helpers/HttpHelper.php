<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/10 11:13 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Helpers;


use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class HttpHelper
{

    /**
     * @param string $url
     * @param array $query
     * @param array $headers
     * @param float $timeOut
     * @param array $options
     * @param bool $parseContent
     * @return mixed|string
     */
    public static function get(
        string $url = '',
        array $query = [],
        array $headers = [],
        float $timeOut = 5.0,
        array $options = [],
        bool $parseContent = true
    ) {
        $client = self::getHttpClient('', $timeOut, $options);

        $queryOption = [
            'headers' => $headers,
            'http_errors' => false,
        ];
        if (!empty($query)) {
            $queryOption['query'] = $query;
        }

        $response = $client->get($url, $queryOption);

        if ($parseContent) {
            $response = self::parseResponse($response);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param float $timeOut
     * @param array $options
     * @param bool $parseContent
     * @return mixed|string
     */
    public static function post(
        string $url = '',
        array $params = [],
        array $headers = [],
        float $timeOut = 5.0,
        array $options = [],
        bool $parseContent = true
    ) {
        $client = self::getHttpClient('', $timeOut, $options);
        $response = $client->post($url, [
            'headers' => $headers,
            'form_params' => $params,
            'http_errors' => false,
        ]);

        if ($parseContent) {
            $response = self::parseResponse($response);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param string $params
     * @param array $headers
     * @param float $timeOut
     * @param array $options
     * @param bool $parseContent
     * @return mixed|ResponseInterface|string
     */
    public static function postXml(
        string $url = '',
        string $params = '',
        array $headers = [],
        float $timeOut = 5.0,
        array $options = [],
        bool $parseContent = true
    ) {
        $client = self::getHttpClient('', $timeOut, $options);

        $response = $client->post($url, [
            'headers' => $headers,
            'body' => $params,
            'http_errors' => false,
        ]);

        if ($parseContent) {
            $response = self::parseResponse($response);
        }

        return $response;
    }


    /**
     * @param string $url
     * @param float $timeOut
     * @param array $option
     * @return Client
     */
    private static function getHttpClient(string $url = '', float $timeOut = 5.0, array $option = [])
    {
        $options = [
            'base_uri' => $url,
            'timeout' => $timeOut,
        ];

        if (!empty($option)) {
            $options = array_merge($options, $option);
        }

        return new Client($options);
    }

    /**
     * @param ResponseInterface $response
     * @return mixed|string
     */
    private static function parseResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        $data = $contents;
        if ((false !== stripos($contentType, 'json')) || (false !== stripos($contentType, 'javascript'))) {
            $data = json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            $data = json_decode(json_encode(simplexml_load_string($contents)), true);
        }

        return $data;
    }
}
