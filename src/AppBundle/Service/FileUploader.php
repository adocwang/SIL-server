<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 15:29
 */

namespace AppBundle\Service;


use AppBundle\Entity\File;
use Symfony\Component\HttpFoundation\Request;

class FileUploader
{
    private $targetDir;

    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * @param Request $request
     * @return File|\Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function upload(Request $request)
    {
        /**
         * @var \Symfony\Component\HttpFoundation\File\UploadedFile $file
         */
        $file = $request->files->get('file');
        $fileName = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
        $fileEntity = new File();
        $fileEntity->setFileName($file->getClientOriginalName());
        $fileEntity->setPath($fileName);

        $file->move($this->targetDir, $fileName);
        return $fileEntity;
    }
}