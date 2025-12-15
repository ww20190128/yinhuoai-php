<?php
namespace service\reuse;
// 加载相关文件
loadFile(array('AliyunOSS'), LIB_PATH . 'aliyun-oss' . DS);    // 加载aliyun-oss第三方库包
use JohnLui\AliyunOSS;
// use Exception;
// use DateTime;
class OSS extends \service\ServiceBase
{
	/**
	 * 单例
	 *
	 * @var object
	 */
	private static $instance;
	
	/**
	 * 单例模式
	 *
	 * @return OSS
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new OSS();
		}
		return self::$instance;
	}
	

  	/* 城市名称：
   	*  
   	*  经典网络下可选：杭州、上海、青岛、北京、张家口、深圳、香港、硅谷、弗吉尼亚、新加坡、悉尼、日本、法兰克福、迪拜
   	*  VPC 网络下可选：杭州、上海、青岛、北京、张家口、深圳、硅谷、弗吉尼亚、新加坡、悉尼、日本、法兰克福、迪拜
   	*/    
	private $city = '北京';

  	// 经典网络 or VPC
  	private $networkType = '经典网络';
  
  	private $AccessKeyId = '';
  	private $AccessKeySecret = '';
  	private $ossClient;

  /**
   * 初始化
   * 
   * @return
   */
  public function init($accessKeyId, $accessKeySecret, $isInternal = false)
  {
  	if ($this->networkType == 'VPC' && !$isInternal) {
  		throw new $this->exception('VPC 网络下不提供外网上传、下载等功能');
  	}
  	$this->AccessKeyId = $accessKeyId;
    $this->AccessKeySecret = $accessKeySecret;
	$this->ossClient = AliyunOSS::boot(
		$this->city,
	   	$this->networkType,
	   	$isInternal,
	   	$accessKeyId,
	    $accessKeySecret
	);
  }


  /**
   * 使用外网上传文件
   * @param  string bucket名称
   * @param  string 上传之后的 OSS object 名称
   * @param  string 删除文件路径
   * @return boolean 上传是否成功
   */
  public static function publicUpload($bucketName, $ossKey, $filePath, $options = [])
  {
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->uploadFile($ossKey, $filePath, $options);
  }

  /**
   * 使用阿里云内网上传文件
   * @param  string bucket名称
   * @param  string 上传之后的 OSS object 名称
   * @param  string 删除文件路径
   * @return boolean 上传是否成功
   */
  public static function privateUpload($bucketName, $ossKey, $filePath, $options = [])
  {
  	// $isInternal == true
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->uploadFile($ossKey, $filePath, $options);
  }


  /**
   * 使用外网直接上传变量内容
   * @param  string bucket名称
   * @param  string 上传之后的 OSS object 名称
   * @param  string 删除传的变量
   * @return boolean 上传是否成功
   */
  public static function publicUploadContent($bucketName, $ossKey, $content, $options = [])
  {
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->uploadContent($ossKey, $content, $options);
  }

  /**
   * 使用阿里云内网直接上传变量内容
   * @param  string bucket名称
   * @param  string 上传之后的 OSS object 名称
   * @param  string 删除传的变量
   * @return boolean 上传是否成功
   */
  public static function privateUploadContent($bucketName, $ossKey, $content, $options = [])
  {
  	// $isInternal == true
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->uploadContent($ossKey, $content, $options);
  }


  /**
   * 使用外网删除文件
   * @param  string bucket名称
   * @param  string 目标 OSS object 名称
   * @return boolean 删除是否成功
   */
  public static function publicDeleteObject($bucketName, $ossKey)
  {
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->deleteObject($bucketName, $ossKey);
  }

  /**
   * 使用阿里云内网删除文件
   * @param  string bucket名称
   * @param  string 目标 OSS object 名称
   * @return boolean 删除是否成功
   */
  public static function privateDeleteObject($bucketName, $ossKey)
  {
    // $isInternal == true
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->deleteObject($bucketName, $ossKey);
  }


  /**
   * -------------------------------------------------
   *
   * 
   *  下面不再分公网内网出 API，也不注释了，大家自行体会吧。。。
   *
   * 
   * -------------------------------------------------
   */

  public function copyObject($sourceBuckt, $sourceKey, $destBucket, $destKey)
  {
    $oss = self::$instance;
    return $oss->ossClient->copyObject($sourceBuckt, $sourceKey, $destBucket, $destKey);
  }

  public function moveObject($sourceBuckt, $sourceKey, $destBucket, $destKey)
  {
    $oss = self::$instance;
    return $oss->ossClient->moveObject($sourceBuckt, $sourceKey, $destBucket, $destKey);
  }

  // 获取公开文件的 URL
  public static function getPublicObjectURL($bucketName, $ossKey)
  {
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->getPublicUrl($ossKey);
  }
  // 获取私有文件的URL，并设定过期时间，如 \DateTime('+1 day')
  public static function getPrivateObjectURLWithExpireTime($bucketName, $ossKey, DateTime $expire_time)
  {
    $oss = self::$instance;
    $oss->ossClient->setBucket($bucketName);
    return $oss->ossClient->getUrl($ossKey, $expire_time);
  }

  public static function createBucket($bucketName)
  {
    $oss = self::$instance;
    return $oss->ossClient->createBucket($bucketName);
  }

  public static function getAllObjectKey($bucketName)
  {
    $oss = self::$instance;
    return $oss->ossClient->getAllObjectKey($bucketName);
  }

  public static function getObjectMeta($bucketName, $ossKey)
  {
    $oss = self::$instance;
    return $oss->ossClient->getObjectMeta($bucketName, $ossKey);
  }

}