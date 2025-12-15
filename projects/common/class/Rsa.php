<?php
namespace service;

/**
 * 非对称加密解密
 * 
 * @author wangwei
 * 
 * genrsa -out rsa_private_key.pem 1024 // 生成原始 RSA私钥文件
 * openssl pkcs8 -topk8 -inform PEM -in rsa_private_key.pem -outform PEM -nocrypt -out private_key.pem // 将原始 RSA私钥转换为 pkcs8格式
 * openssl rsa -in rsa_private_key.pem -pubout -out rsa_public_key.pem // 生成RSA公钥
 * 
 * // 我们将私钥rsa_private_key.pem用在服务器端，公钥发放给android跟ios等前端。
 */
class Rsa 
{
	private static $PRIVATE_KEY = '-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQDKRDH+ByERopZdSbHDmFqY3FxI1IP/6lNTbd+YX5CW+M/Yzwwl
4UMsGnuhT9mo9UYrrFGC9SsxSuQnzDor8zAMPqSx2AdS8HNiTR94fF5v9hKpY5F1
un7QRPtuXl+W2Vn6IkZyRFZTwbR+Cb0B4ZBnUWeeoVyeTF8wid51JsiNcQIDAQAB
AoGAcH080Gpmmcgyl+9ETeONfzYOnPKT9t/7N4sDr1p2r3/xGEMOHoMJOJ4B49Tk
2HKQc/mB27M+MkvUV83dDFRTg35pwlpYEFpsXmr+OIxvyk+RqiJvYvNGEdFpMzUL
8Fjualu6i8eMyBCjYfiTegarLmmM0YE/AV0BiyngQ0DBjYECQQDu/nZYkrvF3e2+
2m19TDyxDsCNtvEV74kjxj462j0c7pEeb/09Gk5z7vVpNav1a8Tv0248WRzsykGm
vRFaBbePAkEA2KizCmU7VcKumqev5PDGtziMF/plYffBO8OnDGBzxhfG5I/UiTJ4
n4DaBJ7N/BB7k7vIAgEDwueWQIy7ctLq/wJAQLjAq/PwzgDv5YOZqxj+RqTMGJS2
bU5VQU7qg12etzsUKb4CQo3hORw5caiLTQdGafxEGiu33ZhYdyM0k8CAmQJAZG/I
GACXwgjvAljMDJilthgrsY3tY74DwR3RGca4xNMO67PVdgiErISCDPRFTx2g+/po
HK21vau4FpJm7zLhpwJADnUXbV9KepXk70IYaNWeuAAPq566AUOYOCeVCl+/jaJB
6yhHleWVEMP1mf964m6EHRewq947n7v0QpCKpfQ+4g==
-----END RSA PRIVATE KEY-----';
	private static $PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDKRDH+ByERopZdSbHDmFqY3FxI
1IP/6lNTbd+YX5CW+M/Yzwwl4UMsGnuhT9mo9UYrrFGC9SsxSuQnzDor8zAMPqSx
2AdS8HNiTR94fF5v9hKpY5F1un7QRPtuXl+W2Vn6IkZyRFZTwbR+Cb0B4ZBnUWee
oVyeTF8wid51JsiNcQIDAQAB
-----END PUBLIC KEY-----';

	/**
	 * 获取私钥
	 * 
	 * @return bool|resource
	 */
	private static function getPrivateKey()
	{
		$privKey = self::$PRIVATE_KEY;
		return openssl_pkey_get_private($privKey);
	}

	/**
	 * 获取公钥
	 * 
	 * @return bool|resource
	 */
	private static function getPublicKey()
	{
		$publicKey = self::$PUBLIC_KEY;
		return openssl_pkey_get_public($publicKey);
	}

	/**
	 * 私钥加密
	 * 
	 * @param 		string 		$data
	 * 
	 * @return null|string
	 */
	public static function privEncrypt($data = '')
	{
		if (!is_string($data)) {
			return null;
		}

		return openssl_private_encrypt($data, $encrypted, self::getPrivateKey()) ? base64_encode($encrypted) : null;
	}

	/**
	 * 公钥加密
	 * 
	 * @param 	string 	$data 	需要加密的数据
	 * 
	 * @return null|string
	 */
	public static function publicEncrypt($data = '')
	{
		if (!is_string($data)) {
			return null;
		}
		return openssl_public_encrypt($data, $encrypted, self::getPublicKey()) ? base64_encode($encrypted) : null;
	}

	/**
	 * 私钥解密
	 * 
	 * @param 	string 	$encrypted 		需要解密的数据
	 * 
	 * @return null
	 */
	public static function privDecrypt($encrypted = '')
	{
		if (!is_string($encrypted)) {
			return null;
		}
		return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, self::getPrivateKey())) ? $decrypted : null;
	}

	/**
	 * 公钥解密
	 * 
	 * @param 	string 	$encrypted 	需要解密的数据
	 * 
	 * @return null
	 */
	public static function publicDecrypt($encrypted = '')
	{
		if (!is_string($encrypted)) {
			return null;
		}
		return (openssl_public_decrypt(base64_decode($encrypted), $decrypted, self::getPublicKey())) ? $decrypted : null;
	}
	
}