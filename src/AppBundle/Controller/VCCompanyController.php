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
     * @Method("GET")
     * @return ApiJsonResponse
     */
    public function list()
    {
        /**
         * @var User $nowUser
         */
        $nowUser = $this->getUser();

        if ($nowUser->getRole()->getRole() != 'ROLE_ADMIN') {
            return new ApiJsonResponse('403');
        }

        $cvCompanyList = $this->getDoctrine()->getRepository('AppBundle:VCCompany')->findAll();
        $list = [];
        foreach ($cvCompanyList as $VCCompany) {
            $list[] = $VCCompany->toArray();
        }
        return new ApiJsonResponse(0, 'ok', $list);
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
            return new ApiJsonResponse(1003, 'need name');
        }

        if (!in_array($this->getUser()->getRole()->getRole(), ['ROLE_ADMIN'])) {
            return new ApiJsonResponse(407, 'no permission');
        }

        $vcCompany = new VCCompany();
        $vcCompany->setName($data['name']);
        $vcCompany->setVcName($data['vc_name']);

        $em = $this->getDoctrine()->getManager();
        $em->persist($vcCompany);
        $em->flush();
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
            return new ApiJsonResponse(1003, 'need id');
        }
        $VCCompanyRepository = $this->getDoctrine()->getRepository('AppBundle:VCCompany');
        $VCCompany = $VCCompanyRepository->find($data['id']);
        if (empty($VCCompany)) {
            return new ApiJsonResponse(2007, 'bank not exist');
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
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($VCCompany);
        $em->flush();
        return new ApiJsonResponse(0, 'add success', $VCCompany->toArray());
    }
}
