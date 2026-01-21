<?php
namespace service;
/**
 * 数据采集
 * 
 * @author
 */
class Snoopy
{
	public  $userName; 					// 用户名
	public  $password; 					// 密码
	public  $proxyHost; 				// 代理主机
	public  $proxyPort; 				// 代理主机端口
	public 	$proxyUserName = '';		// 代理用户名
	public	$proxyPassword = '';		// 代理密码
	public  $fpTimeout = 30; 			// 连接超时时间 (秒)
	public  $error; 					// 报错信息
	public  $httpMethod = 'GET';		// http请求方式
	public  $cookies = array();			// cookies列表
	public  $httpVersion = 'HTTP/1.0';	// http的版本
	public  $agent = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';	// 用户代理伪装 
	public	$otherHeaders	= array();  // 其他头文件
	public  $acceptTypes = 'image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*'; // http 接受类型
	public	$referer = ''; 				// 来路信息
	public  $mimeBoundary = '';			// multipart/form-data的类型分隔线
	public  $readTimeout = 0;			// 读取操作超时 (秒) 设置为0为没有超时 
	public	$maxLineLen	= 4096;			// 行最大长度 (headers)
	public	$maxLength = 500000;		// 最长返回数据长度 (body)
	public  $baseUrl = '';
	public  $decode = '';				// 编码格式 
	public  $maxFrames	= 0;		    // 允许追踪的框架最大数量 ,0 为不限制
	public  $results = '';				// 获取的内容列表
	public  $maxRedirs = 5;				// 最大重定向次数,0=不允许 
	public  $redirectDepth = 0;			// 重定向增量
	public	$redirectAllows	= true;		// 是否允许重定向
	public	$curlPath = "/usr/local/bin/curl";
	public  $expandLinks = true;		// 是否将链接都补全为完整地址 (true)		
	public  $submitType = 'application/x-www-form-urlencoded'; // 默认提交类型
	public  $submitMethod = 'POST';	    // 默认的表单提交类型
	private	$responseCode = '';			// 从服务器返回的响应代码
	private $frameDepth	= 0;			// 当前框架深度  $maxframes 允许追踪的框架最大数量 
	private $frameUrls = array();		// 框架url列表
	private $lastRedirectAddr =	'';		// 最近一次重定向的地址
	private $isReadTimeout = false;     // 如果一次读取操作超时了,返回 true
	private $isProxy = false; 			// 是否使用代理
	private $passcookies = true; 		// 是否用cookie保存密码
	private $redirectAddr = false; 		// 是否重新发送地址
	private	$headers = array(); 		// 头文件列表
	private $status; 					// 抓取的http的状态 
	private $host;	  					// 主机名
	private $port = 80;	  			    // 端口号
	
	/**
	 * 获取文本内容(去掉html代码)
	 * 
	 * @param $uri	uri地址
	 *
     * @return bool | string
     */
	public function fetchText($uri)
    {
		if ($this->fetch($uri)) {					
			if (is_array($this->results)) {
				for ($i = 0; $i < count($this->results); $i++) {
					$this->results[$i] = $this->stripText($this->results[$i]);
				}
			} else {
				$this->results = $this->stripText($this->results);
			}
			return $this->results;
		} else {
			return false;
		}
	}
	
