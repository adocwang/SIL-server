<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Enterprise;
use AppBundle\Entity\Loan;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LoanController extends Controller
{

    /**
     * progress 0:已受理，1：协理已通过，2：支行已通过，3：分行已通过，4：审批通过，5：签约，6：放款
     * @ApiDoc(
     *     section="贷款",
     *     description="获取当前用户所能看到的贷款申请列表",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"},
     *         {"name"="page_limit", "dataType"="integer", "required"=false, "description"="每页size"},
     *         {"name"="progress", "dataType"="integer", "required"=false, "description"="贷款状态，多种状态用逗号,隔开"},
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
     * @Route("/loan/list/")
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

        $pageLimit = $this->getParameter('page_limit');
        if (!empty($data['page_limit']) && $data['page_limit'] > 0) {
            $pageLimit = $data['page_limit'];
        }
        //TODO 整个progress==0都是为了给企业那边临时打补丁，以后要删掉
        if ($data['progress'] == 0) {
            $data['only_mine'] = 1;
            $data['distribute_state'] = 3;
            $data['now_user'] = $this->getUser();
            if ($this->getUser()->getRole()->isRole(Role::ROLE_CUSTOMER_MANAGER)) {
                $data['only_user'] = 1;
            } else {
                $data['bank'] = $this->getUser()->getBank();
            }

            /**
             * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
             * @var \AppBundle\Entity\Enterprise $enterprise
             */
            $pageData = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->listPage($data['page'], $pageLimit, $data);
            $enterprises = [];
            $loans = [];
            foreach ($pageData['data'] as $enterprise) {
                $loan = [
                    'id' => rand(1, 100),
                    'moreData' => [],
                    'progress' => 0,
                    'state' => 1,
                    'created' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'modified' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'enterprise' => $enterprise->toArray()
                ];
                $loans[] = $loan;
            }
            return new ApiJsonResponse(0, 'ok', [
                'count' => $pageData['count'],
                'page_limit' => $pageLimit,
                'page_count' => $pageData['pageCount'],
                'loans' => $loans
            ]);
        }
        if (!empty($data['progress'])) {
            if (strpos($data['progress'], ',') === false) {
                $data['progresses'] = [($data['progress'])];
            } else {
                $data['progresses'] = explode(',', $data['progress']);
            }
        }
        $data['now_user'] = $this->getUser();
        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\Loan $loan
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:Loan')->listPage($data['page'], $pageLimit, $data);
        $loans = [];
        foreach ($pageData['data'] as $loan) {
            $arr = $loan->toArray();
            $arr['enterprise'] = $loan->getEnterprise()->toArray();
            $loans[] = $arr;
        }
        return new ApiJsonResponse(0, 'ok', [
            'count' => $pageData['count'],
            'page_limit' => $pageLimit,
            'page_count' => $pageData['pageCount'],
            'loans' => $loans
        ]);
    }

    /**
     * 发起贷款
     * progress 0:已受理，1：协理审批中，2：机构审批中，3：审批通过，4：签约，5：放款
     * @ApiDoc(
     *     section="贷款",
     *     description="发起贷款",
     *     parameters={
     *         {"name"="enterprise_id", "dataType"="integer", "required"=true, "description"="企业id"},
     *         {"name"="more_data", "dataType"="integer", "required"=false, "description"="更多信息"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="enterprise 不存在",
     *         2008="有正在流程中的贷款",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/loan/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function addLoanAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['enterprise_id'])) {
            return new ApiJsonResponse(1003, '缺少企业id');
        }
        $enterprise = $this->getDoctrine()->getRepository('AppBundle:Enterprise')->find($data['enterprise_id']);
        if ($enterprise->getRoleA() != $this->getUser()) {
            return new ApiJsonResponse(407, 'no permission');
        }
        if (empty($enterprise)) {
            return new ApiJsonResponse(2007, '企业不存在');
        }
        $loanRepository = $this->getDoctrine()->getRepository('AppBundle:Loan');
        if ($loanRepository->findOneBy(['enterprise' => $enterprise, 'progress' => '!= 5'])) {
            return new ApiJsonResponse(2008, '该企业已经有未走完流程的贷款');
        }
        $loan = new Loan();
        $loan->setEnterprise($enterprise);
        $loan->setMoreData(json_encode([
            [
                '时间' => date('Y-m-d H:i:s'),
                '备注' => $data['more_data'],
                '用户' => $this->getUser()->getTrueName()
            ]
        ], true));
        $loan->setBank($this->getUser()->getBank());
        if (empty($enterprise->getRoleB())) {
            $loan->setProgress(1);
        } else {
            $loan->setProgress(0);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($loan);
        $em->flush();
        $this->get('app.op_logger')->logCreateAction('loan', $loan->getId());
        return new ApiJsonResponse(0, 'add success', $loan->toArray());
    }

    /**
     * 修改机构
     * state:1正常, 3已删除
     * progress 0:已受理，1：协理审批中，2：机构审批中，3：审批通过，4：签约，5：放款
     * @ApiDoc(
     *     section="贷款",
     *     description="修改机构",
     *     parameters={
     *         {"name"="loan_id", "dataType"="integer", "required"=true, "description"="loan_id"},
     *         {"name"="pass", "dataType"="boolean", "required"=true, "description"="是否通过"},
     *         {"name"="more_data", "dataType"="text", "required"=false, "description"="更多信息"},
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
     * @Route("/loan/set")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setLoanProgressAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['loan_id']) || empty($data['pass'])) {
            return new ApiJsonResponse(1003, '缺少贷款id');
        }
        $loan = $this->getDoctrine()->getRepository('AppBundle:Loan')->find($data['loan_id']);
        $moreData = json_decode($loan->getMoreData(), true);
        $tmpData = [
            '时间' => date('Y-m-d H:i:s'),
            '备注' => $data['more_data'],
            '用户' => $this->getUser()->getTrueName()
        ];

        if ($data['pass'] == 1) {
            $tmpData['结果'] = '通过';
        } else {
            $tmpData['结果'] = '未通过';
        }
        $moreData[] = $tmpData;
        $loan->setMoreData(json_encode($moreData));
        /**
         * @var User $nowUser
         */
        $enterprise = $loan->getEnterprise();
        $nowUser = $this->getUser();
        switch ($loan->getProgress()) {
            case 0:
                if ($enterprise->getRoleA() != $nowUser) {
                    return new ApiJsonResponse(407, 'no permission1');
                }
                if (empty($enterprise->getRoleB())) {
                    $loan->setProgress(2);
                    $presidentRole = Role::createRole(Role::ROLE_END_PRESIDENT);
                    $manager = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['bank' => $loan->getBank(), 'role' => $presidentRole]);
                    $this->get('app.message_sender')->sendSysMessage(
                        $manager,
                        '有一个贷款申请需要您审批',
                        $enterprise->getName() . '已经提出贷款申请，请审批！',
                        ['page' => 'enterprise_detail', 'param' => ['id' => $enterprise->getId()]]
                    );
                } else {
                    $loan->setProgress(1);
                    $this->get('app.message_sender')->sendSysMessage(
                        $enterprise->getRoleB(),
                        '有一个贷款申请需要您复合',
                        $enterprise->getName() . '的主理！已经提出贷款申请，请审批！',
                        ['page' => 'enterprise_detail', 'param' => ['id' => $enterprise->getId()]]
                    );
                }
                break;
            case 1:
                //协理处理
                if ($enterprise->getRoleB() != $nowUser) {
                    return new ApiJsonResponse(407, 'no permission2');
                }
                if ($data['pass'] == 1) {
                    $loan->setProgress(2);
                    $presidentRole = Role::createRole(Role::ROLE_END_PRESIDENT);
                    $manager = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['bank' => $loan->getBank(), 'role' => $presidentRole]);
                    $this->get('app.message_sender')->sendSysMessage(
                        $manager,
                        '有一个贷款申请需要您审批',
                        $enterprise->getName() . '已经提出贷款申请，请审批！',
                        ['page' => 'enterprise_detail', 'param' => ['id' => $enterprise->getId()]]
                    );
                }
                break;
            case 2:
                //行长处理
                if ($loan->getBank() != $nowUser->getBank() ||
                    !in_array($nowUser->getRole()->getRole(), ['ROLE_END_PRESIDENT', 'ROLE_BRANCH_PRESIDENT'])
                ) {
                    return new ApiJsonResponse(407, 'no permission3');
                }
                if ($data['pass'] == 1) {
                    $superior = $nowUser->getBank()->getSuperior();
                    if (!empty($superior)) {
                        $loan->setBank($superior);
                        $presidentRole = Role::createRole(Role::ROLE_BRANCH_PRESIDENT);
                        $manager = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['bank' => $superior, 'role' => $presidentRole]);
                        $this->get('app.message_sender')->sendSysMessage(
                            $manager,
                            '有一个贷款申请需要您审批',
                            $enterprise->getName() . '已经提出贷款申请，请审批！',
                            ['page' => 'enterprise_detail', 'param' => ['id' => $enterprise->getId()]]
                        );
                    } else {
                        $loan->setProgress(3);
                    }
                }
                break;
            default:
                if ($data['pass'] == 1) {
                    $loan->setProgress($loan->getProgress() + 1);
                }
                break;
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($loan);
        $em->flush();
        return new ApiJsonResponse(0, 'add success', $loan->toArray());
    }
}
