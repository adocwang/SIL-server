<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Bank;
use AppBundle\Entity\User;
use AppBundle\Entity\VCCompany;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class VCCompanyController extends Controller
{

    /**
     * state 1:正常,3:已删除
     * 这个接口会列出所有的VC机构
     * @ApiDoc(
     *     section="投资机构",
     *     description="获取vc机构的基金或者投资机构列表",
     *     parameters={
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
     * @Route("/vc_company/list")
     * @Method("POST|GET")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function list(JsonRequest $request)
    {
        /**
         * @var User $nowUser
         */
        $nowUser = $this->getUser();
        $data = $request->getData();

        if ($nowUser->getRole()->getRole() != 'ROLE_ADMIN') {
            return new ApiJsonResponse('403');
        }
        if (empty($data['page']) || $data['page'] < 1) {
            $data['page'] = 1;
        }
        $pageLimit = $this->getParameter('page_limit');
        if (!empty($data['page_limit']) && $data['page_limit'] > 0) {
            $pageLimit = $data['page_limit'];
        }

        /**
         * @var \Doctrine\ORM\Tools\Pagination\Paginator $paginator
         * @var \AppBundle\Entity\VCCompany $vcCompany
         */
        $pageData = $this->getDoctrine()->getRepository('AppBundle:VCCompany')->listPage($data['page'], $pageLimit, $data);
        $vcCompanies = [];
        foreach ($pageData['data'] as $vcCompany) {
            $vcCompanies[] = $vcCompany->toArray();
        }
//        return new ApiJsonResponse(0, 'ok', $vcCompanies);
        return new ApiJsonResponse(0, 'ok', [
            'count' => $pageData['count'],
            'page_limit' => $pageLimit,
            'page_count' => $pageData['pageCount'],
            'vc_companies' => $vcCompanies
        ]);
    }

    /**
     * 添加投资公司
     * @ApiDoc(
     *     section="投资机构",
     *     description="添加投资公司",
     *     parameters={
     *         {"name"="name", "dataType"="string", "required"=true, "description"="公司名称"},
     *         {"name"="vc_name", "dataType"="string", "required"=true, "description"="所属投资机构名称"}
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="相同 name 的 VCCompany 已存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/vc_company/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function addVCCompanyAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['name']) || empty($data['vc_name'])) {
            return new ApiJsonResponse(1003, '缺少名称');
        }

        if (!in_array($this->getUser()->getRole()->getRole(), ['ROLE_ADMIN'])) {
            return new ApiJsonResponse(407, 'no permission');
        }

        if ($this->getDoctrine()->getRepository('AppBundle:VCCompany')->findByName($data['name'])) {
            return new ApiJsonResponse(2007, '同名公司已存在');
        }
        $vcCompany = new VCCompany();
        $vcCompany->setName($data['name']);
        $vcCompany->setVcName($data['vc_name']);

        $em = $this->getDoctrine()->getManager();
        $em->persist($vcCompany);
        $em->flush();

        $this->get('app.op_logger')->logCreateAction('投资公司', ['名称' => $vcCompany->getName()]);
        return new ApiJsonResponse(0, 'add success', $vcCompany->toArray());
    }

    /**
     * 修改投资公司
     * state:1正常, 2冻结，3已删除
     * @ApiDoc(
     *     section="投资机构",
     *     description="修改投资公司",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="id"},
     *         {"name"="name", "dataType"="string", "required"=false, "description"="名称"},
     *         {"name"="vc_name", "dataType"="string", "required"=false, "description"="所属机构名称"},
     *         {"name"="state", "dataType"="string", "required"=false, "description"="状态"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="vc_company不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/vc_company/set")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setVCCompanyAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, '缺少id');
        }
        $VCCompanyRepository = $this->getDoctrine()->getRepository('AppBundle:VCCompany');
        /**
         * @var VCCompany $VCCompany
         */
        $VCCompany = $VCCompanyRepository->find($data['id']);
        if (empty($VCCompany)) {
            return new ApiJsonResponse(2007, '机构不存在');
        }
        if (!in_array($this->getUser()->getRole()->getRole(), ['ROLE_ADMIN'])) {
            return new ApiJsonResponse(407, 'no permission');
        }

        if (!empty($data['name'])) {
            $VCCompany->setName($data['name']);
        }
        if (!empty($data['vc_name'])) {
            $VCCompany->setName($data['vc_name']);
        }

        if (!empty($data['state'])) {
            $VCCompany->setState($data['state']);
            if ($data['state'] == 3) {
                $this->get('app.op_logger')->logDeleteAction('投资公司', ['名称' => $VCCompany->getName()]);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($VCCompany);
        $em->flush();
        $this->get('app.op_logger')->logUpdateAction('投资公司', ['名称' => $VCCompany->getName()]);
        return new ApiJsonResponse(0, 'add success', $VCCompany->toArray());
    }
}
