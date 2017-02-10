<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\CMTip;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CMTipController extends Controller
{
    /**
     *
     * @ApiDoc(
     *     section="话术",
     *     description="话术列表，搜索话术",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"},
     *         {"name"="page_limit", "dataType"="integer", "required"=false, "description"="每页size"},
     *         {"name"="state", "dataType"="integer", "required"=false, "description"="1正常，3删除，默认1"},
     *         {"name"="keyword", "dataType"="string", "required"=false, "description"="搜索关键词"}
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
     * @Route("/cm_tip/list")
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
        $pageLimit = $this->getParameter('page_limit');
        if (!empty($data['page_limit']) && $data['page_limit'] > 0) {
            $pageLimit = $data['page_limit'];
        }


        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\CMTip $cmTip
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:CMTip')->listPage($data['page'], $pageLimit, $data);
        $cmTips = [];
        foreach ($pageData['data'] as $cmTip) {
            $cmTips[] = $cmTip->toArray();
        }
        return new ApiJsonResponse(0, 'ok', [
            'count' => $pageData['count'],
            'page_limit' => $pageLimit,
            'page_count' => $pageData['pageCount'],
            'cm_tips' => $cmTips
        ]);
    }

    /**
     *
     * @ApiDoc(
     *     section="话术",
     *     description="获得话术详情",
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
     *         2007="话术id不存在"
     *     }
     * )
     *
     * @Route("/cm_tip/get/{id}")
     * @Method("GET")
     * @param integer $id
     * @return ApiJsonResponse
     */
    public function getCMTipAction($id)
    {
        if (empty($id)) {
            return new ApiJsonResponse(1003, 'need id or phone');
        }
        /**
         * @var \AppBundle\Repository\CMTipRepository $cmTipRepository
         * @var \AppBundle\Entity\CMTip $cmTip
         */
        $cmTipRepository = $this->getDoctrine()->getRepository('AppBundle:CMTip');
        $cmTip = $cmTipRepository->find($id);
        if (empty($cmTip)) {
            return new ApiJsonResponse(2007, 'cm_tip not exists');
        }
        return new ApiJsonResponse(0, 'ok', [
            'id' => $cmTip->getId(),
            'title' => $cmTip->getTitle(),
            'content' => $cmTip->getContent(),
            'state' => $cmTip->getState()
        ]);
    }

    /**
     *
     *
     * @ApiDoc(
     *     section="话术",
     *     description="添加话术",
     *     parameters={
     *         {"name"="title", "dataType"="string", "required"=true, "description"="标题"},
     *         {"name"="content", "dataType"="string", "required"=true, "description"="内容"},
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
     * @Route("/cm_tip/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function addCMTipAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['title']) || empty($data['content'])) {
            return new ApiJsonResponse(1003, 'need title , content');
        }

        $cmTip = new CMTip();

        $cmTip->setTitle($data['title']);
        $cmTip->setContent($data['content']);

        if ($this->getUser()->getRole()->getRole() != "ROLE_ADMIN") {
            return new ApiJsonResponse(407, 'no permission');
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($cmTip);
        $em->flush();
        $this->get('app.op_logger')->logCreatAction('cm_tip', $cmTip->getId());
        return new ApiJsonResponse(0, 'add success', $cmTip->toArray());
    }

    /**
     *
     * @ApiDoc(
     *     section="话术",
     *     description="修改话术",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="话术id"},
     *         {"name"="title", "dataType"="string", "required"=false, "description"="标题"},
     *         {"name"="content", "dataType"="string", "required"=false, "description"="内容"},
     *         {"name"="state", "dataType"="integer", "required"=false, "description"="1正常，3删除"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="话术不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/cm_tip/set")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setCMTipAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, 'need id');
        }

        /**
         * @var \AppBundle\Repository\CMTipRepository $cmTipRepository
         * @var \AppBundle\Entity\CMTip $cmTip
         */
        $cmTipRepository = $this->getDoctrine()->getRepository('AppBundle:CMTip');
        if (!empty($data['id'])) {
            $cmTip = $cmTipRepository->find($data['id']);
        }
        if (empty($cmTip)) {
            return new ApiJsonResponse(2007, 'cm_tip not exists');
        }
        if ($this->getUser()->getRole()->getRole() != "ROLE_ADMIN") {
            return new ApiJsonResponse(407, 'no permission');
        }
        if (!empty($data['title'])) {
            $cmTip->setTitle($data['title']);
        }
        if (!empty($data['content'])) {
            $cmTip->setContent($data['content']);
        }
        if (!empty($data['state'])) {
            $cmTip->setState($data['state']);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($cmTip);
        $em->flush();
        return new ApiJsonResponse(0, 'update success', $cmTip->toArray());
    }


}