	/**
	 * 抓取网页的内容 
	 * 
	 * @param $uri	uri地址
	 * 
	 * @return bool
	 */
	private function fetch($uri)
    {
		$uriParts = parse_url($uri);
		empty($uriParts['user'])  or  $this->userName = $uriParts['user'];
		empty($uriParts['pass'])  or  $this->password = $uriParts['pass'];
		empty($uriParts['query']) and $uriParts['query'] = null;
		empty($uriParts['path'])  and $uriParts['path'] = null;
        $fp = null;
		switch(strtolower($uriParts['scheme'])) {
			case 'http':
				$this->host = $uriParts['host'];
				empty($uriParts['port']) or $this->port = $uriParts['port'];
                if ($this->connect($fp)) {
					if ($this->isProxy) {				
						$this->httpRequest($uri, $fp, $uri, $this->httpMethod);
					} else {
						$path = $uriParts['path'] . ($uriParts['query'] ? '?' . 
							$uriParts['query'] : '');
						$this->httpRequest($path, $fp, $uri, $this->httpMethod);
					}
					$this->disconnect($fp);
					if ($this->redirectAddr) {
						if ($this->maxRedirs > $this->redirectDepth) {
							if (preg_match('|^http://' . preg_quote($this->host) . '|i',
								$this->redirectAddr) || $this->redirectAllows) {
								$this->redirectDepth++;
								$this->lastRedirectAddr = $this->redirectAddr;
								$this->fetch($this->redirectAddr);
							}
						}
					}
					if ($this->frameDepth < $this->maxFrames && count($this->frameUrls) > 0) {
						$frameUrls = $this->frameUrls;
						$this->frameUrls = array();
						while (list(, $frameUrl) = each($frameUrls)) {
							if ($this->frameDepth < $this->maxFrames) {
								$this->fetch($frameUrl);
								$this->frameDepth++;
							} else {
								break;
							}	
						}
					}					
				} else {
					return false;
				}
				return $this->results;
				break;
			case 'https':
				if (!$this->curlPath) {
					return false;
				}
				if (function_exists("is_executable")) {
					if (!is_executable($this->curlPath)) {
						 return false;
					}
				}   
				$this->host = $uriParts['host'];
				empty($uriParts['port']) or $this->port = $uriParts['port'];
				if ($this->isProxy) {
					$this->httpsRequest($uri, $uri, $this->httpMethod);
				} else {
					$path = $uriParts['path'] . ($uriParts['query']
						? '?' . $uriParts['query'] : '');
					$this->httpsRequest($path, $uri, $this->httpMethod);
				}
				if ($this->redirectAddr) {
					if ($this->maxRedirs > $this->redirectDepth) {
						if (preg_match('|^http://' . preg_quote($this->host) . 
							'|i', $this->redirectAddr) || $this->redirectAllows) {
							$this->redirectDepth++;
							$this->lastRedirectAddr = $this->redirectAddr;
							$this->fetch($this->redirectAddr);
						}
					}
				}
				if ($this->frameDepth < $this->maxFrames 
					&& count($this->frameUrls) > 0) {
					$frameUrls = $this->frameUrls;
					$this->frameUrls = array();
					while (list(, $frameUrl) = each($frameUrls)) {
						if ($this->frameDepth < $this->maxframes) {
							$this->fetch($frameUrl);
							$this->frameDepth++;
						} else {
							break;
						}
					}
				}					
				return true;					
				break;
			default:
				$this->error = 'Invalid protocol "' . isset($uriParts['scheme']) 
					? $uriParts['scheme'] : 'null' . '"\n';
				return false;
				break;
		}		
		return true;
	}
	
	/**
	 *  获取链接 
	 * 
	 * @param $uri	uri地址
	 *
     * @return array|bool
     */
	public function fetchLinks($uri)
    {
		if ($this->fetch($uri)) {			
			if ($this->lastRedirectAddr) {
				$uri = $this->lastRedirectAddr;
			}
			if (is_array($this->results)) {
				for ($i = 0; $i < count($this->results); $i++) {
					$this->results[$i] = $this->stripLinks($this->results[$i]);
				}	
			} else {
				$this->results = $this->stripLinks($this->results);
			}
			if ($this->expandLinks) {
				$this->results = $this->expandLinks($this->results, $uri);	
			}
			return $this->results;
		} else {
			return false;
		}	
	}
	
	/**
	 * 获取表单 
	 * 
	 * @param $uri	uri地址
	 *
     * @return array|bool
     */
	public function fetchForm($uri)
    {
		if ($this->fetch($uri)) {
			if (is_array($this->results)) {
				for ($i = 0; $i<count($this->results); $i++) {
					$this->results[$i] = $this->stripForm($this->results[$i]);
				}	
			} else {
				$this->results = $this->stripForm($this->results);
			}
			return $this->results;
		} else {
			return false;
		}
	}

