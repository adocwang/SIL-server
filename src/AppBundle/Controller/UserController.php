<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Constant\State;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
    /**
     * 用户state 0:未激活,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="用户",
     *     description="获取用户列表",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"},
     *         {"name"="page_limit", "dataType"="integer", "required"=false, "description"="每页size"},
     *         {"name"="phone", "dataType"="string", "required"=false, "description"="手机号码"},
     *         {"name"="true_name", "dataType"="string", "required"=false, "description"="真实姓名"},
     *         {"name"="bank_name", "dataType"="string", "required"=false, "description"="机构名称(非管理员用户会自动通过当前用户的机构覆盖这个字段)"},
     *         {"name"="role_en_name", "dataType"="string", "required"=false, "description"="角色的英文名称"},
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
     *         3001="角色不存在",
     *     }
     * )
     *
     * @Route("/user/list")
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
        if (!$nowUser->getRole()->isRole(Role::ROLE_ADMIN) && !$nowUser->getRole()->isRole(Role::ROLE_PRESIDENT)) {
            return new ApiJsonResponse(407, 'no permission');
        }

        if (!$nowUser->getRole()->isRole(Role::ROLE_ADMIN)) {
            $data['bank'] = $nowUser->getBank();
        }
        $pageLimit = $this->getParameter('page_limit');
        if (!empty($data['page_limit']) && $data['page_limit'] > 0) {
            $pageLimit = $data['page_limit'];
        }

        if (!empty($data['role_en_name'])) {
            $role = Role::createRole($data['role_en_name']);
            if (empty($role)) {
                return new ApiJsonResponse(3001, '角色不存在');
            }
            $data['role'] = Role::getRoleExpand($role);
        }


        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\User $user
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:User')->listPage($data['page'], $pageLimit, $data);
        $users = [];
        foreach ($pageData['data'] as $user) {
            $users[] = $user->getOtherArr();
        }
        return new ApiJsonResponse(0, 'ok', [
            'count' => $pageData['count'],
            'page_limit' => $pageLimit,
            'page_count' => $pageData['pageCount'],
            'users' => $users
        ]);
    }

    /**
     * 用户state 0:未激活,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="用户",
     *     description="获取一个用户",
     *     parameters={
     *         {"name"="id", "dataType"="string", "required"=false, "description"="用户id"},
     *         {"name"="phone", "dataType"="string", "required"=false, "description"="手机"}
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
     * @Route("/user/get")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function getUserAction(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['id']) && empty($data['phone'])) {
            return new ApiJsonResponse(1003, '缺少id和手机号码');
        }
        /**
         * @var \AppBundle\Repository\UserRepository $userRespository
         * @var \AppBundle\Entity\User $user
         */
        $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');
        if (!empty($data['id'])) {
            $user = $userRepository->find($data['id']);
        }
        if (!empty($data['phone'])) {
            $user = $userRepository->findOneByPhone($data['phone']);
        }
        if (empty($user)) {
            return new ApiJsonResponse(2007, '用户不存在');
        }
        if ($this->getUser()->getId() == $user->getId()) {
            return new ApiJsonResponse(0, 'ok', $user->getSelfArr());
        }
        return new ApiJsonResponse(0, 'ok', $user->getOtherArr());
    }

    /**
     *
     * 角色：
     *   管理员：ROLE_ADMIN
     *   分行行长：ROLE_BRANCH_PRESIDENT
     *   支行行长：ROLE_END_PRESIDENT
     *   客户经理：ROLE_CUSTOMER_MANAGER
     * @ApiDoc(
     *     section="用户",
     *     description="添加用户",
     *     parameters={
     *         {"name"="true_name", "dataType"="string", "required"=true, "description"="真实姓名"},
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="手机号码"},
     *         {"name"="role", "dataType"="string", "required"=true, "description"="角色"},
     *         {"name"="bank_id", "dataType"="string", "required"=false, "description"="机构id"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2006="bank不存在",
     *         2009="role不存在",
     *         2008="手机号码已存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/user/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function addUserAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['phone']) || empty($data['true_name']) || empty($data['role'])) {
            return new ApiJsonResponse(1003, '缺少手机号码或真实姓名或角色');
        }

        if ($this->getDoctrine()->getRepository('AppBundle:User')->findOneByPhone($data['phone'])) {
            return new ApiJsonResponse(2008, '手机号码已存在');
        }

        $targetUser = new User();

        $targetUser->setPhone($data['phone']);
        $targetUser->setTrueName($data['true_name']);
        $role = Role::createRole($data['role']);


        if (empty($role)) {
            return new ApiJsonResponse(2009, '角色不存在');
        }
        if (!empty($data['bank_id'])) {
            $bank = $this->getDoctrine()->getRepository('AppBundle:Bank')->find($data['bank_id']);
            if (empty($bank)) {
                return new ApiJsonResponse(1003, '机构不存在');
            }
            $targetUser->setBank($bank);
        }
        $targetUser->setRole($role);

        $targetUser->setBank($this->getUser()->getBank());
        $targetUser->setToken(md5(uniqid()));
        $targetUser->setState(State::STATE_UN_ACTIVE);
        /**
         * @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator
         */
        $validator = $this->get('validator');
        $errors = $validator->validate($targetUser);
        if (count($errors) > 0) {
            $errorsString = (string)$errors[0]->getMessage();
            return new ApiJsonResponse(1003, $errorsString);
        }

        if (!$this->isGranted('add', $targetUser)) {
            return new ApiJsonResponse(407, 'no permission');
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($targetUser);
        $em->flush();
        $this->get('app.op_logger')->logCreateAction('用户', ['手机' => $targetUser->getPhone()]);
        return new ApiJsonResponse(0, 'update success', $targetUser->getSelfArr());
    }

    /**
     *
     * 角色名称：ROLE_ADMIN,ROLE_BRANCH_PRESIDENT,ROLE_END_PRESIDENT,ROLE_CUSTOMER_MANAGER
     * 用户state 0:未激活,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="用户",
     *     description="修改用户资料",
     *     parameters={
     *         {"name"="user_id", "dataType"="integer", "required"=true, "description"="用户id"},
     *         {"name"="new_password", "dataType"="string", "required"=false, "description"="新密码"},
     *         {"name"="true_name", "dataType"="string", "required"=false, "description"="真实姓名"},
     *         {"name"="phone", "dataType"="string", "required"=false, "description"="手机号码"},
     *         {"name"="role", "dataType"="string", "required"=false, "description"="角色英文名称"},
     *         {"name"="bank_id", "dataType"="string", "required"=false, "description"="所属机构id"},
     *         {"name"="state", "dataType"="string", "required"=false, "description"="状态"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="用户不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/user/set")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setUserAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['user_id'])) {
            return new ApiJsonResponse(1003, '缺少用户id');
        }

        $targetUser = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['id' => $data['user_id']]);
        if (empty($targetUser) || !$targetUser instanceof User) {
            return new ApiJsonResponse(2007, '用户不存在');
        }

        if (!$this->isGranted('edit', $targetUser)) {
            return new ApiJsonResponse(407, 'no permission');
        }

        if (!empty($data['new_password'])) {
            $encoder = $this->get('security.password_encoder');
            $encoded = $encoder->encodePassword($targetUser, $data['new_password']);
            $targetUser->setPassword($encoded);
            if ($targetUser->getState() == State::STATE_UN_ACTIVE) {
                $targetUser->setState(State::STATE_NORMAL);
            }
        }

        if (!empty($data['true_name'])) {
            $targetUser->setTrueName($data['true_name']);
        }

        if (!empty($data['role_name'])) {
            $role = Role::createRoleByName($data['role_name']);
            if (!empty($role)) {
                $targetUser->setRole($role);
            }
        }

        if (!empty($data['role'])) {
            $role = Role::createRole($data['role']);
            if (!empty($role)) {
                $targetUser->setRole($role);
            }
        }

        if (!empty($data['bank_id'])) {
            $bank = $this->getDoctrine()->getRepository('AppBundle:Bank')->find($data['bank_id']);
            if (!empty($bank)) {
                $targetUser->setBank($bank);
            }
        }

        if (!empty($data['phone'])) {
            $targetUser->setPhone($data['phone']);
        }

        if (empty($targetUser->getToken())) {
            $targetUser->setToken(md5(uniqid()));
        }

        if (!empty($data['state'])) {
            $targetUser->setState($data['state']);
            if ($data['state'] == 3) {
                $this->get('app.op_logger')->logDeleteAction('用户', ['手机' => $targetUser->getPhone()]);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($targetUser);
        $em->flush();
        $this->get('app.op_logger')->logUpdateAction('用户', ['手机' => $targetUser->getPhone()]);
        return new ApiJsonResponse(0, 'update success', $targetUser->getSelfArr());
    }


    /**
     *
     *
     * @ApiDoc(
     *     section="用户",
     *     description="注销登录",
     *     parameters={
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/user/logout")
     * @Method("GET")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function logoutAction(JsonRequest $request)
    {

        $targetUser = $this->getUser();
        $targetUser->setToken(md5(uniqid()));
        $em = $this->getDoctrine()->getManager();
        $em->persist($targetUser);
        $em->flush();
        return new ApiJsonResponse(0, 'logout success');
    }
}
