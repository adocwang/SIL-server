<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Blacklist;
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

class BlackListController extends Controller
{
    /**
     * @ApiDoc(
     *     section="企业黑名单",
     *     description="获得黑名单列表",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="页码"},
     *         {"name"="page_limit", "dataType"="integer", "required"=false, "description"="每页size"},
     *         {"name"="name", "dataType"="string", "required"=false, "description"="根据名称搜索"},
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
     * @Route("/blacklist/list")
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

        /**
         * @var User $nowUser
         */
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)) {
            //单用户不是管理员的时候
            return new ApiJsonResponse(407, 'no permission');
        }

        $pageLimit = $this->getParameter('page_limit');
        if (!empty($data['page_limit']) && $data['page_limit'] > 0) {
            $pageLimit = $data['page_limit'];
        }

        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\Blacklist $item
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:Blacklist')->listPage($data['page'], $pageLimit, $data);
        $items = [];
        foreach ($pageData['data'] as $item) {
            $items[] = $item->toArray();
        }
        return new ApiJsonResponse(0, 'ok', [
            'count' => $pageData['count'],
            'page_limit' => $pageLimit,
            'page_count' => $pageData['pageCount'],
            'blacklist' => $items
        ]);
    }

    /**
     *
     * @ApiDoc(
     *     section="企业黑名单",
     *     description="添加黑名单",
     *     parameters={
     *         {"name"="name", "dataType"="integer", "required"=true, "description"="黑名单项目"},
     *         {"name"="source", "dataType"="integer", "required"=false, "description"="来源"},
     *         {"name"="note", "dataType"="integer", "required"=false, "description"="备注"},
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
     * @Route("/blacklist/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setBlacklistAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['name'])) {
            return new ApiJsonResponse(1003, '缺少黑名单项目名称');
        }

        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)) {
            return new ApiJsonResponse(407, 'no permission');
        }

        $blackItem = new Blacklist();
        $blackItem->setName($data['name']);
        $blackItem->setSource($data['source']);
        $blackItem->setNote($data['note']);

        $em = $this->getDoctrine()->getManager();
        $em->persist($blackItem);
        $em->flush();
        $this->get('app.op_logger')->logUpdateAction('黑名单', ['名称修改为' => $blackItem->getName()]);
        return new ApiJsonResponse(0, 'update success', $blackItem->toArray());
    }

    /**
     *
     * @ApiDoc(
     *     section="企业黑名单",
     *     description="删除黑名单",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="黑名单id"},
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
     * @Route("/blacklist/del")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function delBlacklistAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, '缺少id');
        }

        /**
         * @var Enterprise $enterprise
         */
        $blacklist = $this->getDoctrine()->getRepository('AppBundle:Blacklist')->find($data['id']);
        if (empty($blacklist) || !$blacklist instanceof Blacklist) {
            return new ApiJsonResponse(2007, '黑名单不存在');
        }

        $em = $this->getDoctrine()->getManager();
        $this->get('app.op_logger')->logDeleteAction('黑名单', ['黑名单名称' => $blacklist->getName()]);
        $em->remove($blacklist);
        $em->flush();
        return new ApiJsonResponse(0, 'delete success');
    }

}
