<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Message;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MessageController extends Controller
{
    /**
     * @ApiDoc(
     *     section="message",
     *     description="获取消息",
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
     *         2007="用户不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/message/{id}")
     * @Method("GET")
     * @param integer $id
     * @return ApiJsonResponse
     */
    public function getAction($id)
    {
        /**
         * @var Message $message
         */
        if (empty($id)) {
            return new ApiJsonResponse(1003, 'need id');
        }
        $message = $this->getDoctrine()->getRepository('AppBundle:Message')->find($id);
        if (empty($message)) {
            return new ApiJsonResponse(2007, 'message not exists');
        }
        if ($message->getToUser()->getId() != $this->getUser()->getId()) {
            return new ApiJsonResponse(407);
        }
        return new ApiJsonResponse(0, 'ok', $message->getArr());
    }

    /**
     * 用户state 0:未激活,1:正常,2:已冻结,3:已删除
     * @ApiDoc(
     *     section="message",
     *     description="获取消息列表",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/message/list")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function listAction(JsonRequest $request)
    {
        $data = $request->getData();
        if (empty($data['page']) || $data['page'] < 1) {
            $data['page'] = 1;
        }
        $data['to_user'] = $this->getUser();
        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\Message $message
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:Message')->listPage($data['page'],
            $this->getParameter('page_count'), $data);
        $messages = [];
        foreach ($pageData['data'] as $message) {
            $messages[] = $message->getArr();
        }
        return new ApiJsonResponse(0, 'ok', ['page_count' => $pageData['pageCount'], 'messages' => $messages]);
    }

    /**
     * 可用于设置已读，删除消息
     * state:0,未读，1已读，2已删除
     * @ApiDoc(
     *     section="message",
     *     description="设置消息状态",
     *     parameters={
     *         {"name"="id", "dataType"="string", "required"=true, "description"="消息id"},
     *         {"name"="state", "dataType"="string", "required"=true, "description"="消息状态"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="消息不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/message/delete")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function deleteAction(JsonRequest $request)
    {
        /**
         * @var Message $message
         */
        $data = $request->getData();
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, 'need id');
        }
        $message = $this->getDoctrine()->getRepository('AppBundle:Message')->find($data['id']);
        if (empty($message)) {
            return new ApiJsonResponse(2007, 'message not exists');
        }
        if ($message->getToUser()->getId() != $this->getUser()->getId()) {
            return new ApiJsonResponse(407);
        }
        if (!in_array($data['state'], [0, 1, 2])) {
            $data['state'] = 1;
        }
        $message->setState($data['state']);
        $em = $this->getDoctrine()->getManager();
        $em->persist($message);
        $em->flush();
        return new ApiJsonResponse(0);
    }
}
