<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Constant\State;
use AppBundle\Entity\Role;
use AppBundle\Entity\Bank;
use AppBundle\Entity\Enterprise;
use AppBundle\Entity\Finding;
use AppBundle\Entity\Loan;
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
     *     section="企业",
     *     description="获取当前用户能够处理的企业列表",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"},
     *         {"name"="page_limit", "dataType"="integer", "required"=false, "description"="每页size"},
     *         {"name"="name", "dataType"="string", "required"=false, "description"="企业名称"},
     *         {"name"="bank_name", "dataType"="string", "required"=false, "description"="所属银行名称"},
     *         {"name"="role_a_disable", "dataType"="boolean", "required"=false, "description"="roleA是否不可用：1，0"},
     *         {"name"="state", "dataType"="integer", "required"=false, "description"="状态"},
     *         {"name"="only_mine", "dataType"="integer", "required"=false, "description"="只列出我的企业,0,1"},
     *         {"name"="only_role_a", "dataType"="integer", "required"=false, "description"="只列出我是role a的企业,0,1"},
     *         {"name"="in_black_list", "dataType"="boolean", "required"=false, "description"="是否在黑名单"},
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
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)) {
            //单用户不是管理员的时候
//            $data['bank'] = $nowUser->getBank();
            $data['state'] = State::STATE_NORMAL;//只拉得到正常状态的企业
            $data['in_black_list'] = 0;//只拉得到不在黑名单的企业
        }
        $data['now_user'] = $nowUser;

        if (!empty($data['only_mine']) && $data['only_mine'] == 1) {
            if ($this->getUser()->getRole()->isRole(Role::ROLE_CUSTOMER_MANAGER)) {
                $data['only_user'] = 1;
            } else {
                $data['bank'] = $nowUser->getBank();
            }
        }

        $pageLimit = $this->getParameter('page_limit');
        if (!empty($data['page_limit']) && $data['page_limit'] > 0) {
            $pageLimit = $data['page_limit'];
        }

        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\Enterprise $enterprise
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->listPage($data['page'], $pageLimit, $data);
        $enterprises = [];
        foreach ($pageData['data'] as $enterprise) {
            $enterprises[] = $enterprise->toArray();
        }
        return new ApiJsonResponse(0, 'ok', [
            'count' => $pageData['count'],
            'page_limit' => $pageLimit,
            'page_count' => $pageData['pageCount'],
            'enterprises' => $enterprises
        ]);
    }


    /**
     * 企业state 0:未激活,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="企业",
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
     * @param integer $id
     * @return ApiJsonResponse
     */
    public function getEnterpriseAction($id)
    {
        if (empty($id)) {
            return new ApiJsonResponse(1003, 'need id');
        }
        /**
         * @var \AppBundle\Repository\EnterpriseRepository $enterpriseRepository
         * @var \AppBundle\Entity\Enterprise $enterprise
         * @var Loan $loan
         */
        $enterpriseRepository = $this->getDoctrine()->getRepository('AppBundle:Enterprise');
        $enterprise = $enterpriseRepository->find($id);
        if (empty($enterprise)) {
            return new ApiJsonResponse(2007, 'enterprise not exists');
        }
        $enterpriseResult = $enterprise->toArray();
        if (!empty($enterprise->getDetailObjId())) {
            $enterpriseMongoRepository = $this->get('doctrine_mongodb')->getManager()->getRepository('AppBundle:EnterpriseDetail');
            $enterpriseDetail = $enterpriseMongoRepository->find($enterprise->getDetailObjId());
            $enterpriseResult['detail'] = $enterpriseDetail->getDetail();
        } else {
            $enterpriseResult['detail'] = new \stdClass();
        }

        $enterpriseResult['loan'] = new \stdClass();
        $loanRepository = $this->getDoctrine()->getRepository('AppBundle:Loan');
        $loans = $loanRepository->findBy(['enterprise' => $enterprise], ['id' => 'DESC'], 1);
        if (!empty($loans)) {
            $loan = $loans[0];
            $enterpriseResult['loan'] = $loan->toArray();
        }

        return new ApiJsonResponse(0, 'ok', $enterpriseResult);
    }

    /**
     *
     * 修改企业，用于分配企业，分配AB角
     * state 0:未激活,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="企业",
     *     description="修改企业",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="企业id"},
     *         {"name"="bank_id", "dataType"="integer", "required"=false, "description"="银行id"},
     *         {"name"="role_a_id", "dataType"="integer", "required"=false, "description"="A角user_id"},
     *         {"name"="role_b_id", "dataType"="integer", "required"=false, "description"="B角user_id"},
     *         {"name"="state", "dataType"="integer", "required"=false, "description"="企业状态"},
     *         {"name"="in_black_list", "dataType"="integer", "required"=false, "description"="是否在黑名单，0否，1是"},
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
     *         2008="角不存在",
     *         2009="角不是客户经理",
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

        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN) && !$this->getUser()->getRole()->isRole(Role::ROLE_END_PRESIDENT)) {
            return new ApiJsonResponse(407, 'no permission');
        }

        $nowUserBank = $this->getUser()->getBank();
        if (!empty($data['bank_id'])) {
            /**
             * @var Bank $bank
             */
            $bank = $this->getDoctrine()->getRepository('AppBundle:Bank')->find($data['bank_id']);
            if (empty($bank)) {
                return new ApiJsonResponse(2007, 'bank not exists');
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
            $enterprise->setBank($bank);
            $presidentRoles = [];
            $presidentRoles[] = Role::createRole(Role::ROLE_BRANCH_PRESIDENT);
            $presidentRoles[] = Role::createRole(Role::ROLE_END_PRESIDENT);
            $managers = $this->getDoctrine()->getRepository('AppBundle:User')->findBy(['bank' => $bank, 'role' => $presidentRoles]);
            foreach ($managers as $manager) {
                $this->get('app.message_sender')->sendSysMessage(
                    $manager,
                    '有一个企业已被分配到您的银行',
                    $enterprise->getName() . '已被分配到您的银行！请处理！',
                    ['page' => 'enterprise_operation', 'param' => ['id' => $enterprise->getId()]]
                );
            }
        }

        /**
         * @var User $roleA ,$roleB
         */

        if (!empty($data['role_a_id'])) {
            $roleA = $this->getDoctrine()->getRepository('AppBundle:User')->find($data['role_a_id']);
            if (empty($roleA)) {
                return new ApiJsonResponse(2008, 'role_a not exists');
            }
            if ($roleA->getBank() != $nowUserBank && $this->getUser()->getRole()->getRole() != 'ROLE_ADMIN') {
                return new ApiJsonResponse(407, 'no permission to set role_a');
            }
            if (!$roleA->getRole()->isRole(Role::ROLE_CUSTOMER_MANAGER)) {
                return new ApiJsonResponse(2009, 'role a not customer manager');
            }
            $enterprise->setRoleA($roleA);
            $this->get('app.message_sender')->sendSysMessage(
                $roleA,
                '您被分配了一个新的企业',
                '您已被分配为 ' . $enterprise->getName() . '的主理！请悉知！',
                ['page' => 'enterprise_operation', 'param' => ['id' => $enterprise->getId()]]
            );
        }

        if (!empty($data['role_b_id'])) {
            $roleB = $this->getDoctrine()->getRepository('AppBundle:User')->find($data['role_b_id']);
            if (empty($roleB)) {
                return new ApiJsonResponse(2008, 'role_b not exists');
            }
            if ($roleB->getBank() != $nowUserBank && $this->getUser()->getRole()->getRole() != 'ROLE_ADMIN') {
                return new ApiJsonResponse(407, 'no permission to set role_b');
            }
            if (!$roleB->getRole()->isRole(Role::ROLE_CUSTOMER_MANAGER)) {
                return new ApiJsonResponse(2009, 'role b not customer manager');
            }
            $enterprise->setRoleB($roleB);
            $this->get('app.message_sender')->sendSysMessage(
                $roleB,
                '您被分配了一个新的企业',
                '您已被分配为 ' . $enterprise->getName() . '的协理！请悉知！',
                ['page' => 'enterprise_operation', 'param' => ['id' => $enterprise->getId()]]
            );
        }

        if (!empty($data['state'])) {
            $enterprise->setState($data['state']);
        }

        if (!empty($data['in_black_list'])) {
            $enterprise->setInBlackList($data['in_black_list']);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($enterprise);
        $em->flush();
        return new ApiJsonResponse(0, 'update success', $enterprise->toArray());
    }

    /**
     *
     * 修改企业调查结果
     * @ApiDoc(
     *     section="企业",
     *     description="修改企业调查结果",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="企业id"},
     *         {"name"="data", "dataType"="string", "required"=false, "description"="调查结果数据"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="企业不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/enterprise/set_finding")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setEnterpriseFindingAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id']) || empty($data['data'])) {
            return new ApiJsonResponse(1003, 'need id and data');
        }

        /**
         * @var Enterprise $enterprise
         */
        $enterprise = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->find($data['id']);
        if (empty($enterprise) || !$enterprise instanceof Enterprise) {
            return new ApiJsonResponse(2007, 'enterprise not exist');
        }

        $finding = $enterprise->getFinding();
        if (empty($finding)) {
            $finding = new Finding();
            $finding->setEnterprise($enterprise);
        }
        $finding->setData($data['data']);

        //权限判断
        $nowUser = $this->getUser();
        if ($nowUser != $enterprise->getRoleA()) {
            return new ApiJsonResponse(407, 'only role A can set finding');
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($finding);
        $em->flush();
        return new ApiJsonResponse(0, 'set success', $finding);
    }

    /**
     * 获得企业调查结果
     * @ApiDoc(
     *     section="企业",
     *     description="获得企业调查结果",
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
     * @Route("/enterprise/get_finding/{enterprise_id}")
     * @Method("GET")
     * @param integer $enterprise_id
     * @return ApiJsonResponse
     */
    public function getEnterpriseFindingAction($enterprise_id)
    {
        if (empty($enterprise_id)) {
            return new ApiJsonResponse(1003, 'need enterprise_id');
        }
        /**
         * @var \AppBundle\Repository\EnterpriseRepository $enterpriseRepository
         * @var \AppBundle\Entity\Enterprise $enterprise
         * @var Loan $loan
         */
        $enterpriseRepository = $this->getDoctrine()->getRepository('AppBundle:Enterprise');
        $enterprise = $enterpriseRepository->find($enterprise_id);
        if (empty($enterprise)) {
            return new ApiJsonResponse(2007, 'enterprise not exists');
        }

        return new ApiJsonResponse(0, 'ok', $enterprise->getFinding() ? $enterprise->getFinding()->toArray() : new \stdClass());
    }
}
