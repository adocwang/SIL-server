<?php

namespace AppBundle\Controller;

use AppBundle\ApiJsonResponse;
use AppBundle\Entity\File;
use AppBundle\JsonRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ResourceController extends Controller
{
    /**
     * @ApiDoc(
     *     section="上传下载资源",
     *     description="上传文件",
     *     parameters={
     *         {"name"="file", "dataType"="file", "required"=true, "description"="要上传的文件"},
     *     },
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="用户不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/resource/upload")
     * @Method("POST")
     * @param JsonRequest $request
     * @return ApiJsonResponse
     */
    public function uploadAction(JsonRequest $request)
    {
        /**
         * @var \AppBundle\Service\FileUploader $fileUploader
         */
        $fileUploader = $this->get('app.file_uploader');
        $file = $fileUploader->upload($request);
        if (empty($file)) {
            return new ApiJsonResponse(1003, '文件为空');
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($file);
        $em->flush();
        return new ApiJsonResponse(0, 'ok', ['resourceId' => $file->getId()]);
    }

    /**
     * @ApiDoc(
     *     section="上传下载资源",
     *     description="获取资源",
     *     headers={
     *         {
     *             "name"="extra",
     *             "default"="{""token"":""iamsuperman:15828516285""}"
     *         }
     *     },
     *     statusCodes={
     *         1003="缺少参数",
     *         2007="用户不存在",
     *         407="无权限",
     *     }
     * )
     *
     * @Route("/resource/get/{resource_id}")
     * @Method("GET")
     * @param string $resource_id
     * @return Response|null
     */
    public function getAction($resource_id)
    {
        /**
         * @var File $file
         */
        if (empty($resource_id)) {
            return new Response('', 404);
        }

        $file = $this->getDoctrine()->getRepository('AppBundle:File')->findOneById($resource_id);

        if (empty($file)) {
            return new Response('', 404);
        }
        $absPath = $this->getParameter('uploaded_directory') . DIRECTORY_SEPARATOR . $file->getPath();
        if (!file_exists($absPath)) {
            return new Response('', 404);
        }
        $nFileSize = filesize($absPath);
//        header("Content-Disposition: attachment; filename=" . $file->getOriginalName());
        header("Content-Length: " . $nFileSize);
        header("Content-type: " . $file->getMimeType());
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $file->getUploaded()->getTimestamp()) . ' GMT');
        readfile($absPath);
        exit;
    }
}
