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
use Doctrine\ODM\MongoDB\Mapping\Annotations\Date;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\DateTime;

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
     *         {"name"="bank_name", "dataType"="string", "required"=false, "description"="所属机构名称"},
     *         {"name"="role_a_disable", "dataType"="boolean", "required"=false, "description"="roleA是否不可用：1，0"},
     *         {"name"="state", "dataType"="integer", "required"=false, "description"="状态"},
     *         {"name"="only_mine", "dataType"="integer", "required"=false, "description"="只列出我的新增企业,0,1"},
     *         {"name"="only_mine_accepted", "dataType"="integer", "required"=false, "description"="只列出我已认领的企业"},
     *         {"name"="only_my_finding", "dataType"="integer", "required"=false, "description"="列出与我的采集相关的企业"},
     *         {"name"="only_loan_ready", "dataType"="integer", "required"=false, "description"="只列出可以计算贷款辅助信息的企业"},
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
        }
        $data['now_user'] = $nowUser;

        if (!empty($data['only_my_finding']) && $data['only_my_finding'] == 1) {
            if ($this->getUser()->getRole()->isRole(Role::ROLE_PRESIDENT)) {
                $data['my_bank_finding'] = 1;
                $data['bank'] = $nowUser->getBank();
            } else {
                $data['my_finding'] = 1;
            }
        }
        if (!empty($data['only_mine_accepted']) && $data['only_mine_accepted'] == 1) {
            $data['only_role_ab'] = 1;
            $data['distribute_state'] = 3;
        }

        if (!empty($data['only_mine']) && $data['only_mine'] == 1) {
            if (!$this->getUser()->getRole()->isRole(Role::ROLE_PRESIDENT)) {
                $data['only_role_a'] = 1;
                $data['distribute_state'] = 2;
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
            $enterpriseArr = $enterprise->toArray();
            $finding = $enterprise->getFinding();
            $enterpriseArr['finding'] = [];
            if (!empty($finding)) {
                $enterpriseArr['finding'] = [
                    'id' => $finding->getId(),
                    'progress' => $finding->getProgress(),
                    'created' => $finding->getCreated()->format('Y-m-d H:i:s'),
                    'modified' => $finding->getModified()->format('Y-m-d H:i:s')
                ];
            }
            if (!empty($data['only_mine']) && $data['only_mine'] == 1) {
                if ($enterprise->getDistributeState() == 1) {
                    $enterpriseArr['distribute_state_type'] = "red";
                    $enterpriseArr['distribute_state_text'] = "待分配";
                } elseif ($enterprise->getDistributeState() == 2) {
                    if ($nowUser->getRole()->isRole(Role::ROLE_PRESIDENT)) {
                        $enterpriseArr['distribute_state_type'] = "green";
                        $enterpriseArr['distribute_state_text'] = "已分配";
                    } else {
                        $enterpriseArr['distribute_state_type'] = "red";
                        $enterpriseArr['distribute_state_text'] = "待认领";
                    }
                } elseif ($enterprise->getDistributeState() == 3) {
                    $enterpriseArr['distribute_state_type'] = "green";
                    $enterpriseArr['distribute_state_text'] = "已认领";
                }
            }
            $enterprises[] = $enterpriseArr;
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
         * @var User $nowUser
         */
        $enterpriseRepository = $this->getDoctrine()->getRepository('AppBundle:Enterprise');
        $enterprise = $enterpriseRepository->find($id);
        if (empty($enterprise)) {
            return new ApiJsonResponse(2007, 'enterprise not exists');
        }
        $nowUser = $this->getUser();
        $enterpriseResult = $enterprise->toArray();
        $enterpriseResult['link_map'] = 'http://apph5.qixin.com/new-network/' . $enterprise->getQixinId() . '.html?eid=' . $enterprise->getQixinId() . '&serviceType=c&version=3.6.1&client_type=ios&from=app';
        $enterpriseResult['relation_map'] = 'http://apph5.qixin.com/new-relation/' . $enterprise->getQixinId() . '.html?eid=' . $enterprise->getQixinId() . '&serviceType=c&version=3.6.1&client_type=ios&from=app';
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
        $enterpriseResult['operation_enable'] = [];
        if ($enterprise->getDistributeState() == 1) {
            if ($nowUser->getRole()->isRole(Role::ROLE_PRESIDENT)) {
                $enterpriseResult['operation_enable'][] = 'distribute_cm';
            }
            if ($nowUser->getRole()->isRole(Role::ROLE_BRANCH_PRESIDENT) ||
                $nowUser->getRole()->isRole(Role::ROLE_CHANNEL_MANAGER)
            ) {
                $enterpriseResult['operation_enable'][] = 'distribute_bank';
            }
        } elseif ($enterprise->getDistributeState() == 2) {
            if ($nowUser->getRole()->isRole(Role::ROLE_END_PRESIDENT)) {
                $enterpriseResult['operation_enable'][] = 'distribute_cm';
            }
            if ($nowUser->getRole()->isRole(Role::ROLE_CUSTOMER_MANAGER) && $enterprise->getRoleA() == $nowUser) {
                $enterpriseResult['operation_enable'][] = 'accept';
                $enterpriseResult['operation_enable'][] = 'refuse';
            }
        } elseif ($enterprise->getDistributeState() == 3) {
            if ($nowUser->getRole()->isRole(Role::ROLE_END_PRESIDENT) && $enterprise->getRoleA()->getState() != 1) {
                $enterpriseResult['operation_enable'][] = 'distribute_cm';
            }
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
     *         {"name"="bank_id", "dataType"="integer", "required"=false, "description"="机构id"},
     *         {"name"="role_a_id", "dataType"="integer", "required"=false, "description"="A角user_id"},
     *         {"name"="role_b_id", "dataType"="integer", "required"=false, "description"="B角user_id"},
     *         {"name"="state", "dataType"="integer", "required"=false, "description"="企业状态"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="机构不存在",
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
                    '有一个企业已被分配到您的机构',
                    $enterprise->getName() . '已被分配到您的机构！请处理！',
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
            $enterprise->setDistributeState(2);
            $this->get('app.message_sender')->sendSysMessage(
                $roleA,
                '您被分配为一个新企业的主理',
                '您已被分配为 ' . $enterprise->getName() . '的主理！请悉知！',
                ['page' => 'enterprise_operation', 'param' => ['id' => $enterprise->getId()]]
            );
            $this->get('app.op_logger')->logOtherAction('enterprise', 'assigning',
                ['id' => $enterprise->getId(), 'role_a' => $roleA->getId()]);
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
                '您被分配为一个新企业的协理',
                '您已被分配为 ' . $enterprise->getName() . '的协理！请悉知！',
                ['page' => 'enterprise_operation', 'param' => ['id' => $enterprise->getId()]]
            );
            $this->get('app.op_logger')->logOtherAction('enterprise', 'assigning',
                ['id' => $enterprise->getId(), 'role_b' => $roleB->getId()]);
        }

        if (!empty($data['state'])) {
            $enterprise->setState($data['state']);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($enterprise);
        $em->flush();
        $this->get('app.op_logger')->logUpdateAction('enterprise', $enterprise->toArray());
        return new ApiJsonResponse(0, 'update success', $enterprise->toArray());
    }

    /**
     *
     * @ApiDoc(
     *     section="企业",
     *     description="添加企业",
     *     parameters={
     *         {"name"="name", "dataType"="integer", "required"=true, "description"="名称"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="机构不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/enterprise/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function addEnterpriseAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['name'])) {
            return new ApiJsonResponse(1003, 'need name');
        }

        $enterprise = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->findOneByName($data['name']);
        if (!empty($enterprise)) {
            return new ApiJsonResponse(2007, 'enterprise name exist');
        }
        $enterprise = new Enterprise();
        $enterprise->setName($data['name']);
        $enterprise->setBank($this->getUser()->getBank());
        $enterprise->setState(0);

        $em = $this->getDoctrine()->getManager();
        $em->persist($enterprise);
        $em->flush();
        $this->get('app.op_logger')->logCreateAction('enterprise', $enterprise->getId());
        return new ApiJsonResponse(0, 'add success', $enterprise->toArray());
    }

    /**
     *
     * 企业分配是否接受
     * @ApiDoc(
     *     section="企业",
     *     description="接受与拒绝企业分配",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="企业id"},
     *         {"name"="accept", "dataType"="integer", "required"=true, "description"="是否接受:-1不接受，1接受"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="机构不存在",
     *         2008="角不存在",
     *         2009="角不是客户经理",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/enterprise/set_distribute")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setEnterpriseDistributeAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id']) || empty($data['accept'])) {
            return new ApiJsonResponse(1003, 'need id and accept');
        }

        $enterprise = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->findOneBy(['id' => $data['id']]);
        if (empty($enterprise) || !$enterprise instanceof Enterprise) {
            return new ApiJsonResponse(2007, 'enterprise not exist');
        }

        if ($enterprise->getRoleA() != $this->getUser()) {
            return new ApiJsonResponse(407, 'no permission');
        }
        if ($data['accept'] == -1) {
            $enterprise->setRoleA(null);
            $enterprise->setRoleB(null);
            $enterprise->setDistributeState(1);

            $presidentRoles = [];
            $presidentRoles[] = Role::createRole(Role::ROLE_END_PRESIDENT_WITH_CM);
            $presidentRoles[] = Role::createRole(Role::ROLE_END_PRESIDENT);
            $managers = $this->getDoctrine()->getRepository('AppBundle:User')->findBy(
                ['bank' => $enterprise->getBank(), 'role' => $presidentRoles]
            );
            foreach ($managers as $manager) {
                $this->get('app.message_sender')->sendSysMessage(
                    $manager,
                    '有一个您已分配的企业被拒绝认领',
                    $enterprise->getName() . '已被拒绝认领！请处理！',
                    ['page' => 'enterprise_operation', 'param' => ['id' => $enterprise->getId()]]
                );
            }
            $this->get('app.op_logger')->logOtherAction('enterprise', 'refuse',
                ['id' => $enterprise->getId(), 'role_a' => $enterprise->getRoleA()->getId()]);
        } else {
            $enterprise->setDistributeState(3);
            $this->get('app.op_logger')->logOtherAction('enterprise', 'accept',
                ['id' => $enterprise->getId(), 'role_a' => $enterprise->getRoleA()->getId()]);
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
     *     description="设置企业采集结果",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="企业id"},
     *         {"name"="data", "dataType"="string", "required"=false, "description"="调查结果数据"}
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
        $this->get('app.op_logger')->logOtherAction('enterprise', 'update_finding',
            ['id' => $enterprise->getId(), 'role_a' => $enterprise->getRoleA()->getId()]);
        return new ApiJsonResponse(0, 'set success', $finding);
    }

    /**
     * 获得企业调查结果
     * @ApiDoc(
     *     section="企业",
     *     description="获得企业采集结果",
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
        $finding = $enterprise->getFinding();
        $findingArr = new \stdClass();
        if (!empty($finding)) {
            $findingArr = $finding->toArray();
            $findingArr['un_pass_reason'] = $finding->getUnPassReason();
            /**
             * @var $nowUser User
             */
            $nowUser = $this->getUser();
            $findingArr['operation_enable'] = 'none';
            if ($finding->getProgress() == 0 && $enterprise->getRoleB() == $nowUser) {
                $findingArr['operation_enable'] = 'check';
            }
            if ($finding->getProgress() == 1 && $enterprise->getBank() == $nowUser->getBank()
                && $nowUser->getRole()->isRole(Role::ROLE_PRESIDENT)
            ) {
                $findingArr['operation_enable'] = 'check';
            }
            if ($finding->getProgress() == 3 && $enterprise->getRoleA() == $nowUser) {
                $findingArr['operation_enable'] = 'refinding';
            }
        }
        return new ApiJsonResponse(0, 'ok', $findingArr);
    }

    /**
     *
     * 设置调查结果是否可以通过
     * @ApiDoc(
     *     section="企业",
     *     description="设置采集结果是否可以通过",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="企业id"},
     *         {"name"="pass", "dataType"="string", "required"=true, "description"="-1不通过，1已通过"},
     *         {"name"="un_pass_reason", "dataType"="string", "required"=false, "description"="不通过原因"}
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
     *         2008="没有finding",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/enterprise/set_finding_pass")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setEnterpriseFindingPassAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id']) || empty($data['pass'])) {
            return new ApiJsonResponse(1003, 'need id and pass');
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
            return new ApiJsonResponse(2007, 'finding not exist');
        }
        if ($data['pass'] == '-1') {
            $newProgress = 3;
            $finding->setProgress($newProgress);
            if (empty($data['un_pass_reason'])) {
                return new ApiJsonResponse(1003, 'need unpass reason');
            }
            $finding->setUnPassReason($this->getUser()->getTrueName() . '设置为未通过，原因：' . $data['un_pass_reason'] .
                '，时间：' . (new \DateTime())->format('Y-m-d H:i:s') . "\n" . $finding->getUnPassReason());
        } elseif ($data['pass'] == '1') {
            $newProgress = $finding->getProgress() + 1;
            if ($newProgress > 2) {
                $newProgress = 2;
            }
            $finding->setProgress($newProgress);
            $finding->setUnPassReason($this->getUser()->getTrueName() . '设置为通过，时间：' .
                (new \DateTime())->format('Y-m-d H:i:s') . "\n" . $finding->getUnPassReason());
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($finding);
        $em->flush();
        $this->get('app.op_logger')->logOtherAction('enterprise', 'pass_finding', $finding->toArray());
        return new ApiJsonResponse(0, 'set pass success', $finding->toArray());
    }

    /**
     *
     * 删除采集结果
     * @ApiDoc(
     *     section="企业",
     *     description="重新采集",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="企业id"}
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
     *         2008="没有finding",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/enterprise/re_finding")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function enterpriseReFindingAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, 'need id');
        }

        /**
         * @var Enterprise $enterprise
         * @var Finding $finding
         */
        $enterprise = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->find($data['id']);
        if (empty($enterprise) || !$enterprise instanceof Enterprise) {
            return new ApiJsonResponse(2007, 'enterprise not exist');
        }

        $finding = $this->getDoctrine()->getRepository('AppBundle:Finding')->findOneByEnterprise($enterprise);
        if (empty($finding)) {
            return new ApiJsonResponse(2007, 'finding not exist');
        }
        $finding->setProgress(-1);
        $em = $this->getDoctrine()->getManager();
        $em->persist($finding);
        $em->flush();
        $this->get('app.op_logger')->logOtherAction('enterprise', '重新采集', ['公司名称' => $enterprise->getName()]);
        return new ApiJsonResponse(0, 're_finding success');
    }
}
