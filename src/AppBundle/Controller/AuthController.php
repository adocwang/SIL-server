<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Sms;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;


class AuthController extends Controller
{
    /**
     * @ApiDoc(
     *     section="auth",
     *     description="发送登录短信验证码",
     *     parameters={
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="手机号码"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token""=""iamsuperman""}"
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
     * @return JsonResponse
     */
    public function sendSmsAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
        if (empty($data['phone'])) {
            return new JsonResponse(['code' => 1003, 'info' => 'need phone', 'data' => new \stdClass()]);
        }
        $phone = $data['phone'];
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneByPhone($phone);
        if (empty($user)) {
            return new JsonResponse(['code' => 2007, 'info' => 'phone not exist', 'data' => new \stdClass()]);
        }

        $lastSms = $this->getDoctrine()->getRepository('AppBundle:Sms')->findBy(['phone' => $phone], ['created' => 'desc'], 1);
        if (!empty($lastSms)) {
            $lastSms = $lastSms[0];
        }
        if (!empty($lastSms) || $lastSms instanceof Sms) {
            if ($lastSms->getCreated()->getTimestamp() > (time() - 60)) {
                return new JsonResponse(['code' => 2004, 'info' => 'to many sms to one phone', 'data' => new \stdClass()]);
            }
        }

        $newSms = new Sms();
        $newSms->setPhone($phone);
        $code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
        if (in_array($this->container->get('kernel')->getEnvironment(), ['dev'], true)) {
            $code = '6666';
        }
        $newSms->setCode($code);
        $newSms->setType('login');
        $newSms->setCreated(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($newSms);
        $em->flush();
        return new JsonResponse(['code' => 0, 'info' => 'send success', 'data' => new \stdClass()]);
    }

    /**
     * @ApiDoc(
     *     section="auth",
     *     description="通过短信登录",
     *     parameters={
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="手机号码"},
     *         {"name"="code", "dataType"="string", "required"=true, "description"="验证码"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token""=""iamsuperman""}"
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
     * @return JsonResponse
     */
    public function smsLoginAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
        if (empty($data['phone']) || empty($data['code'])) {
            return new JsonResponse(['code' => 1003, 'info' => 'need phone and code', 'data' => new \stdClass()]);
        }
        $sms = $this->getDoctrine()->getRepository('AppBundle:Sms')->findBy(
            ['phone' => $data['phone'], 'code' => $data['code']], ['created' => 'desc'], 1
        );

        if (empty($sms) || !$sms[0] instanceof Sms || strcmp($sms[0]->getType(), 'login') !== 0) {
            return new JsonResponse(['code' => 2005, 'info' => 'sms code error', 'data' => new \stdClass()]);
        }
        $sms = $sms[0];
        if ($sms->getUsed() != null) {
            return new JsonResponse(['code' => 2006, 'info' => 'sms used', 'data' => new \stdClass()]);
        }
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['phone' => $data['phone']]);
        if (empty($user) || !($user instanceof User)) {
            return new JsonResponse(['code' => 2007, 'info' => 'user not exist', 'data' => new \stdClass()]);
        }
        $loginResult = $this->loginProcess($user);
        if (!$loginResult) {
            return new JsonResponse(['code' => 500, 'info' => 'login process error', 'data' => new \stdClass()]);
        }
        $sms->setCreated(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($sms);
        try {
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['code' => 500, 'info' => 'save code used error!', 'data' => new \stdClass()]);
        }
        return new JsonResponse(['code' => 0, 'info' => 'login success', 'data' => $loginResult->getSelfArr()]);

    }

    /**
     * @ApiDoc(
     *     section="auth",
     *     description="通过密码登录",
     *     parameters={
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="手机号码"},
     *         {"name"="password", "dataType"="string", "required"=true, "description"="密码"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token""=""iamsuperman""}"
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
     * @return JsonResponse
     */
    public function passwordLoginAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
        if (empty($data['phone']) || empty($data['password'])) {
            return new JsonResponse(['code' => 1003, 'info' => 'need phone and password', 'data' => new \stdClass()]);
        }
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['phone' => $data['phone']]);
        if (empty($user) || !($user instanceof User)) {
            return new JsonResponse(['code' => 2007, 'info' => 'user not exist', 'data' => new \stdClass()]);
        }

        $plainPassword = $data['password'];

        if (!password_verify($data['password'], $user->getPassword())) {
            return new JsonResponse(['code' => 2003, 'info' => 'password error', 'data' => new \stdClass()]);
        }

        $loginResult = $this->loginProcess($user);
        if (!$loginResult) {
            return new JsonResponse(['code' => 500, 'info' => 'login process error', 'data' => new \stdClass()]);
        }
        return new JsonResponse(['code' => 0, 'info' => 'login success', 'data' => $loginResult->getSelfArr()]);

    }

    /**
     * @param User $user
     * @return User|null
     */
    public function loginProcess(User $user)
    {
        $token = md5(random_bytes(32));
        $user->setToken($token);
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
