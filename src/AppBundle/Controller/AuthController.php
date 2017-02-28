<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Constant\State;
use AppBundle\Entity\Role;
use AppBundle\Entity\Sms;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;


class AuthController extends Controller
{
    /**
     * @ApiDoc(
     *     section="身份认证",
     *     description="角色列表",
     *     parameters={
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman""}"
     *         }
     *     },
     *     statusCodes={
     *     }
     * )
     *
     * @Route("/auth/role_list")
     * @Method("GET")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function getRoleListAction(JsonRequest $request)
    {
        $roleList = Role::getRoleList();
        return new ApiJsonResponse(0, 'ok', $roleList);
    }

    /**
     * @ApiDoc(
     *     section="身份认证",
     *     description="发送登录短信验证码",
     *     parameters={
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="手机号码"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman""}"
     *         }
     *     },
     *     statusCodes={
     *         2007="用户不存在",
     *         2004="发送密码过于频繁",
     *     }
     * )
     *
     * @Route("/auth/send_login_sms")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function sendSmsAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields

        $newSms = new Sms();
        $newSms->setPhone($data['phone']);
        $code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
        if (in_array($this->container->get('kernel')->getEnvironment(), ['dev'], true)) {
            $code = '6666';
        }
        $newSms->setCode($code);
        $newSms->setType('login');
        $newSms->setCreated(new \DateTime());
        /**
         * @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator
         */
        $validator = $this->get('validator');
        $errors = $validator->validate($newSms);
        if (count($errors) > 0) {
            $errorsString = (string)$errors[0]->getMessage();
            return new ApiJsonResponse(1003, $errorsString);
        }
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneByPhone($data['phone']);
        if (empty($user)) {
            return new ApiJsonResponse(2007, '手机号码不存在');
        }

        $lastSms = $this->getDoctrine()->getRepository('AppBundle:Sms')->findBy(['phone' => $data['phone']], ['created' => 'desc'], 1);
        if (!empty($lastSms)) {
            $lastSms = $lastSms[0];
        }
        if (!empty($lastSms) || $lastSms instanceof Sms) {
            if ($lastSms->getCreated()->getTimestamp() > (time() - 60)) {
                return new ApiJsonResponse(2004, '短信发送频率过高');
            }
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($newSms);
        $em->flush();
        return new ApiJsonResponse(0, '发送成功');
    }

    /**
     * @ApiDoc(
     *     section="身份认证",
     *     description="通过短信登录",
     *     parameters={
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="手机号码"},
     *         {"name"="code", "dataType"="string", "required"=true, "description"="验证码"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman""}"
     *         }
     *     },
     *     statusCodes={
     *         2005="短信验证码错误",
     *         2006="短信验证码已用",
     *         2007="用户不存在"
     *     }
     * )
     *
     * @Route("/auth/sms_login")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function smsLoginAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
        if (empty($data['phone']) || empty($data['code'])) {
            return new ApiJsonResponse(1003, '缺少手机号码或验证码');
        }
        $sms = $this->getDoctrine()->getRepository('AppBundle:Sms')->findBy(
            ['phone' => $data['phone'], 'code' => $data['code']], ['id' => 'desc'], 1
        );

        if (empty($sms)) {
            return new ApiJsonResponse(2005, '验证码错误');
        }
        $sms = $sms[0];
        if (!$sms instanceof Sms || strcmp($sms->getType(), 'login') !== 0) {
            return new ApiJsonResponse(2005, 'sms code type error');
        }
        if ($sms->getUsed() != null) {
            return new ApiJsonResponse(2006, '验证码已被使用过');
        }
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['phone' => $data['phone']]);
        if (empty($user) || !($user instanceof User)) {
            return new ApiJsonResponse(2007, '用户不存在');
        }
        $user->setPlatform($request->getExtra('platform'));
        $loginResult = $this->loginProcess($user);
        if (!$loginResult) {
            return new ApiJsonResponse(500, '登录过程中遇到服务器错误');
        }
        $sms->setUsed(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($sms);
        try {
            $em->flush();
        } catch (Exception $e) {
            return new ApiJsonResponse(500, '设置验证码状态时出错');
        }
        return new ApiJsonResponse(0, 'login success', $loginResult->getSelfArr());

    }

    /**
     * @ApiDoc(
     *     section="身份认证",
     *     description="通过密码登录",
     *     parameters={
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="手机号码"},
     *         {"name"="password", "dataType"="string", "required"=true, "description"="密码"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman""}"
     *         }
     *     },
     *     statusCodes={
     *         2007="用户不存在",
     *         2003="密码错误"
     *     }
     * )
     *
     * @Route("/auth/password_login")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function passwordLoginAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
        if (empty($data['phone']) || empty($data['password'])) {
            return new ApiJsonResponse(1003, '缺少手机号码或者密码');
        }
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['phone' => $data['phone']]);
        if (empty($user) || !($user instanceof User)) {
            return new ApiJsonResponse(2007, '用户不存在');
        }
        if (!$user->checkPassword($data['password'])) {
            return new ApiJsonResponse(2003, '密码错误');
        }
        $user->setPlatform($request->getExtra('platform'));
        $loginResult = $this->loginProcess($user);
        if (!$loginResult) {
            return new ApiJsonResponse(500, '登录过程中遇到未知错误');
        }
        return new ApiJsonResponse(0, 'login success', $loginResult->getSelfArr());

    }

    /**
     * @param User $user
     * @return User|null
     */
    public function loginProcess(User $user)
    {
        $token = md5(uniqid());
        $user->setToken($token);
        if ($user->getState() == State::STATE_UN_ACTIVE) {
            $user->setState(State::STATE_NORMAL);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        try {
            $em->flush();
            $this->get('app.op_logger')->logOtherAction('用户', '登录', ['手机号码' => $user->getPhone()], $user);
        } catch (Exception $e) {
            return null;
        }
        return $user;
    }
}
