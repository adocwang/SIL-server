<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Role;
use AppBundle\Entity\Bank;
use AppBundle\Entity\User;
use AppBundle\JsonRequest;
use Doctrine\ORM\QueryBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BankController extends Controller
{

    /**
     * state 1:正常,3:已删除
     * @ApiDoc(
     *     section="机构",
     *     description="获取机构列表",
     *     parameters={
     *         {"name"="name", "dataType"="string", "required"=true, "description"="名称"},
     *         {"name"="state", "dataType"="string", "required"=true, "description"="状态"},
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
     * @Route("/bank/list")
     * @Method("POST|GET")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function list(JsonRequest $request)
    {
        /**
         * @var User $nowUser
         */
        $data = $request->getData();
        $nowUser = $this->getUser();
        /**
         * @var QueryBuilder $queryBuilder
         * @var Bank $bank
         */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('a')
            ->from('AppBundle:Bank', 'a');
        if (!empty($data['name'])) {
            $queryBuilder->andWhere('a.name like :name');
            $queryBuilder->setParameter('name', '%' . $data['name'] . '%');
        }
        if (!empty($data['state'])) {
            $queryBuilder->andWhere('a.state = :state');
            $queryBuilder->setParameter('state', $data['state']);
        }
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)) {
            $queryBuilder->andWhere('a.superior = :superior');
            $queryBuilder->setParameter('superior', $nowUser->getBank());
            $queryBuilder->andWhere('a.state = 1');
        }
        $banks = $queryBuilder->getQuery()->getResult();
        array_push($banks, $nowUser->getBank());
        $bankList = [];
        foreach ($banks as $bank) {
            $bankList[] = $bank->toArrayNoSubordinate();
        }
        return new ApiJsonResponse(0, 'ok', $bankList);
    }

    /**
     * 添加机构
     * @ApiDoc(
     *     section="机构",
     *     description="添加机构",
     *     parameters={
     *         {"name"="name", "dataType"="string", "required"=true, "description"="机构"},
     *         {"name"="address", "dataType"="string", "required"=true, "description"="地址"},
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="电话"},
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
     * @Route("/bank/add")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function addBankAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['name'])) {
            return new ApiJsonResponse(1003, '缺少名称');
        }
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)
            && !$this->getUser()->getRole()->isRole(Role::ROLE_BRANCH_PRESIDENT)
            && !$this->getUser()->getRole()->isRole(Role::ROLE_CHANNEL_MANAGER)
        ) {
            return new ApiJsonResponse(407, '无权限');
        }

        $bank = new Bank();
        $bank->setName($data['name']);
        if (!empty($data['address'])) {
            $bank->setAddress($data['address']);
        }
        if (!empty($data['phone'])) {
            $bank->setPhone($data['phone']);
        }
        $bank->setSuperior($this->getUser()->getBank());

        $em = $this->getDoctrine()->getManager();
        $em->persist($bank);
        $em->flush();

        $this->get('app.op_logger')->logCreateAction('银行', ['名称' => $bank->getName()]);
        return new ApiJsonResponse(0, 'add success', $bank->toArray());
    }

    /**
     * 修改机构
     * state:1正常, 3已删除
     * @ApiDoc(
     *     section="机构",
     *     description="修改机构",
     *     parameters={
     *         {"name"="id", "dataType"="integer", "required"=true, "description"="id"},
     *         {"name"="name", "dataType"="string", "required"=false, "description"="机构"},
     *         {"name"="address", "dataType"="string", "required"=false, "description"="地址"},
     *         {"name"="phone", "dataType"="string", "required"=false, "description"="电话"},
     *         {"name"="coordinates", "dataType"="string", "required"=false, "description"="坐标"},
     *         {"name"="state", "dataType"="string", "required"=false, "description"="状态"},
     *         {"name"="superior_id", "dataType"="integer", "required"=false, "description"="上级id"}
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
     * @Route("/bank/set")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function setBankAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
//        print_r($data);exit;
        if (empty($data['id'])) {
            return new ApiJsonResponse(1003, '缺少机构id');
        }
        $bankRepository = $this->getDoctrine()->getRepository('AppBundle:Bank');
        if (empty($bankRepository->find($data['id']))) {
            return new ApiJsonResponse(2007, '机构不存在');
        }
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN) && !$this->getUser()->getRole()->isRole(Role::ROLE_BRANCH_PRESIDENT)) {
            return new ApiJsonResponse(407, 'no permission');
        }
        /**
         * @var Bank $bank
         */
        $bank = $bankRepository->find($data['id']);
        if (!empty($data['name'])) {
            $bank->setName($data['name']);
        }
        if (!empty($data['address'])) {
            $bank->setAddress($data['address']);
        }
        if (!empty($data['phone'])) {
            $bank->setPhone($data['phone']);
        }
        if (!empty($data['coordinates'])) {
            $bank->setCoordinates($data['coordinates']);
        }

        if (!empty($data['state'])) {
            $bank->setState($data['state']);
            if ($data['state'] == 3) {
                $this->get('app.op_logger')->logDeleteAction('银行', ['名称' => $bank->getName()]);
            }
        }

        if (!empty($data['superior_id'])) {
            /**
             * @var Bank $superior
             */
            $superior = $bankRepository->find($data['superior_id']);
            if (empty($superior) || $superior->getState() != 1) {//上级机构不存在或者被冻结
                return new ApiJsonResponse(2007, '上级机构不存在');
            }
            $bank->setSuperior($superior);
            $right = false;
            $nowUserBank = $this->getUser()->getBank();
            $tmpSuperior = $superior;
            do {
                if (!empty($tmpSuperior)) {
                    if ($tmpSuperior == $nowUserBank) {
                        $right = true;
                        break;
                    }
                } else {
                    break;
                }
            } while ($tmpSuperior = $tmpSuperior->getSuperior());
            if (!$right) {
                return new ApiJsonResponse(407, 'no permission');
            }
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($bank);
        $em->flush();
        $this->get('app.op_logger')->logUpdateAction('银行', ['名称' => $bank->getName()]);
        return new ApiJsonResponse(0, 'add success', $bank->toArray());
    }
}
