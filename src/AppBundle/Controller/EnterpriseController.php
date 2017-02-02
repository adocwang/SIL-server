<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Bank;
use AppBundle\Entity\Enterprise;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EnterpriseController extends Controller
{
    /**
     * 用户state 0:未通过筛选,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="enterprise",
     *     description="获取企业列表",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"},
     *         {"name"="name", "dataType"="string", "required"=false, "description"="企业名称"},
     *         {"name"="bank_name", "dataType"="string", "required"=false, "description"="所属银行名称(非管理员用户会自动通过当前用户的银行覆盖这个字段)"},
     *         {"name"="state", "dataType"="string", "required"=false, "description"="状态"},
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
     * @Route("/enterprise/list")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function list(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['page']) || $data['page'] < 1) {
            $data['page'] = 1;
        }

        if (!empty($data['bank_name'])) {
            /**
             * @var \AppBundle\Entity\Bank $bank
             */
            $data['bank'] = $this->getDoctrine()->getRepository('AppBundle:Bank')->findOneByName($data['bank_name']);

        } else {
            $data['bank'] = null;
        }

        /**
         * @var User $nowUser
         */
        $nowUser = $this->getUser();
        if ($nowUser->getRole()->getRole() != 'ROLE_ADMIN') {
            $data['bank'] = $nowUser->getBank();
            $data['state'] = 1;
        }

        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\Enterprise $enterprise
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->listPage($data['page'],
            $this->getParameter('page_count'), $data);
        $enterprises = [];
        foreach ($pageData['data'] as $enterprise) {
            $enterprises[] = $enterprise->toArray();
        }
        return new ApiJsonResponse(0, 'ok', ['page_count' => $pageData['pageCount'], 'enterprises' => $enterprises]);
    }

    /**
     * 企业state 0:未激活,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="enterprise",
     *     description="获取一个企业",
     *     parameters={
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="用户不存在"
     *     }
     * )
     *
     * @Route("/enterprise/{id}")
     * @Method("GET")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function getEnterpriseAction(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['id']) && empty($data['phone'])) {
            return new ApiJsonResponse(1003, 'need id or phone');
        }
        /**
         * @var \AppBundle\Repository\EnterpriseRepository $enterpriseRepository
         * @var \AppBundle\Entity\Enterprise $enterprise
         */
        $enterpriseRepository = $this->getDoctrine()->getRepository('AppBundle:Enterprise');
        $enterprise = $enterpriseRepository->find($data['id']);
        if (empty($enterprise)) {
            return new ApiJsonResponse(2007, 'user not exists');
        }
        return new ApiJsonResponse(0, 'ok', $enterprise->toArray());
    }

    /**
     *
     * 修改企业，用于分配企业，分配AB角
     * @ApiDoc(
     *     section="enterprise",
     *     description="修改企业",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="企业id"},
     *         {"name"="bank_id", "dataType"="integer", "required"=false, "description"="银行id"},
     *         {"name"="role_a_id", "dataType"="integer", "required"=false, "description"="A角id"},
     *         {"name"="role_b_id", "dataType"="integer", "required"=false, "description"="B角id"},
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
     *         2008="A角不存在",
     *         2009="B角不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/enterprise/set")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setEnterpriseAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, 'need id');
        }

        $enterprise = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->findOneBy(['id' => $data['id']]);
        if (empty($enterprise) || !$enterprise instanceof Enterprise) {
            return new ApiJsonResponse(2007, 'enterprise not exist');
        }

        if (!$this->isGranted('edit', $enterprise)) {
            return new ApiJsonResponse(407, 'no permission');
        }

        if (!empty($data['bank_id'])) {
            /**
             * @var Bank $bank
             */
            $bank = $this->getDoctrine()->getRepository('AppBundle:Bank')->find($data['bank_id']);
            if (empty($bank)) {
                return new ApiJsonResponse('2007', 'bank not exists');
            }
        }
        $nowUserBank = $this->getUser()->getBank();

        /**
         * @var User $roleA $roleB
         */

        if (!empty($data['role_a_id'])) {
            $roleA = $this->getDoctrine()->getRepository('AppBundle:User')->find($data['role_a_id']);
            if (empty($roleA)) {
                return new ApiJsonResponse('2008', 'role_b not exists');
            }
            if ($roleA->getBank() != $nowUserBank) {
                return new ApiJsonResponse(407, 'no permission to set role_a');
            }
        }

        if (!empty($data['role_b_id'])) {
            $roleB = $this->getDoctrine()->getRepository('AppBundle:User')->find($data['role_b_id']);
            if (empty($roleB)) {
                return new ApiJsonResponse('2008', 'role_b not exists');
            }
            if ($roleB->getBank() != $nowUserBank) {
                return new ApiJsonResponse(407, 'no permission to set role_b');
            }
        }
        $right = false;
        $nowSuperior = $bank;
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
            return new ApiJsonResponse(407, 'no permission to set bank');
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($enterprise);
        $em->flush();
        return new ApiJsonResponse(0, 'update success', $enterprise->toArray());
    }
}
