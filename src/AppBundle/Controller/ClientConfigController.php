<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\ClientConfig;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ClientConfigController extends Controller
{

    /**
     * 类型:web,ios,android,all
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
        } elseif ($type == 'all') {
            $types = [0, 1, 2, 3, 4, 5];
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

    /**
     * 类型:web,ios,android,all
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
     * @Route("/client_config/get_special/{key}")
     * @Method("GET")
     * @param integer $key
     * @return ApiJsonResponse
     */
    public function getSpecialConfigAction($key)
    {
        if (empty($key)) {
            return new ApiJsonResponse(1003, '缺少key');
        }

        /**
         * @var ClientConfig $config
         */
        $config = $this->getDoctrine()->getRepository('AppBundle:ClientConfig')->findOneByConfigKey($key);

        if (empty($config)) {
            return new ApiJsonResponse(0, 'ok', []);
//            return new ApiJsonResponse(2007, 'key not exist');
        }
        return new ApiJsonResponse(0, 'ok', $config->getConfigValue());
    }

    /**
     * @ApiDoc(
     *     section="配置",
     *     description="设置配置",
     *     parameters={
     *         {"name"="key", "dataType"="string", "required"=true, "description"="key"},
     *         {"name"="value", "dataType"="string", "required"=true, "description"="value"},
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
     * @Route("/client_config/set_special")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setSpecialConfigAction(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['key']) || empty($data['value'])) {
            return new ApiJsonResponse(1003, '缺少key或value');
        }

        /**
         * @var ClientConfig $config
         */
        $config = $this->getDoctrine()->getRepository('AppBundle:ClientConfig')->findOneByConfigKey($data['key']);
        if (empty($config)) {
            $config = new ClientConfig();
        }
        $config->setConfigKey($data['key']);
        $config->setConfigValue($data['value']);
        $config->setType(5);
        $em = $this->getDoctrine()->getManager();
        $em->persist($config);
        $em->flush();
        $this->get('app.op_logger')->logUpdateAction('系统配置', ['key' => $config->getConfigKey()]);
        return new ApiJsonResponse(0, 'ok', $config->toArray());
    }
}