    /**
     * 提交表单
     *
     * @param       string          $uri        uri地址
     * @param       array|string    $formVars   表单变量
     * @param       array|string    $formFiles  表单内容
     *
     * @return bool
     */
	public function submit($uri, $formVars = '', $formFiles = '')
    {
		unset($postData);
		$postData = $this->preparePostBody($formVars, $formFiles);
		$uriParts = parse_url($uri);
		empty($uriParts['user'])  or  $this->userName = $uriParts['user'];
		empty($uriParts['pass'])  or  $this->password = $uriParts['pass'];
		empty($uriParts['query']) and $uriParts['query'] = '';
		empty($uriParts['path'])  and $uriParts['path']  = '';
        $fp = null;
		switch(strtolower($uriParts['scheme'])) {
			case 'http':
				$this->host = $uriParts['host'];
				empty($uriParts['port']) or $this->port = $uriParts['port'];
				if ($this->connect($fp)) {
					if ($this->isProxy) {
						$this->httpRequest($uri, $fp, $uri, 
							$this->submitMethod, $this->submitType, $postData);
					} else {
						$path = $uriParts['path'] . ($uriParts['query'] ? 
							'?' . $uriParts['query'] : '');
						$this->httpRequest($path, $fp, $uri, $this->submitMethod, 
							$this->submitType, $postData);
					}
					$this->disconnect($fp);
					if ($this->redirectAddr) {
						if ($this->maxRedirs > $this->redirectDepth) {						
							if (!preg_match('|^' . $uriParts['scheme'] . '://|', $this->redirectAddr))
								$this->redirectAddr = $this->expandLinks($this->redirectAddr, 
									$uriParts['scheme'] . '://' . $uriParts['host']);
							if (preg_match('|^http://' . preg_quote($this->host) . '|i', 
								$this->redirectAddr) || $this->redirectAllows) {
								$this->redirectDepth++;
								$this->lastRedirectAddr = $this->redirectAddr;
								if (strpos( $this->redirectAddr, '?' ) > 0) {
									$this->fetch($this->redirectAddr);	
								} else {
									$this->submit($this->redirectAddr, $formVars, $formFiles);
								}
							}
						}
					}
					if ($this->frameDepth < $this->maxFrames && count($this->frameUrls) > 0) {
						$frameUrls = $this->frameUrls;
						$this->frameUrls = array();
						while(list(, $frameUrl) = each($frameUrls)) {														
							if ($this->frameDepth < $this->maxFrames) {
								$this->fetch($frameUrl);
								$this->frameDepth++;
							} else {
								break;
							}
						}
					}
				} else {
					return false;
				}
				return true;					
				break;
			case 'https':
				if (!$this->curlPath) {
					return false;
				}
				if (function_exists("is_executable")) {
					if (!is_executable($this->curlPath)) {
						return false;
					}
				}
				$this->host = $uriParts['host'];
				empty($uriParts['port']) or $this->port = $uriParts['port'];					
				if ($this->isProxy) {
					$this->httpsRequest($uri, $uri, $this->submitMethod, 
					$this->submitType, $postData);
				} else {
					$path = $uriParts['path'] . ($uriParts['query'] 
						? '?' . $uriParts['query'] : '');
					$this->httpsRequest($path, $uri, $this->submitMethod, $this->submitType, $postData);
				}
				if ($this->redirectAddr) {
					if($this->maxRedirs > $this->redirectDepth) {						
						if (!preg_match('|^' . $uriParts['scheme'] . '://|', $this->redirectAddr)) {
							$this->redirectAddr = $this->expandLinks($this->redirectAddr,
								$uriParts['scheme'] . '://' . $uriParts['host']);	
						}
						if (preg_match('|^http://' . preg_quote($this->host) . '|i',
							$this->redirectAddr) || $this->redirectAllows) {
							$this->redirectDepth++;
							$this->lastRedirectAddr = $this->redirectAddr;
							if (strpos( $this->redirectAddr, '?') > 0) {
								$this->fetch($this->redirectAddr);
							} else {
								$this->submit($this->redirectAddr, $formVars, $formFiles);
							}
						}
					}
				}
				if ($this->frameDepth < $this->maxFrames && count($this->frameUrls) > 0) {
					$frameUrls = $this->frameUrls;
					$this->frameUrls = array();
					while(list(, $frameUrl) = each($frameUrls)) {														
						if ($this->frameDepth < $this->maxFrames) {
							$this->fetch($frameUrl);
							$this->frameDepth++;
						} else {
							break;
						}	
					}
				}					
				return true;					
				break;
			default:
				$this->error = 'Invalid protocol "' . $uriParts['scheme'] . '"\n';
				return false;
				break;
		}		
		return true;
	}
	
