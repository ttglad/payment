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
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\StringHelper;

abstract class UnionBaseService extends BaseService
{
    /**
     * 银联成功返回code
     */
    const REQ_SUCCESS = '00';

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
        $this->gatewayUrl = self::$config->get('gateway_url', 'https://gateway.95516.com/%s');
        $this->version = self::$config->get('version', '5.0.0');
        $this->signMethod = self::$config->get('sign_method', '01');
        $this->merId = self::$config->get('mer_id', '');

        switch ($this->signMethod) {
            case '01':
                $this->certDir = self::$config->get('cert_dir', '');
                $this->certPath = self::$config->get('cert_path', '');
                $this->certPwd = self::$config->get('cert_pwd', '');

                if (empty($this->certPath) || empty($this->certPwd)) {
                    throw new PaymentException('[certPath] and [certPwd] is need.', PaymentCode::PARAM_ERROR);
                }
                if (!file_exists($this->certPath)) {
                    throw new PaymentException('[certPath] is not exist.', PaymentCode::PARAM_ERROR);
                }
                break;
            case '11':
                $this->secureKey = self::$config->get('secure_key', '');

                if (empty($this->secureKey)) {
                    throw new PaymentException('[secureKey] is need.', PaymentCode::PARAM_ERROR);
                }
                break;
            default:
                throw new PaymentException(sprintf('[%s] is not support.', $this->signMethod), PaymentCode::SIGN_ERROR);
        }

    }

    /**
     * @param array $requestParams
     * @return string
     * @throws PaymentException
     */
    public function buildParam(array $requestParams)
    {

        try {
            $params = [
                'version' => $this->version,
                'encoding' => self::$config->get('encoding', 'UTF-8'),
                'currencyCode' => self::$config->get('currency_code', '156'),
                'signMethod' => $this->signMethod,
                'merId' => $this->merId,
                'certId' => $this->getCertId($this->certPath, $this->certPwd),
            ];

            if (!empty($requestParams)) {
                $selfParams = $this->getSelfParams($requestParams);

                if (is_array($selfParams) && !empty($selfParams)) {
                    $params = array_merge($params, $selfParams);
                }
            }
            $params = ArrayHelper::paramFilter($params);

            ksort($params);

            $signStr = StringHelper::createLinkString($params);
            $params['signature'] = $this->makeSign($signStr);

            $this->checkParam($params, $this->needsKey);
        } catch (Exception $e) {
            throw new PaymentException($e->getMessage(), PaymentCode::PARAM_ERROR);
        }
        return $params;
    }

    /**
     * @param $params
     * @return string
     * @throws PaymentException
     */
    private function makeSign($params)
    {
        try {
            switch ($this->signMethod) {
                case '01':
                    $private_key = $this->getPrivateKey($this->certPath, $this->certPwd);
                    if ($this->version == '5.0.0') {
                        $params_sha1x16 = sha1($params, false);
                        $sign_result = openssl_sign($params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1);
                    } elseif ($this->version == '5.1.0') {
                        $params_sha256x16 = hash('sha256', $params);
                        $sign_result = openssl_sign($params_sha256x16, $signature, $private_key, 'sha256');
                    } else {
                        throw new PaymentException(sprintf('version [%s] is not support', $this->version),
                            PaymentCode::SIGN_ERROR);
                    }
                    // 签名
                    if (!$sign_result) {
                        throw new PaymentException('union sign error', PaymentCode::SIGN_ERROR);
                    }
                    $signature = base64_encode($signature);
                    break;
                case '11':
                    $params_before = hash('sha256', $this->secureKey);
                    $params_before = $params . '&' . $params_before;
                    $signature = hash('sha256', $params_before);
                    break;
                case '12':
                    throw new PaymentException(sprintf('[%s] sign method is not apply.', $this->signMethod),
                        PaymentCode::SIGN_ERROR);
                default:
                    throw new PaymentException(sprintf('[%s] sign method is not support.', $this->signMethod),
                        PaymentCode::SIGN_ERROR);
            }
        } catch (PaymentException $e) {
            throw new PaymentException($e->getMessage(), $e->getCode());
        }

        return $signature;
    }

    /**
     * @param array $params
     * @return bool|int
     * @throws PaymentException
     */
    protected function verifySign(array $params)
    {
        try {
            if (!isset($params['signature']) || StringHelper::checkEmpty($params['signature'])) {
                throw new PaymentException('sign key [signature] is not exists.', PaymentCode::UNION_CHECK_FAILED);
            }
            $signature = $params['signature'];
            unset($params['signature']);

            $paramsStr = StringHelper::createLinkString($params);

            switch ($params['signMethod']) {
                case '01':
                    if ($params['version'] == '5.0.0') {
                        $publicKey = $this->getPublicKeyByCertId($params['certId'], $this->certDir);
                        $signature = base64_decode($signature);
                        $params_sha1x16 = sha1($paramsStr, false);
                        $result = openssl_verify($params_sha1x16, $signature, $publicKey, OPENSSL_ALGO_SHA1);
                    } elseif ($params['version'] == '5.1.0') {

                        $strCert = $params['signPubKeyCert'];
                        /**
                         * todo 检验
                         * 2020/12/18 add by TaoYl
                         * openssl_x509_checkpurpose($strCert, X509_PURPOSE_ANY, []);
                         */
                        $params_sha256x16 = hash('sha256', $paramsStr);
                        $signature = base64_decode($signature);
                        $result = openssl_verify($params_sha256x16, $signature, $strCert, "sha256");
                    } else {
                        throw new PaymentException(sprintf('version [%s] is not support', $this->version),
                            PaymentCode::SIGN_ERROR);
                    }
                    break;
                case '11':
                    $params_before_sha256 = hash('sha256', $this->secureKey);
                    $params_before_sha256 = $paramsStr . '&' . $params_before_sha256;
                    $params_after_sha256 = hash('sha256', $params_before_sha256);
                    $result = ($params_after_sha256 == $signature);
                    break;
                case '12':
                    throw new PaymentException(sprintf('[%s] sign method is not apply.', $this->signMethod),
                        PaymentCode::SIGN_ERROR);
                default:
                    throw new PaymentException(sprintf('[%s] sign method is not support.', $this->signMethod),
                        PaymentCode::SIGN_ERROR);
            }

            // 签名
            if (!$result) {
                throw new PaymentException('union verify error', PaymentCode::SIGN_ERROR);
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
    private function getCertId($certPath, $certPwd)
    {
        $pkcs12certdata = file_get_contents($certPath);

        openssl_pkcs12_read($pkcs12certdata, $certs, $certPwd);
        $x509data = $certs['cert'];
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        return $certdata['serialNumber'];
    }

    /**
     * @param $certPath
     * @return mixed
     */
    private function getCertIdByCerPath($certPath)
    {
        $x509data = file_get_contents($certPath);
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        $cert_id = $certdata['serialNumber'];
        return $cert_id;
    }

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
     * @param $certId
     * @param $path
     * @return false|string|null
     */
    protected function getPublicKeyByCertId($certId, $path)
    {
        // 证书目录
        $handle = opendir($path);
        if ($handle) {
            while ($file = readdir($handle)) {
                clearstatcache();
                $filePath = $path . '/' . $file;
                if (is_file($filePath)) {
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'cer') {
                        if ($this->getCertIdByCerPath($filePath) == $certId) {
                            closedir($handle);
                            return file_get_contents($filePath);
                        }
                    }
                }
            }
        }
        closedir($handle);
        return null;
    }

    /**
     * @param array $param
     * @param array $needParam
     * @throws PaymentException
     */
    protected function checkParam(array $param = [], array $needParam = [])
    {
        if (!empty($needParam)) {
            foreach ($needParam as $item) {
                if (!isset($param["$item"]) || StringHelper::checkEmpty($param["$item"])) {
                    throw new PaymentException(sprintf('key [%s] is need', $item), PaymentCode::PARAM_ERROR);
                }
            }
        }
    }

}
