<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\User;
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
     *     section="import",
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
        /**
         * @var ExcelIOService $excelIO
         */
        $excelIO = $this->get('app.excel_io');
        $lines = $excelIO->getUserExcelData($this->getParameter('uploaded_directory') . '/' . $file->getPath());
//        print_r($lines);exit;
        $em = $this->getDoctrine()->getManager();
        $bankRepository = $this->getDoctrine()->getRepository('AppBundle:Bank');
        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:Role');
        $errorLines = [];
        foreach ($lines as $line) {
            $user = new User();
            if (empty($line[0]) || empty($line[1])) {
                $errorLines[] = $line;
                continue;
            }
            $user->setPhone($line[0]);
            $user->setTrueName($line[1]);
            if (!empty($line[2])) {
                $bank = $bankRepository->findOneByName($line[2]);
                if (empty($bank)) {
                    $errorLines[] = $line;
                    continue;
                }
                $user->setBank($bank);
            }
            if (!empty($line[3])) {
                $role = $roleRepository->findOneByName($line[3]);
                if (empty($role)) {
                    $errorLines[] = $line;
                    continue;
                }
                $user->setRole($role);
            }
            $user->setState(0);
            $em->persist($user);
        }
        $em->flush();
        return new ApiJsonResponse(0, 'saved', [
            "successCount" => (count($lines) - count($errorLines)),
            "errors" => $errorLines
        ]);

    }
}