	/**
	 * 提交后只返回 去除html的 文本 
	 * 
	 * @param 	string	$uri		uri地址
	 * @param	array	$formVars	表单变量
	 * @param	array	$formFiles	表单内容
	 *
     * @return bool
     */
	public function submitText($uri, $formVars = '', $formFiles = '')
    {
		if ($this->submit($uri, $formVars, $formFiles)) {			
			if ($this->lastRedirectAddr) {
				$uri = $this->lastRedirectAddr;
			}
			if (is_array($this->results)) {
				for ($i=0; $i < count($this->results); $i++) {
					$this->results[$i] = $this->_striptext($this->results[$i]);
					if ($this->expandLinks) {
						$this->results[$i] = $this->expandLinks($this->results[$i], $uri);
					}	
				}
			} else {
				$this->results = $this->stripText($this->results);
				if ($this->expandLinks)
					$this->results = $this->expandLinks($this->results, $uri);
			}
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 提交后只返回 链接 
	 * 
	 * @param 	string	$uri		uri地址
	 * @param	array	$formVars	表单变量
	 * @param	array	$formFiles	表单内容
	 * 
	 * $return bool
	 */
	public function submitLinks($uri, $formVars = '', $formFiles = '') {
		if ($this->submit($uri, $formVars, $formFiles)) {			
			if ($this->lastRedirectAddr) {
				$uri = $this->lastRedirectAddr;
			}	
			if (is_array($this->results)) {
				for ($i = 0; $i < count($this->results); $i++) {
					$this->results[$i] = $this->stripLinks($this->results[$i]);
					if ($this->expandLinks) {
						$this->results[$i] = $this->expandLinks($this->results[$i], $uri);
					}	
				}
			} else {
				$this->results = $this->stripLinks($this->results);
				if ($this->expandLinks) {
					$this->results = $this->expandLinks($this->results, $uri);
				}
			}
			return true;
		} else {
			return false;
		}
	}

    /**
     * http请求
     *
     * @param   string                  $url                url地址
     * @param   resource                $fp                 连接句柄
     * @param   string                  $uri                uri地址
     * @param   string                  $httpMethod         请求方式
     * @param   string                  $contentType        内容类型
     * @param \service\strint|string    $body               内容
     *
     * @return  bool
     */
	private function httpRequest($url, $fp, $uri, $httpMethod,
								$contentType = '', $body = '')
    {
		if ($this->passcookies && $this->redirectAddr) {
			$this->setCookies();
		}
		$uriParts = parse_url($uri);
		if (empty($url)) {
			$url = '/';
		}
		$headers = $httpMethod .' ' . $url . ' ' . $this->httpVersion . "\r\n";
		empty($this->agent) or $headers .= 'User-Agent: ' . $this->agent . "\r\n";	
		if (!empty($this->host) && !isset($this->otherHeaders['Host'])) {
			$headers .= 'Host: ' . $this->host;
			empty($this->port) or $headers .= ':' . $this->port;		
			$headers .= "\r\n";
		}
		empty($this->acceptTypes) or $headers .= 'Accept: ' . $this->acceptTypes . "\r\n";
		empty($this->referer) or $headers .= 'Referer: ' . $this->referer . "\r\n";
		$cookieHeaders = null;
		if (!empty($this->cookies)) {
			is_array($this->cookies) or $this->cookies = (array)$this->cookies;		
			reset($this->cookies);
			if (count($this->cookies) > 0) {
				$cookieHeaders .= 'Cookie: ';
				foreach ($this->cookies as $cookieKey => $cookieVal) {
					$cookieHeaders .= $cookieKey . '=' . urlencode($cookieVal) . '; ';
				}
				$headers .= substr($cookieHeaders, 0, -2) . "\r\n";
			}
		}	
		if (!empty($this->otherHeaders)) {
			is_array($this->otherHeaders) or $this->otherHeaders = (array)$this->otherHeaders;			
			while(list($headerKey, $headerVal) = each($this->otherHeaders))
				$headers .= $headerKey . ': ' . $headerVal . "\r\n";
		}	
		if (!empty($contentType)) {
			$headers .= 'Content-type: ' . $contentType;
			$contentType != 'multipart/form-data' or $headers .= '; boundary=' . $this->mimeBoundary;
			$headers .= "\r\n";
		}
		empty($body) or $headers .= 'Content-length: ' . strlen($body) . "\r\n";
		if (!empty($this->userName) || !empty($this->password))	{
			$headers .= 'Authorization: Basic ' . base64_encode($this->userName . ':' . 
						$this->password)."\r\n";
		}
		empty($this->proxyUserName) or $headers .= 'Proxy-Authorization: ' . 'Basic ' . 
			base64_encode($this->proxyUserName . ':' . $this->proxyPassword) . "\r\n";
		$headers .= "\r\n";
		if ($this->readTimeout > 0) {
			socket_set_timeout($fp, $this->readTimeout, 0);
		}
		$this->isReadTimeout = false;
		fwrite($fp, $headers . $body, strlen($headers . $body));
		$this->redirectAddr = false;
		unset($this->headers);
		while ($currentHeader = fgets($fp, $this->maxLineLen)) {	
			if ($this->readTimeout > 0 && $this->checkTimeout($fp)) {
				$this->status = -100;
				return false;
			}
			if ("\r\n" == $currentHeader) {
				break;
			}
			if (preg_match('/^(Location:|URI:)/i', $currentHeader)) {
				preg_match('/^(Location:|URI:)[ ]+(.*)/i', chop($currentHeader), $matches);
				if (!preg_match('|\:\/\/|',$matches['2'])) {
					$this->redirectAddr = $uriParts['scheme'] . '://'. $this->host . ':' . $this->port;
					if (!preg_match('|^/|', $matches['2'])) {
						$this->redirectAddr .= '/' . $matches['2'];
					} else {
						$this->redirectAddr .= $matches['2'];
					}		
				} else {
					$this->redirectAddr = $matches['2'];
				}	
			}
			if (preg_match('|^HTTP/|', $currentHeader)) {
                if (preg_match('|^HTTP/[^\s]*\s(.*?)\s|', $currentHeader, $status)) {
					$this->status = $status['1'];
                }				
				$this->responseCode = $currentHeader;
			}	
			$this->headers[] = $currentHeader;
		}
		$results = ''; //源码源代码
		do {
    		$data = fread($fp, $this->maxLength);
    		if (0 == strlen($data)) {
        		break;
    		}
    		$results .= $data;
		} while(true);
		if ($this->readTimeout > 0 && $this->checkTimeout($fp)) {
			$this->status = -100;
			return false;
		}
		if (preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",
			$results, $match)) {
			$this->redirectAddr = $this->expandLinks($match['1'], $uri);	
		}
		preg_match('/<meta.+?charset=([-\w]+)/i', $results, $arr);
		if (isset($arr['1'])) {
			$this->decode = $arr['1'];
		} else {
			$encoded = mb_detect_encoding(substr($results, 0, 500),
				'EUC-JP, JIS, EUC-CN, ISO-2022-JP, GB2312, BIG5, CP936, UTF-8');
			if ($encoded === false) {
				$this->decode = 'UTF-8';
			} else {
				$this->decode = $encoded;
			}
		}
		$this->baseUrl = '';
		preg_match('/<base\s*href\s*=\s*[\"\'](.*?)[\"\'].*?>/i', $results, $baseUrl);
		isset($baseUrl['1']) and $this->baseUrl = trim($baseUrl['1'], '/');
		if (($this->frameDepth < $this->maxFrames) 
			&& preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i", $results, $match)) {
			$this->results[] = $results;
			for ($i = 0; $i<count($match['1']); $i++) {
				$this->frameUrls[] = $this->expandLinks($match['1'][$i], 
														$uriParts['scheme'] . '://' . $this->host);
			}
		} elseif(is_array($this->results)) {
			$this->results[] = $results;
		} else {
			$this->results = $results;
		}
		return true;
	}

    /**
     * http请求
     *
     * @param string $url            url地址
     * @param string $uri            uri地址
     * @param string $httpMethod        请求方式
     * @param string $contentType    内容类型
     * @param \service\strint|string $body 内容
     *
     * @internal param resource $fp 连接句柄
     * @return  bool
     */
	private function httpsRequest($url, $uri, $httpMethod, $contentType = '', $body = '') {  
		if ($this->passcookies && $this->redirectAddr) {
			$this->setCookies();
		}
		$headers = array();			
		$uriParts = parse_url($uri);
		if (empty($url)) {
			$url = '/';
		}
		empty($this->agent) or $headers[] = 'User-Agent: ' . $this->agent;
		if (!empty($this->host)) {
			if (!empty($this->port)) {
				$headers[] = 'Host: ' . $this->host . ':' . $this->port;
			} else {
				$headers[] = 'Host: '.$this->host;	
			}
		}
		empty($this->acceptTypes) or $headers[] = 'Accept: ' . $this->acceptTypes;
		empty($this->referer) or $headers[] = 'Referer: ' . $this->referer;
		if (!empty($this->cookies)) {
			is_array($this->cookies) or $this->cookies = (array)$this->cookies;
			reset($this->cookies);
			if (count($this->cookies) > 0 ) {
				$cookieStr = 'Cookie: ';
				foreach ($this->cookies as $cookieKey => $cookieVal ) {
					$cookieStr .= $cookieKey . '=' . urlencode($cookieVal) . '; ';
				}
				$headers[] = substr($cookieStr, 0, -2);
			}
		}
		if (!empty($this->otherHeaders)) {
			is_array($this->otherHeaders) 
				or $this->otherHeaders = (array)$this->otherHeaders;	
			while(list($headerKey, $headerVal) = each($this->otherHeaders)) {
				$headers[] = $headerKey . ': ' . $headerVal;
			}
		}
		if (!empty($contentType)) {
			if ($contentType == 'multipart/form-data') {
				$headers[] = "Content-type: $contentType; boundary=" . $this->mimeBoundary;
			} else {
				$headers[] = "Content-type: $contentType";
			}	
		}
		empty($body) or $headers[] = 'Content-length: ' . strlen($body);
		if (!empty($this->userName) || !empty($this->password)) {
			$headers[] = 'Authorization: BASIC ' . 
				base64_encode($this->userName . ':' . $this->password);
		}	
		for ($currHeader = 0; $currHeader < count($headers); $currHeader++) {
			$saferHeader = strtr($headers[$currHeader], "\"", " ");
			$cmdLineParams .= " -H \"".$saferHeader."\"";
		}
		empty($body) or $cmdLineParams .= " -d \"$body\"";
		$this->readTimeout > 0 and $cmdLineParams .= ' -m ' . $this->readTimeout;
		$headerFile = tempnam($temp_dir, "sno");
		exec($this->curlPath." -k -D \"$headerFile\"".$cmdLineParams." \"".escapeshellcmd($uri)."\"", 
			$results, $return);
		if ($return) {
			$this->error = "Error: cURL could not retrieve the document, error $return .";
			return false;
		}
		$results = implode("\r\n", $results);
		$resultHeaders = file("$headerFile");
		$this->redirectAddr = false;
		unset($this->headers);
		for ($currentHeader = 0; $currentHeader < count($resultHeaders); $currentHeader++) {
			if (preg_match('/^(Location: |URI: )/i', $resultHeaders[$currentHeader])) {
				preg_match('/^(Location: |URI:)\s+(.*)/', 
					chop($resultHeaders[$currentHeader]),$matches);
				if (!preg_match('|\:\/\/|', $matches['2'])) {
					$this->redirectAddr = $uriParts['scheme'] . '://'.
						$this->host . ':' . $this->port;
					if (!preg_match('|^/|', $matches['2'])) {
						$this->redirectAddr .= '/' . $matches['2'];
					} else {
						$this->redirectAddr .= $matches['2'];
					}
				} else {
					$this->redirectAddr = $matches['2'];
				}
			}
			if (preg_match('|^HTTP/|', $resultHeaders[$currentHeader])) {
				$this->responseCode = $resultHeaders[$currentHeader];
			}
			$this->headers[] = $resultHeaders[$currentHeader];
		}
		if (preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",
			$results, $match)) {
			$this->redirectAddr = $this->expandLinks($match['1'], $uri);	
		}
		if (($this->frameDepth < $this->maxFrames) 
			&& preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i", $results, $match)) {
			$this->results[] = $results;
			for ($i=0; $i<count($match[1]); $i++) {
				$this->frameUrls[] = $this->expandLinks($match['1'][$i], 
					$uriParts['scheme'] . '://' . $this->host);
			}
		} elseif(is_array($this->results)) {
			$this->results[] = $results;
		} else {
			$this->results = $results;
		}
		unlink("$headerFile");
		return true;
	}
	
