<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
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

class ExportController extends Controller
{

    /**
     * @ApiDoc(
     *     section="导出",
     *     description="下载用户导入模板",
     *     parameters={
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/export/user_template")
     * @Method("GET")
     * @return null
     */
    public function downloadUserTemplateAction()
    {

        /**
         * @var ExcelIOService $excelIO
         */
        $excelIO = $this->get('app.excel_io');
        $name = '用户导入模板';
        $header = ['手机', '真实姓名', '所属银行', '角色'];
        $excelWriter = $excelIO->exportExcel($name, [$header]);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        $excelWriter->save('php://output');
        exit;
//        print_r($lines);exit;


    }

    /**
     * @ApiDoc(
     *     section="导出",
     *     description="下载银行导入模板",
     *     parameters={
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/export/bank_template")
     * @Method("GET")
     * @return null
     */
    public function downloadBankTemplateAction()
    {

        /**
         * @var ExcelIOService $excelIO
         */
        $excelIO = $this->get('app.excel_io');
        $name = '机构导入模板';
        $header = ['机构名称', '上级机构名称', '机构地址', '联系电话'];
        $excelWriter = $excelIO->exportExcel($name, [$header]);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        $excelWriter->save('php://output');
        exit;
//        print_r($lines);exit;


    }

    /**
     * @ApiDoc(
     *     section="导出",
     *     description="下载投资公司导入模板",
     *     parameters={
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     }
     * )
     *
     * @Route("/export/vc_company_template")
     * @Method("GET")
     * @return null
     */
    public function downloadVCCompanyTemplateAction()
    {

        /**
         * @var ExcelIOService $excelIO
         */
        $excelIO = $this->get('app.excel_io');
        $name = '投资公司导入模板';
        $header = ['基金（投资公司）名称', '所属投资机构名称'];
        $excelWriter = $excelIO->exportExcel($name, [$header]);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        $excelWriter->save('php://output');
        exit;
//        print_r($lines);exit;


    }
}
