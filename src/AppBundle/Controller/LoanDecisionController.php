<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Document\LoanConditionData;
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
     *         {"name"="id", "dataType"="string", "required"=true, "description"="企业id"},
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
     * @Route("/loan_decision/get_result")
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
        if (empty($data['data'])) {
            $loanConditionData = $this->get('doctrine_mongodb')->
            getRepository('AppBundle:LoanConditionData')->findOneBy(['enterpriseId' => intval($data['id'])]);
            if (!empty($loanConditionData)) {
                $historyData = $loanConditionData->getData();
            } else {
                $historyData = [];
            }
        } else {
            $historyData = json_decode($data['data'], 1);
        }
        $result = $this->get('app.loan_decision_helper')->getDataForm($data['id'], $historyData);
        return new ApiJsonResponse(0, 'ok', $result);
    }

    /**
     *
     * @ApiDoc(
     *     section="贷款决策",
     *     description="设置一个企业的修改后贷款条件",
     *     parameters={
     *         {"name"="id", "dataType"="string", "required"=true, "description"="企业id"},
     *         {"name"="data", "dataType"="integer", "required"=true, "description"="修正后的数据"},
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
     * @Route("/loan_decision/set_result")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setResultAction(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['id']) || empty($data['data'])) {
            return new ApiJsonResponse(1003, 'need id and data');
        }
        $data['data'] = json_decode($data['data'], true);
        $historyData = $this->get('doctrine_mongodb')->
        getRepository('AppBundle:LoanConditionData')->findOneBy(['enterpriseId' => intval($data['id'])]);
        if (empty($historyData)) {
            $historyData = new LoanConditionData();
            $historyData->setEnterpriseId($data['id']);
        }
        $historyData->setData($data['data']);
        $mm = $this->get('doctrine_mongodb')->getManager();
        $mm->persist($historyData);
        $mm->flush();
        $this->get('app.op_logger')->logUpdateAction('loan_decision', ['id' => $historyData->getId()]);
        return new ApiJsonResponse(0, 'ok');
    }
}
