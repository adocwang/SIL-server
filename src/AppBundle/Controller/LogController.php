<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LogController extends Controller
{
    /**
     *
     * @ApiDoc(
     *     section="日志",
     *     description="日志列表，搜索日志",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"},
     *         {"name"="page_limit", "dataType"="integer", "required"=false, "description"="每页size"},
     *         {"name"="module", "dataType"="string", "required"=false, "description"="模块"},
     *         {"name"="action", "dataType"="string", "required"=false, "description"="操作"},
     *         {"name"="operator_id", "dataType"="integer", "required"=false, "description"="操作员"},
     *         {"name"="time_from", "dataType"="integer", "required"=false, "description"="开始时间时间戳"},
     *         {"name"="time_to", "dataType"="integer", "required"=false, "description"="结束时间时间戳"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/log/list")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function listAction(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['page']) || $data['page'] < 1) {
            $data['page'] = 1;
        }
        $pageLimit = $this->getParameter('page_limit');
        if (!empty($data['page_limit']) && $data['page_limit'] > 0) {
            $pageLimit = $data['page_limit'];
        }


        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\Log $log
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:Log')->listPage($data['page'], $pageLimit, $data);
        $logs = [];
        foreach ($pageData['data'] as $log) {
            $logs[] = $log->toArray();
        }
        return new ApiJsonResponse(0, 'ok', [
            'count' => $pageData['count'],
            'page_limit' => $pageLimit,
            'page_count' => $pageData['pageCount'],
            'logs' => $logs
        ]);
    }

}
