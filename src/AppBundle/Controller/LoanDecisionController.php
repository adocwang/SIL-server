<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LoanDecisionController extends Controller
{

    /**
     *
     * @ApiDoc(
     *     section="贷款决策",
     *     description="获取一个企业的贷款决策评分",
     *     parameters={
     *         {"name"="id", "dataType"="string", "required"=false, "description"="企业id"},
     *         {"name"="data", "dataType"="integer", "required"=false, "description"="修正后的数据"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少id",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/loan_decision/get_result/")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function getResultAction(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, 'need id');
        }
        return new ApiJsonResponse(0);
    }
}
