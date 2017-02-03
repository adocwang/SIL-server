<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Bank;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BankController extends Controller
{

    /**
     * state 1:正常,3:已删除
     * @ApiDoc(
     *     section="银行",
     *     description="获取银行列表",
     *     parameters={
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
     * @Route("/bank/list")
     * @Method("GET")
     * @return ApiJsonResponse
     */
    public function list()
    {
        /**
         * @var User $nowUser
         */
        $nowUser = $this->getUser();

        if ($nowUser->getRole()->getRole() != 'ROLE_ADMIN') {
            return new ApiJsonResponse(403);
        }

        $bankList = $nowUser->getBank()->toArray();
        return new ApiJsonResponse(0, 'ok', $bankList);
    }

    /**
     * 添加银行
     * @ApiDoc(
     *     section="银行",
     *     description="添加银行",
     *     parameters={
     *         {"name"="name", "dataType"="string", "required"=true, "description"="银行"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/bank/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function addBankAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['name'])) {
            return new ApiJsonResponse(1003, 'need name');
        }
        if (!in_array($this->getUser()->getRole()->getRole(), ['ROLE_ADMIN', 'ROLE_BRANCH_PRESIDENT'])) {
            return new ApiJsonResponse(407, 'no permission');
        }

        $bank = new Bank();
        $bank->setName($data['name']);
        $bank->setSuperior($this->getUser()->getBank());

        $em = $this->getDoctrine()->getManager();
        $em->persist($data);
        $em->flush();
        return new ApiJsonResponse(0, 'add success', $bank->toArray());
    }

    /**
     * 修改银行
     * state:1正常, 3已删除
     * @ApiDoc(
     *     section="银行",
     *     description="修改银行",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="银行"},
     *         {"name"="name", "dataType"="string", "required"=false, "description"="银行"},
     *         {"name"="state", "dataType"="string", "required"=false, "description"="状态"},
     *         {"name"="superior_id", "dataType"="integer", "required"=false, "description"="上级id"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="银行不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/bank/set")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setBankAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, 'need id');
        }
        $bankRepository = $this->getDoctrine()->getRepository('AppBundle:Bank');
        if (empty($bankRepository->find($data['id']))) {
            return new ApiJsonResponse(2007, 'bank not exist');
        }
        if (!in_array($this->getUser()->getRole()->getRole(), ['ROLE_ADMIN', 'ROLE_BRANCH_PRESIDENT'])) {
            return new ApiJsonResponse(407, 'no permission');
        }

        $bank = new Bank();
        if (!empty($data['name'])) {
            $bank->setName($data['name']);
        }

        if (!empty($data['state'])) {
            $bank->setState($data['state']);
        }

        if (!empty($data['superior_id'])) {
            $bankRepository = $this->getDoctrine()->getRepository('AppBundle:Bank');
            /**
             * @var Bank $superior
             */
            $superior = $bankRepository->find($data['superior_id']);
            if (empty($superior)) {
                return new ApiJsonResponse(2007, 'superior bank not exist');
            }
            $bank->setSuperior($superior);
            $right = false;
            $nowUserBank = $this->getUser()->getBank();
            $nowSuperior = $superior->getSuperior();
            do {
                if (!empty($nowSuperior)) {
                    if ($nowSuperior == $nowUserBank) {
                        $right = true;
                        break;
                    }
                } else {
                    break;
                }
            } while ($nowSuperior = $nowSuperior->getSuperior());
            if (!$right) {
                return new ApiJsonResponse(407, 'no permission');
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($bank);
        $em->flush();
        return new ApiJsonResponse(0, 'add success', $bank->toArray());
    }
}