	/**
	 * 连接
	 * 
	 * @param   resource    $fp	    句柄
	 * 
	 * @return bool
	 */
	private function connect(&$fp)
    {
		if (!empty($this->proxyHost) && !empty($this->proxyPort)) {
			$host = $this->proxyHost;
			$port = $this->proxyPort;
			$this->isProxy = true;
		} else {
			$host = $this->host;
			$port = $this->port;
		}
		$this->status = 0;
		if ($fp = fsockopen($host, $port, $errno, $errstr, $this->fpTimeout)) {
			return true;
		} else {
			$this->status = $errno;
			switch ($errno) {
				case -3:
					$this->error = "socket creation failed (-3)";
				case -4:
					$this->error = "dns lookup failure (-4)";
				case -5:
					$this->error = "connection refused or timed out (-5)";
				default:
					$this->error = "connection failed (" . $errno . ")";
			}
			return false;
		}
	}
	
	/**
	 * 断开连接
	 * 
	 * @param resource $fp	句柄
	 * 
	 * @return bool
	 */
	private function disconnect($fp) {
		return fclose($fp);
	}
	
	/**
	 * 设置cookies
	 * 
	 * @return void
	 */
	private function setCookies()
    {
		for ($i = 0; $i < count($this->headers); $i++) {
			if (preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $this->headers[$i], $match)) {
				$this->cookies[$match['1']] = urldecode($match['2']);
			}	
		}
		return ;
	}
	
	/**
	 * 检查是否超时
	 * 
	 * @param resource  $fp	sock句柄
	 * 
	 * @return bool
	 */
	private function checkTimeout($fp)
    {
		if ($this->readTimeout > 0) {
			$fpStatus = socket_get_status($fp);
			if ($fpStatus['timed_out']) {
				$this->isReadTimeout = true;
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 展开链接
	 * 
	 * @param 	array	 	$links	链接列表
	 * @param 	string	 	$uri	uri地址
	 * 
	 * @return array
	 */
	private function expandLinks($links, $uri)
    {
		preg_match('/^[^\?]+/', $uri, $match);
		$match = preg_replace('|/[^\/\.]+\.[^\/\.]+$|', '', $match['0']);
		$match = preg_replace('|/$|', '', $match);
		$matchPart = parse_url($match);
		$matchRoot = $matchPart['scheme'] . '://' . $matchPart['host'];
		$this->baseUrl != '' and $match = $this->baseUrl;
		$search = array('|^http://' . preg_quote($this->host) . '|i',
						'|^(\/)|i',
						'|^(?!http://)(?!mailto:)|i',
						'|/\./|',
						'|/[^\/]+/\.\./|');
		$replace = array('', $matchRoot . '/', $match . '/', '/', '/');
		return preg_replace($search, $replace, $links);
	}
	
	/**
	 * 处理内容
	 * 
	 * @param array $document 内容
	 * 
	 * @return array
	 */
	private function stripText($document)
    {
		$search = array("'<script[^>]*?>.*?</script>'si",	// strip out javascript
						"'<[\/\!]*?[^<>]*?>'si",			// strip out html tags
						"'([\r\n])[\s]+'",					// strip out white space
						"'&(quot|#34|#034|#x22);'i",		// replace html entities
						"'&(amp|#38|#038|#x26);'i",			// added hexadecimal values
						"'&(lt|#60|#060|#x3c);'i",
						"'&(gt|#62|#062|#x3e);'i",
						"'&(nbsp|#160|#xa0);'i",
						"'&(iexcl|#161);'i",
						"'&(cent|#162);'i",
						"'&(pound|#163);'i",
						"'&(copy|#169);'i",
						"'&(reg|#174);'i",
						"'&(deg|#176);'i",
						"'&(#39|#039|#x27);'",
						"'&(euro|#8364);'i",				// europe
						"'&a(uml|UML);'",					// german
						"'&o(uml|UML);'",
						"'&u(uml|UML);'",
						"'&A(uml|UML);'",
						"'&O(uml|UML);'",
						"'&U(uml|UML);'",
						"'&szlig;'i",
						);
		$replace = array(	"",
							"",
							"\\1",
							"\"",
							"&",
							"<",
							">",
							" ",
							chr(161),
							chr(162),
							chr(163),
							chr(169),
							chr(174),
							chr(176),
							chr(39),
							chr(128),
							"ä",
							"ö",
							"ü",
							"Ä",
							"Ö",
							"Ü",
							"ß",
						);
		return preg_replace($search, $replace, $document);
	}
	
	/**
	 * 处理连接
	 * 
	 * @param array $document 内容
	 * 
	 * @return array
	 */
	private function stripLinks($document)
    {
		preg_match_all("'<\s*a\s.*?href\s*=\s*
						([\"\'])?
						(?(1) (.*?)\\1 | ([^\s\>]+))
						'isx", $document, $links);
		while(list($key, $val) = each($links['2'])) {
			empty($val) or $match[] = $val;
		}
		while(list($key, $val) = each($links['3'])) {
			empty($val) or $match[] = $val;	
		}
		return $match;
	}
	
	/**
	 * 处理表单
	 * 
	 * @param array $document 内容
	 * 
	 * @return array
	 */
	private function stripForm($document)
    {
		preg_match_all("'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi",
		$document, $elements);
		return implode("\r\n", $elements['0']);
	}
	
	/**
	 * 处理提交的内容
	 * 
	 * @param	array	$formVars	表单变量
	 * @param	array	$formFiles	表单内容
	 * 
	 * @return array
	 */
	private function preparePostBody($formVars, $formFiles)
    {
		settype($formVars, 'array');
		settype($formFiles, 'array');
		$postData = '';
		if (0 == count($formVars) && 0 == count($formFiles)) {
			return;
		}	
		switch ($this->submitType) {
			case 'application/x-www-form-urlencoded':
				reset($formVars);
				while(list($key, $val) = each($formVars)) {
					if (is_array($val) || is_object($val)) {
						while (list($curKey, $curVal) = each($val)) {
							$postData .= urlencode($key) . '[]=' . urlencode($curVal) . '&';
						}
					} else
						$postData .= urlencode($key) . '=' . urlencode($val) . '&';
				}				
				break;
			case 'multipart/form-data':
				$this->mimeBoundary = 'Snoopy' . md5(uniqid(microtime()));
				reset($formVars);
				while(list($key, $val) = each($formVars)) {
					if (is_array($val) || is_object($val)) {
						while (list($curKey, $curVal) = each($val)) {
							$postData .= '--' . $this->mimeBoundary . "\r\n";
							$postData .= "Content-Disposition: form-data; name=\"$key\[\]\"\r\n\r\n";
							$postData .= "$curVal\r\n";
						}
					} else {
						$postData .= '--' . $this->mimeBoundary . "\r\n";
						$postData .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
						$postData .= "$val\r\n";
					}
				}
				reset($formFiles);
				while(list($fieldName, $fileNames) = each($formFiles)) {
					settype($fileNames, 'array');
					while(list(, $fileName) = each($fileNames)) {
						if (!is_readable($fileName)) continue;
						$fp = fopen($fileName, 'r');
						$fileContent = fread($fp, filesize($fileName));
						fclose($fp);
						$baseName = basename($fileName);
						$postData .= '--' . $this->mimeBoundary . "\r\n";
						$postData .= "Content-Disposition: form-data; name=\"$fileName\"; filename=\"$baseName\"\r\n\r\n";
						$postData .= "$fileContent\r\n";
					}
				}
				$postData .= '--' . $this->mimeBoundary . "--\r\n";
				break;
		}
		return $postData;
	}
	
}