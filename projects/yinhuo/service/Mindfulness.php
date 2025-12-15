<?php
namespace service;

// require_once('vendor/autoload.php');

// require_once('vendor/getID3/getid3/getid3.php');

/**
 * 正念 逻辑类
 *
 * @author
*/
class Mindfulness extends ServiceBase
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
	 * @return Mindfulness
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new Mindfulness();
		}
		return self::$instance;
	}
	
	/**
	 * 获取分类列表
	 *
	 * @return array
	 */
	private function getClassifyList()
	{
		$mindfulnessClassifyDao = \dao\MindfulnessClassify::singleton();
		$mindfulnessClassifyEttList = $mindfulnessClassifyDao->readListByIndex(array(
			'status' => 0,
		));
		$commonSv = \service\Common::singleton();
		$modelList = array(array(
	    	'id'    	=> 0,
	    	'name'      => '全部',
	       	'index'     => 0,
	      	'iconImg'   => $commonSv::formartImgUrl('mindfulness-icon-all.png', 'mindfulness/icon'),
        ));
		if (is_iteratable($mindfulnessClassifyEttList)) foreach ($mindfulnessClassifyEttList as $mindfulnessClassifyEtt) {
			$modelList[] = array(
	            'id'            => intval($mindfulnessClassifyEtt->id),
	            'name'          => $mindfulnessClassifyEtt->name,
	            'status'        => $mindfulnessClassifyEtt->status,
	            'index'         => intval($mindfulnessClassifyEtt->index),
	            'iconImg'  		=> $commonSv::formartImgUrl($mindfulnessClassifyEtt->icon, 'mindfulness/icon'),
	            'createTime'    => intval($mindfulnessClassifyEtt->createTime),
        	);
		}
		uasort($modelList, array($commonSv, 'sortByIndex'));
		return $modelList;
	}
					
	/**
	 * 首页信息
	 *
	 * @return array
	 */
	public function info($userId)
	{
		$sentenceDao = \dao\Sentence::singleton();
    	$sentenceEttList = $sentenceDao->readListByWhere();
    	$randKey = array_rand($sentenceEttList, 1);
    	
    	

// $randKey = 79;
    	$sentenceEtt = $sentenceEttList[$randKey];
    	$imgNum = rand(1, 22);
    	
    	$commonSv = \service\Common::singleton();
    	$img = $commonSv::formartImgUrl($imgNum . '.jpg', 'sentence');
    	$sentence = array(
			'text' => $sentenceEtt->text,
			'source' => $sentenceEtt->source,
			'img' => $img,
		);
    	$mindfulnessList = $this->getListByClassify($userId);
    	$classifyList = $this->getClassifyList();
		return array(
			'classifyList' => $classifyList,
			'sentence' => $sentence,
			'list' => array_values($mindfulnessList),
		);
	}
	
	/**
	 * 创建音频
	 *
	 * @return array
	 */
	public function makeAudio()
	{
		$charPlayTime = 300;
		$mindfulnessAudioDao = \dao\MindfulnessAudio::singleton();
		$mindfulnessAudioEttList = $mindfulnessAudioDao->readListByWhere();
		$mindfulnessDao = \dao\Mindfulness::singleton();
		$mindfulnessEttList = $mindfulnessDao->readListByIndex(array(
			'status' => 0,
		));
		$mindfulnessEttList = $mindfulnessDao->refactorListByKey($mindfulnessEttList);
		$dir = '/data/www/resources/mindfulness/audio/';
		
		$getID3 = new \getID3();
		// 		$getID3->analyze($file);
		$url = "/data/www/resources/mindfulness/audio/改善亲子沟通/平静情绪.mp3";
		
		foreach ($mindfulnessAudioEttList as $mindfulnessAudioEtt) {
			$mindfulnessEtt = $mindfulnessEttList[$mindfulnessAudioEtt->mindfulnessId];
			$file = $dir . $mindfulnessEtt->name . DS . $mindfulnessAudioEtt->name . '.mp3';
			if (is_file($file)) {
// 				continue;
			}
			$fileInfo = $getID3->analyze($file); // playtime_string
			
		
			$playtime_string = $fileInfo['playtime_string'];
			$playtime_seconds = ceil($fileInfo['playtime_seconds']);
			$mindfulnessAudioEtt->set('time', $playtime_seconds);
			$mindfulnessAudioDao->update($mindfulnessAudioEtt);
	
// 			echo $file . "\t" . $playtime_string . "\t" . $playtime_seconds . "\n";continue;
			
    		// 将文本按行分割成数组
    		$lines = explode('\r\n', $mindfulnessAudioEtt->lyric);
    		$contentMap = array();
    		$index = 1;
    		foreach ($lines as $line) {
    			if (preg_match('/^\[(\d{2}:\d{2}\.\d{3})\](.*)$/', $line, $matches)) {
	    			$startTime = $matches[1]; // 开始时间
	    			$content = $matches[2]; // 内容
	    			list($minutes, $seconds) = explode(':', $startTime);
	    			list($seconds, $milliseconds) = explode('.', $seconds);
	    			$startTimeMs = ((int)$minutes * 60 + (int)$seconds) * 1000 + (int)$milliseconds;
	    			$contentMap[$index++] = array(
	    				'startTime' => $startTimeMs,
	    				'content' => $content,
	    			);
	    		}
    		}
  
	   		$newMap = array();
	    	foreach ($contentMap as $index => $row) {
	    		$newText = $row['content'];
	    		$newText = str_replace(array('TA', 'ta', '欢迎来到心潮', '这里是心潮', '欢迎回到心潮', '欢迎进入心潮', '心潮'), 
	    			array('他', '他', '欢迎来到智乐心理。#!500ms#了解更真实的自己。#!2000ms#', '这里是智乐心理', '欢迎回到智乐心理', '欢迎进入智乐心理', '智乐心理'), $newText);
	    		if (!empty($contentMap[$index+1]['startTime'])) { // 最后一段
	    			$startTime = $row['startTime'];
	    			$endTime = $contentMap[$index+1]['startTime'];
	    			$diffMs = $endTime - $startTime;
	    			$contentLength = mb_strlen($row['content']);
	    			$pauseTime = $diffMs - ($charPlayTime * $contentLength); // 停顿时长
	    			if ($pauseTime <= 800) {
	    				$pauseTime = 800;
	    			} elseif ($pauseTime >= 4000) {
	    				$pauseTime = 4000;
	    			}
	//     			$pauseTime = 1000;
	    			$newText.= "。#!{$pauseTime}ms#";
	    		}
	    		$newMap[] = $newText;
	    	}
	    	$lyricText = implode('', $newMap);
	    	
	    	
	    	echo $mindfulnessEtt->name . "\n\n";
	    	echo "\t\t" . $mindfulnessAudioEtt->name . "\n\n";
			echo $lyricText . "\n\n\n";

		}
		exit;
	}
	
	/**
	 * 获取分类列表
	 *
	 * @return array
	 */
	public function getListByClassify($userId, $mindfulnessIds = array(), $mindfulnessClassifyId = 0, $info = array())
	{
		$mindfulnessDao = \dao\Mindfulness::singleton();
		$mindfulnessEttList = array();
		if (!empty($mindfulnessClassifyId)) {
			$mindfulnessClassifyDao = \dao\MindfulnessClassify::singleton();
			$mindfulnessClassifyEtt = $mindfulnessClassifyDao->readByPrimary($mindfulnessClassifyId);
			if (empty($mindfulnessClassifyEtt) || $mindfulnessClassifyEtt->status == \constant\Common::DATA_DELETE) {
				throw new $this->exception('分类已删除');
			}
			$mindfulnessClassifyRelationDao = \dao\MindfulnessClassifyRelation::singleton();
			$mindfulnessClassifyRelationEttList = $mindfulnessClassifyRelationDao->readListByIndex(array(
				'classifyId' => $mindfulnessClassifyId,
			));
			if (empty($mindfulnessClassifyRelationEttList)) {
				return array();
			}
			$mindfulnessIds = array(); // 正念课程ID
			foreach ($mindfulnessClassifyRelationEttList as $mindfulnessClassifyRelationEtt) {
				$mindfulnessIds[] = intval($mindfulnessClassifyRelationEtt->mindfulnessId);
			}
			$mindfulnessEttList = $mindfulnessDao->readListByPrimary($mindfulnessIds);
		} else{ // 获取全部
			$mindfulnessEttList = $mindfulnessDao->readListByIndex(array(
				'status' => 0,
			));
		}
		$mindfulnessEttList = $mindfulnessDao->refactorListByKey($mindfulnessEttList);
		$mindfulnessIds = array_column($mindfulnessEttList, 'id');
		if (empty($mindfulnessIds)) {
			return array();
		}
		$commonSv = \service\Common::singleton();
		$mindfulnessAudioDao = \dao\MindfulnessAudio::singleton();
		$mindfulnessAudioEttList = $mindfulnessAudioDao->readListByWhere('`mindfulnessId` in (' . implode(',', $mindfulnessIds) . ')');
		$audioMapList = array(); // 音频列表
		if (is_iteratable($mindfulnessAudioEttList)) foreach ($mindfulnessAudioEttList as $mindfulnessAudioEtt) {
			$mindfulnessEtt = empty($mindfulnessEttList[$mindfulnessAudioEtt->mindfulnessId]) ? array(): $mindfulnessEttList[$mindfulnessAudioEtt->mindfulnessId];
			if (empty($mindfulnessEtt)) {
				continue;
			}
			$audioMapList[$mindfulnessAudioEtt->mindfulnessId][$mindfulnessAudioEtt->id] = array(
				'id' 			=> intval($mindfulnessAudioEtt->id),
				'name' 			=> $mindfulnessAudioEtt->name,
				'index' 		=> $mindfulnessAudioEtt->index,
				'url' 			=> $commonSv::formartImgUrl($mindfulnessAudioEtt->url, 'mindfulness/audio' . DS . $mindfulnessEtt->name),
				'time' 			=> intval($mindfulnessAudioEtt->time / 60),
				'status' 		=> intval($mindfulnessAudioEtt->status),
				'updateTime' 	=> intval($mindfulnessAudioEtt->updateTime),
				'createTime' 	=> intval($mindfulnessAudioEtt->createTime),
			);
		}
			
		$mindfulnessList = array();
		if (is_iteratable($mindfulnessEttList)) foreach ($mindfulnessEttList as $mindfulnessEtt) {
			$audioList = empty($audioMapList[$mindfulnessEtt->id]) ? array() : $audioMapList[$mindfulnessEtt->id];
			$totalTime = 0; // 总时长
			if (is_iteratable($audioList)) foreach ($audioList as $audio) {
				$totalTime += $audio['time'];
			}
			$coverImg = $commonSv::formartImgUrl($mindfulnessEtt->coverImg, 'mindfulness/cover');
			$mindfulnessList[] = array(
				'id' 			=> intval($mindfulnessEtt->id),
				'name' 			=> $mindfulnessEtt->name,
				'classify'  	=> $mindfulnessEtt->classify, // 所属分类
				'coverImg' 		=> $coverImg,
				'price' 		=> $mindfulnessEtt->price,
				'originalPrice' => $mindfulnessEtt->originalPrice,
				'desc' 			=> $mindfulnessEtt->desc,
				'updateTime' 	=> intval($mindfulnessEtt->updateTime),
				'createTime' 	=> intval($mindfulnessEtt->createTime),
				'audioNum'		=> count($audioList), // 音频数量
				'totalTime'		=> $totalTime, // 总时长
				'tagList'		=> empty($mindfulnessEtt->tagList) ? '' : explode(',', $mindfulnessEtt->tagList),
				
			);
		}
		return $mindfulnessList;
	}

	/**
	 * 获取课程详情
	 *
	 * @return array
	 */
	public function mindfulnessInfo($userId, $mindfulnessId)
	{
		$userInfo = array();
		$vipInfo = array(); // 用户的vip信息
		$userEtt = null;
		$payMindfulnessIds = array();
		if (!empty($userId)) {
			$userDao = \dao\User::singleton();
			$userEtt = $userDao->readByPrimary($userId);
			if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
				throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
			}
			$payMindfulnessIds = empty($userEtt->mindfulnessIds) ? array() : explode(',', $userEtt->mindfulnessIds);
			$userSv = \service\User::singleton();
			$userInfo = $userSv->userInfo($userId);
			$vipInfo = empty($userInfo['userInfo']['vipInfo']) ? array() : $userInfo['userInfo']['vipInfo'];
		}
		
		$mindfulnessDao = \dao\Mindfulness::singleton();
		$mindfulnessEtt = $mindfulnessDao->readByPrimary($mindfulnessId);
		if (empty($mindfulnessEtt) || $mindfulnessEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('课程已删除');
        }
		$mindfulnessAudioDao = \dao\MindfulnessAudio::singleton();
		$mindfulnessAudioEttList = $mindfulnessAudioDao->readListByIndex(array(
			'mindfulnessId' => $mindfulnessId,
		));
		$commonSv = \service\Common::singleton();
		$coverImg = $commonSv::formartImgUrl($mindfulnessEtt->coverImg, 'mindfulness/cover');
		$audioList = array(); // 音频列表
		$totalTime = 0; // 总时长
		$index = 1;
		if (is_iteratable($mindfulnessAudioEttList)) foreach ($mindfulnessAudioEttList as $mindfulnessAudioEtt) {
			$showFreeTag = 0;
			$payStatus = 2; // 锁定
			if ($mindfulnessEtt->price <= 0) { // 免费音频，解锁
				$payStatus = 1;
			}
			if (!empty($vipInfo)) { // 有vip，解锁
				$payStatus = 1;
			}
			// 系列课程，将第一个解锁
			if (count($mindfulnessAudioEttList) > 1 && $index <= 1) {
				$payStatus = 1;
			}
			if (in_array($mindfulnessEtt->id, $payMindfulnessIds)) { // 已购买
				$payStatus = 1;
			}
			if ($payStatus == 1 && $index <= 1) {
				$showFreeTag = 1;
			}
			$audioList[$mindfulnessAudioEtt->id] = array(
				'id' 			=> intval($mindfulnessAudioEtt->id),
				'name' 			=> $mindfulnessAudioEtt->name,
				'artist'		=> empty($mindfulnessAudioEtt->time) ? ' ' : date('H:i:s', $mindfulnessAudioEtt->time),
				'cover'			=> $coverImg,
				'lrc'			=> $mindfulnessAudioEtt->lyric,
				'index' 		=> $index,
				'url' 			=> $commonSv::formartImgUrl($mindfulnessAudioEtt->url, 'mindfulness/audio' . DS . $mindfulnessEtt->name),
				'time' 			=> intval($mindfulnessAudioEtt->time / 60),
				'status' 		=> intval($mindfulnessAudioEtt->status),
				'updateTime' 	=> intval($mindfulnessAudioEtt->updateTime),
				'createTime' 	=> intval($mindfulnessAudioEtt->createTime),
				'payStatus'		=> $payStatus, // 1  已解锁  2 锁定
				'showFreeTag'	=> $showFreeTag, // 是否显示 免费标签 1 显示 0 不显示
			);
			$index++;
			$totalTime += $audioList[$mindfulnessAudioEtt->id]['time'];
		}

		// 是否已收藏
		$collectList = array();
		if (empty($userId)) {
			$collectSv = \service\Collect::singleton();
			$collectResult = empty($userId) ? array() : $collectSv->collectList($userId);
			$collectList = empty($collectResult['mindfulnesss']) ? array() : array_column($collectResult['mindfulnesss'], null, 'id');
		}
		$mindfulnessModel = array(
			'id' 			=> intval($mindfulnessEtt->id),
			'name' 			=> $mindfulnessEtt->name,
			'classify'  	=> $mindfulnessEtt->classify, // 所属分类
			'coverImg' 		=> $coverImg,
			'price' 		=> $mindfulnessEtt->price,
			'originalPrice' => $mindfulnessEtt->originalPrice,
			'desc' 			=> $mindfulnessEtt->desc,
			'tagList'		=> empty($mindfulnessEtt->tagList) ? array() : explode(',', $mindfulnessEtt->tagList),
			'updateTime' 	=> intval($mindfulnessEtt->updateTime),
			'createTime' 	=> intval($mindfulnessEtt->createTime),
			'audioNum'		=> count($audioList), // 音频数量
			'totalTime'		=> $totalTime, // 总时长
			'payStatus'		=> in_array($mindfulnessEtt->id, $payMindfulnessIds) ? 1 : 0,
			'collectStatus' => empty($collectList[$mindfulnessEtt->id]) ? 0 : $collectList[$mindfulnessEtt->id],
		);
		return array(
			'mindfulness' => $mindfulnessModel,
			'audioList' => array_values($audioList),
			'userInfo' => $userInfo,
		);
	}
	
}