<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 14:22
 */

namespace AppBundle\Service;

use Liuggio\ExcelBundle\Factory;

class ExcelIOService
{
    /**
     * @var \Liuggio\ExcelBundle\Factory $phpexcel
     */
    private $phpexcel;

    public function __construct(Factory $phpexcel)
    {
        $this->phpexcel = $phpexcel;
    }

    public function getExcelData($filePath, $startLine = 2)
    {
        if (!file_exists($filePath)) {
            return null;
        }
        $phpExcelObject = $this->phpexcel->createPHPExcelObject($filePath);
        $sheet = $phpExcelObject->getSheet(0);
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumn = $sheet->getHighestDataColumn(); // 取得总列数

        /** 循环读取每个单元格的数据 */
        $lines = [];
        for ($row = $startLine; $row <= $highestRow; $row++) {//行数是以第1行开始
            $dataset = [];
            for ($column = 'A'; $column <= $highestColumn; $column++) {//列数是以A列开始
                $dataset[] = $sheet->getCell($column . $row)->getValue();
            }
            $lines[] = $dataset;
        }

        return $lines;
    }

    /**
     * @param $name string
     * @param $lines array
     * @return \PHPExcel_Writer_IWriter
     */
    public function exportExcel($name, $lines)
    {
        $objPHPExcel = $this->phpexcel->createPHPExcelObject();
        $objPHPExcel->getProperties()->setCreator("project_sil")
            ->setLastModifiedBy("project_sil")
            ->setTitle($name)
            ->setSubject($name)
            ->setDescription($name);

        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $objWorksheet->fromArray($lines);
        $objPHPExcel->getActiveSheet()->setTitle($name);
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = $this->phpexcel->createWriter($objPHPExcel, 'Excel2007');
        return $objWriter;
    }
}