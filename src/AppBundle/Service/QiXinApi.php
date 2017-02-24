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
//        $url = 'enterprise/getDetailAndContactById?appkey=ada44bd0070711e6b8a865678b483fde' . $this->appKey . '&keyword=' . urlencode($companyName);
        $url = 'enterprise/getDetailByName?appkey=ada44bd0070711e6b8a865678b483fde' . $this->appKey . '&keyword=' . urlencode($companyName);
        $options = ['connect_timeout' => 5, 'timeout' => 5];
        $response = $this->client->get($url, $options);
        $json = $response->getBody()->getContents();
        $arr = json_decode($json, true);
        if (!empty($arr)) {
            return $arr['data'];
        }
        return [];
    }

    /**
     * @param $qixinId
     * @return array|null
     * @internal param string $companyName
     */
    public function getGongShangInfoById($qixinId)
    {
        /**
         * @var \GuzzleHttp\Client $qixinApi
         */
        $url = 'enterprise/getDetailAndContactById?appkey=ada44bd0070711e6b8a865678b483fde&id=' . $qixinId;
        $options = ['connect_timeout' => 5, 'timeout' => 5];
        $response = $this->client->get($url, $options);
        $json = $response->getBody()->getContents();
        $arr = json_decode($json, true);
        if (!empty($arr)) {
            return $arr['data'];
        }
        return [];
    }

    public function getChildCompany($vcCompanyName)
    {
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