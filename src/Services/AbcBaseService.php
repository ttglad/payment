<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/17 10:26 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\EncryptHelper;

abstract class AbcBaseService extends BaseService
{
    /**
     * 成功返回code
     */
    const REQ_SUCCESS = '0000';

    /**
     * @var mixed|null
     */
    protected $gatewayUrl = '';

    /**
     * @var mixed|null
     */
    private $version = '';

    /**
     * @var mixed|null
     * 01（表示采用RSA签名） HASH表示散列算法
     * 11：支持散列方式验证SHA-256
     * 12：支持散列方式验证SM3
     */
    private $signMethod = '';

    /**
     * @var mixed|null
     */
    private $certDir = '';

    /**
     * @var mixed|null
     */
    private $certPath = '';

    /**
     * @var mixed|null
     */
    private $certPwd = '';

    /**
     * @var mixed|null
     */
    private $secureKey = '';

    /**
     * @var array
     */
    protected $needsKey = [];

    /**
     * UnionBaseService constructor.
     * @throws PaymentException
     */
    public function __construct()
    {
        $this->gatewayUrl = self::$config->get('gateway_url',
            'https://pay.abchina.com/ebus/trustpay/ReceiveMerchantTrxReqServlet');
        $this->version = self::$config->get('version', 'V3.0.0');
        $this->merchantId = self::$config->get('merchant_id', '');
        $this->certPath = self::$config->get('cert_path', '');
        $this->certPwd = self::$config->get('cert_pwd', '');
        $this->platCert = self::$config->get('plat_cert_path', '');
    }

    /**
     * @param array $requestParams
     * @return string
     * @throws PaymentException
     *         $tMessage = "{\"Version\":\"V3.0.0\",\"Format\":\"JSON\",\"Merchant\":" . "{\"ECMerchantType\":\"" . "EBUS" . "\",\"MerchantID\":\"" . MerchantConfig::getMerchantID($aMerchantNo) . "\"},\"Common\":" . "{\"Channel\":\"" . "Merchant"  . "\"}," . "\"TrxRequest\":" . $aMessage . "}";
     */
    public function buildParam(array $requestParams)
    {

        try {
            $params = [
                'Version' => $this->version,
                'Format' => self::$config->get('format', 'JSON'),
                'Merchant' => [
                    'ECMerchantType' => 'EBUS',
                    'MerchantID' => $this->merchantId,
                ],
                'Common' => [
                    'Channel' => 'Merchant',
                ],
                'TrxRequest' => $this->getSelfParams($requestParams)
            ];

            $result = [
                'Message' => $params,
                'Signature-Algorithm' => 'SHA1withRSA',
                'Signature' => $this->makeSign($params),
            ];

        } catch (Exception $e) {
            throw new PaymentException($e->getMessage(), PaymentCode::PARAM_ERROR);
        }
        return json_encode($result);
    }

    /**
     * @param $params
     * @return string
     * @throws PaymentException
     */
    private function makeSign($params)
    {
        try {
            $pKey = $this->getPrivateKey($this->certPath, $this->certPwd);
            $pkey = openssl_pkey_get_private($pKey);


            if (!openssl_sign(json_encode($params), $signature, $pkey, OPENSSL_ALGO_SHA1)) {
                throw new PaymentException('union sign error', PaymentCode::SIGN_ERROR);
            }

            $signature = base64_encode($signature);

        } catch (PaymentException $e) {
            throw new PaymentException($e->getMessage(), $e->getCode());
        }

        return $signature;
    }

    /**
     * @param array $param
     * @return false|string
     * @throws PaymentException
     */
    protected function httpRequest(array $param)
    {
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'user_agent' => 'TrustPayClient V3.0.0',
                'protocol_version' => 1.0,
                'header' => array('Content-Type: text/html', 'Accept: */*'),
                'content' => $this->buildParam($param)
            ),
            'ssl' => array(
                'verify_peer' => false
            )
        );

        $context = stream_context_create($opts);
        return file_get_contents($this->gatewayUrl, false, $context);
    }

    /**
     * @param array $params
     * @return bool|int
     * @throws PaymentException
     */
    protected function verifySign(string $message, string $signature)
    {
        $result = false;
        try {

            if (empty($message)) {
                throw new PaymentException('verify message is empty', PaymentCode::PARAM_ERROR);
            }

            if (empty($signature)) {
                throw new PaymentException('verify signature is empty', PaymentCode::PARAM_ERROR);
            }

            $signature = base64_decode($signature);

            $key = openssl_pkey_get_public(openssl_x509_read(EncryptHelper::der2pem(file_get_contents($this->platCert))));

            if (!$result = openssl_verify($message, $signature, $key, OPENSSL_ALGO_SHA1)) {
                throw new PaymentException('verify signature error', PaymentCode::SIGN_ERROR);
            }

        } catch (PaymentException $e) {
            throw new PaymentException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getSelfParams(array $requestParams);


    /**
     * @param $certPath
     * @param $certPwd
     * @return mixed
     */
    private function getPrivateKey($certPath, $certPwd)
    {
        $pkcs12 = file_get_contents($certPath);
        openssl_pkcs12_read($pkcs12, $certs, $certPwd);
        return $certs['pkey'];
    }

    /**
     * @param $aTag
     * @param $message
     * @return string
     */
    public function getValue($aTag, $message)
    {
        $json = $message;
        $index = 0;
        $length = 0;
        $index = strpos($json, $aTag, 0);
        if ($index === false) {
            return "";
        }
        do {
            if ($json[$index - 1] === "\"" && $json[$index + strlen($aTag)] === "\"") {
                break;
            } else {
                $index = strpos($json, $aTag, $index + 1);
                if ($index === false) {
                    return "";
                }
            }
        } while (true);
        $index = $index + strlen($aTag) + 2;
        $c = $json[$index];
        if ($c === '{') {
            $output = $this->GetObjectValue($index, $json);
        }
        if ($c === '"') {
            $output = $this->GetStringValue($index, $json);
        }
        return $output;
    }

    /**
     * @param $index
     * @param $json
     * @return string
     */
    private function GetObjectValue($index, $json)
    {
        $count = 0;
        $_output = "";
        do {
            $c = $json[$index];
            if ($c === '{') {
                $count++;
            }
            if ($c === '}') {
                $count--;
            }

            if ($count !== 0) {
                $_output = $_output . $c;
            } else {
                $_output = $_output . $c;
                return $_output;
            }
            $index++;
        } while (true);
    }

    /**
     * @param $index
     * @param $json
     * @return string
     */
    private function GetStringValue($index, $json)
    {
        $index++;
        $_output = "";
        do {
            $c = $json[$index++];
            if ($c !== '"') {
                $_output = $_output . $c;
            } else {
                return $_output;
            }

        } while (true);
    }
}
