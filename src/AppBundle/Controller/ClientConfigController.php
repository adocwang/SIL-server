<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Bank;
use AppBundle\Entity\ClientConfig;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ClientConfigController extends Controller
{

    /**
     * 类型:web,ios,android
     * @ApiDoc(
     *     section="配置",
     *     description="获取配置",
     *     parameters={
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         404="参数type 错误",
     *     }
     * )
     *
     * @Route("/client_config/list/{type}")
     * @Method("GET")
     * @param integer $type
     * @return ApiJsonResponse
     */
    public function list($type)
    {
        $types = [0];
        if ($type == 'client') {
            $types[] = 1;
        } elseif ($type == 'ios') {
            $types[] = 2;
            $types[] = 3;
        } elseif ($type == 'android') {
            $types[] = 2;
            $types[] = 4;
        }

        $configs = $this->getDoctrine()->getRepository('AppBundle:ClientConfig')->findBy(['type' => $types]);

        $configArr = [];
        /**
         * @var ClientConfig $config
         */
        foreach ($configs as $config) {
            $configArr[($config->getConfigKey())] = $config->getConfigValue();
        }
        return new ApiJsonResponse(0, 'ok', $configArr);
    }
}
