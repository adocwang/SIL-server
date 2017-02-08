<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/8/17
 * Time: 00:38
 */

namespace AppBundle\Service;


use GuzzleHttp\Client;

class QiXinApi
{
    private $client;

    private $appKey;

    public function __construct(Client $client, $appKey)
    {
        $this->client = $client;
        $this->appKey = $appKey;
    }

    /**
     * @param $companyName string
     * @return array|null
     */
    public function getGongShangInfo($companyName)
    {
        /**
         * @var \GuzzleHttp\Client $qixinApi
         */
        $url = 'enterprise/getDetailAndContactByName?appkey=' . $this->appKey . '&keyword=' . urlencode($companyName);
        $options = ['connect_timeout' => 5, 'timeout' => 5];
        $response = $this->client->get($url, $options);
        $json = $response->getBody()->getContents();
        $arr = json_decode($json, true);
        if (!empty($arr)) {
            return $arr['data'];
        }
        return [];
    }

    public function getChildCompany($vcCompanyName){
        /**
         * @var \GuzzleHttp\Client $qixinApi
         */
        $url = 'investment/getInvestListByName?appkey=7483ea885c2746449f1db045a411e06c&keyword=' . urlencode($vcCompanyName);
//        echo $url;exit;
        $options = ['connect_timeout' => 5, 'timeout' => 5];
        $response = $this->client->get($url, $options);
        $json = $response->getBody()->getContents();
        $arr = json_decode($json, true);
        if (!empty($arr)) {
            return $arr['data']['items'];
        }
        return [];
    }
}