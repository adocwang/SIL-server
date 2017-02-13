<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\Role;
use AppBundle\Entity\Bank;
use AppBundle\Entity\User;
use AppBundle\Entity\VCCompany;
use AppBundle\Service\ExcelIOService;
use Doctrine\Common\Util\Debug;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ImportController extends Controller
{
    /**
     * @ApiDoc(
     *     section="导入",
     *     description="导入用户",
     *     parameters={
     *         {"name"="file", "dataType"="file", "required"=true, "description"="用户账号列表文件"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/import/user")
     * @Method("POST")
     * @param Request $request
     * @return ApiJsonResponse
     */
    public function importUserAction(Request $request)
    {
        /**
         * @var \AppBundle\Service\FileUploader $fileUploader
         */
        $fileUploader = $this->get('app.file_uploader');
        $file = $fileUploader->upload($request);
        if (empty($file)) {
            return new ApiJsonResponse(1003, 'file empty');
        }
        /**
         * @var ExcelIOService $excelIO
         */
        $excelIO = $this->get('app.excel_io');
        $lines = $excelIO->getExcelData($this->getParameter('uploaded_directory') . '/' . $file->getPath());
//        print_r($lines);exit;
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)) {
            return new ApiJsonResponse(407, 'no permission');
        }
        $em = $this->getDoctrine()->getManager();
        $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');
        $bankRepository = $this->getDoctrine()->getRepository('AppBundle:Bank');
        $errorLines = [];
        foreach ($lines as $line) {
            $user = new User();
            if (empty($line[0]) || empty($line[1])) {
                $errorLines[] = ['data' => $line, 'reason' => '缺少必要元素'];
                continue;
            }
            if ($userRepository->findByPhone($line[0])) {
                $errorLines[] = ['data' => $line, 'reason' => '手机号已存在'];
                continue;
            }
            $user->setPhone($line[0]);
            $user->setTrueName($line[1]);
            if (!empty($line[2])) {
                $bank = $bankRepository->findOneByName($line[2]);
                if (empty($bank)) {
                    $errorLines[] = ['data' => $line, 'reason' => '所填银行不存在'];
                    continue;
                }
                $user->setBank($bank);
            }
            if (!empty($line[3])) {
                $role = Role::createRoleByName($line[3]);
                if (empty($role)) {
                    $errorLines[] = ['data' => $line, 'reason' => '所填角色不存在'];
                    continue;
                }
                $user->setRole($role);
            }
            $user->setState(0);
            $em->persist($user);
            $em->flush();
        }
        return new ApiJsonResponse(0, 'saved', [
            "successCount" => (count($lines) - count($errorLines)),
            "errors" => $errorLines
        ]);

    }

    /**
     * @ApiDoc(
     *     section="导入",
     *     description="导入银行",
     *     parameters={
     *         {"name"="file", "dataType"="file", "required"=true, "description"="银行列表文件"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/import/bank")
     * @Method("POST")
     * @param Request $request
     * @return ApiJsonResponse
     */
    public function importBankAction(Request $request)
    {
        /**
         * @var \AppBundle\Service\FileUploader $fileUploader
         */
        $fileUploader = $this->get('app.file_uploader');
        $file = $fileUploader->upload($request);
        if (empty($file)) {
            return new ApiJsonResponse(1003, 'file empty');
        }
        /**
         * @var ExcelIOService $excelIO
         */
        $excelIO = $this->get('app.excel_io');
        $lines = $excelIO->getExcelData($this->getParameter('uploaded_directory') . '/' . $file->getPath());
//        print_r($lines);exit;
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)) {
            return new ApiJsonResponse(407, 'no permission');
        }
        $em = $this->getDoctrine()->getManager();
        $bankRepository = $this->getDoctrine()->getRepository('AppBundle:Bank');
        $errorLines = [];
        foreach ($lines as $line) {
            $bank = new Bank();
            if ($bankRepository->findByName($line[0])) {
                $errorLines[] = ['data' => $line, 'reason' => '银行已存在'];
                continue;
            }
            if (empty($line[0])) {
                $errorLines[] = ['data' => $line, 'reason' => '缺少必要元素'];
                continue;
            }
            $bank->setName($line[0]);
            if (empty($line[1])) {
                $superior = $bankRepository->find(1);
            } else {
                $superior = $bankRepository->findOneByName($line[1]);
                if (empty($superior)) {
                    $errorLines[] = ['data' => $line, 'reason' => '上级银行不存在'];
                    continue;
                }
            }
            if (!empty($superior)) {
                $bank->setSuperior($superior);
            }
            $bank->setState(0);
            $em->persist($bank);
            $em->flush();
        }
        return new ApiJsonResponse(0, 'saved', [
            "successCount" => (count($lines) - count($errorLines)),
            "errors" => $errorLines
        ]);
    }

    /**
     * @ApiDoc(
     *     section="导入",
     *     description="导入投资公司",
     *     parameters={
     *         {"name"="file", "dataType"="file", "required"=true, "description"="投资公司列表文件"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/import/vc_company")
     * @Method("POST")
     * @param Request $request
     * @return ApiJsonResponse
     */
    public function importVCCompanyAction(Request $request)
    {
        /**
         * @var \AppBundle\Service\FileUploader $fileUploader
         */
        $fileUploader = $this->get('app.file_uploader');
        $file = $fileUploader->upload($request);
        if (empty($file)) {
            return new ApiJsonResponse(1003, 'file empty');
        }
        /**
         * @var ExcelIOService $excelIO
         */
        $excelIO = $this->get('app.excel_io');
        $lines = $excelIO->getExcelData($this->getParameter('uploaded_directory') . '/' . $file->getPath());
//        print_r($lines);exit;
        if (!$this->getUser()->getRole()->isRole(Role::ROLE_ADMIN)) {
            return new ApiJsonResponse(407, 'no permission');
        }
        $em = $this->getDoctrine()->getManager();
        $vcCompanyRepository = $this->getDoctrine()->getRepository('AppBundle:VCCompany');
        $errorLines = [];
        foreach ($lines as $line) {
            $VCCompany = new VCCompany();
            if (empty($line[0]) || empty($line[0])) {
                $errorLines[] = ['data' => $line, 'reason' => '缺少必要元素'];
                continue;
            }
            if ($vcCompanyRepository->findByName($line[0])) {
                $errorLines[] = ['data' => $line, 'reason' => '投资公司已存在'];
                continue;
            }
            $VCCompany->setName($line[0]);
            $VCCompany->setVcName($line[1]);
            $em->persist($VCCompany);
            $em->flush();
        }
        return new ApiJsonResponse(0, 'saved', [
            "successCount" => (count($lines) - count($errorLines)),
            "errors" => $errorLines
        ]);
    }
}
