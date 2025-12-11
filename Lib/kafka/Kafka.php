<?php

include_once 'vendor/autoload.php';

/**
 * 简单封装了kafka发布与订阅
 * 接入说明，请参考：
 * http://git.tsingzone.com/public-server/develop-document/tree/master/%E6%95%B0%E6%8D%AE%E4%B8%AD%E5%8F%B0/%E6%B6%88%E6%81%AF%E6%80%BB%E7%BA%BF%E7%9B%B8%E5%85%B3%E6%96%87%E6%A1%A3
 *
 * Class Kafka
 */
class Kafka
{
    protected $conf = [
        'server'  => '39.106.73.19:10020',
        'topic'   => ['kjs_message_bus'],
        'groupId' => 'test',
        'offset'  => 'latest', // earliest
    ];

    public function setConf($conf)
    {
        $this->conf = array_merge($this->conf, array_intersect_key($conf, $this->conf));
    }

    /**
     * 发布
     *
     * @param $data
     * @return bool
     */
    public function publish($data)
    {
        // 规定的格式
        $_data = [
            'source'    => 'test',
            'channel'   => 'test',
            'channelId' => -1,
            'sendTime'  => time() * 1000,
            'data'      => []
        ];
        $data  = array_merge($_data, array_intersect_key($data, $_data));

        // kafka配置
        $config = \Kafka\ProducerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList($this->conf['server']);
        //$config->setBrokerVersion('0.9.0.1');
        $config->setRequiredAck(1);
        $config->setIsAsyn(false);
        $config->setProduceInterval(500);
        $producer = new \Kafka\Producer();
        // 同步调用
        $result = $producer->send([
            [
                'topic' => $this->conf['topic'][0],
                'value' => json_encode($data),
                'key'   => ''
            ]
        ]);

        if (isset($result[0]['data'][0]['partitions'][0]['errorCode'])
            && $result[0]['data'][0]['partitions'][0]['errorCode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 订阅
     *
     * @param $callback
     */
    public function subscription(\Closure $func)
    {
        // kafka 消息者配置
        $config = \Kafka\ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList($this->conf['server']);
        $config->setGroupId($this->conf['groupId']);
        //$config->setBrokerVersion('0.9.0.1');
        $config->setTopics($this->conf['topic']);
        $config->setOffsetReset($this->conf['offset']); // latest

        $consumer = new \Kafka\Consumer();
        return $consumer->start($func);
    }
}