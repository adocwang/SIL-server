<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Constant\State;
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
            return new ApiJsonResponse(2007, 'phone not exist');
        }

        $lastSms = $this->getDoctrine()->getRepository('AppBundle:Sms')->findBy(['phone' => $data['phone']], ['created' => 'desc'], 1);
        if (!empty($lastSms)) {
            $lastSms = $lastSms[0];
        }
        if (!empty($lastSms) || $lastSms instanceof Sms) {
            if ($lastSms->getCreated()->getTimestamp() > (time() - 60)) {
                return new ApiJsonResponse(2004, 'to many sms to one phone');
            }
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($newSms);
        $em->flush();
        return new ApiJsonResponse(0, 'send success');
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
            return new ApiJsonResponse(1003, 'need phone and code');
        }
        $sms = $this->getDoctrine()->getRepository('AppBundle:Sms')->findBy(
            ['phone' => $data['phone'], 'code' => $data['code']], ['id' => 'desc'], 1
        );

        if (empty($sms)) {
            return new ApiJsonResponse(2005, 'sms code error');
        }
        $sms = $sms[0];
        if (!$sms instanceof Sms || strcmp($sms->getType(), 'login') !== 0) {
            return new ApiJsonResponse(2005, 'sms code type error');
        }
        if ($sms->getUsed() != null) {
            return new ApiJsonResponse(2006, 'sms used');
        }
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['phone' => $data['phone']]);
        if (empty($user) || !($user instanceof User)) {
            return new ApiJsonResponse(2007, 'user not exist');
        }
        $user->setPlatform($request->getExtra('platform'));
        $loginResult = $this->loginProcess($user);
        if (!$loginResult) {
            return new ApiJsonResponse(500, 'login process error');
        }
        $sms->setUsed(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($sms);
        try {
            $em->flush();
        } catch (Exception $e) {
            return new ApiJsonResponse(500, 'mark code used error');
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
            return new ApiJsonResponse(1003, 'need phone and password');
        }
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['phone' => $data['phone']]);
        if (empty($user) || !($user instanceof User)) {
            return new ApiJsonResponse(2007, 'user not exist');
        }
        if (!$user->checkPassword($data['password'])) {
            return new ApiJsonResponse(2003, 'password error');
        }
        $user->setPlatform($request->getExtra('platform'));
        $loginResult = $this->loginProcess($user);
        if (!$loginResult) {
            return new ApiJsonResponse(500, 'login process error');
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
        } catch (Exception $e) {
            return null;
        }
        return $user;
    }
}
