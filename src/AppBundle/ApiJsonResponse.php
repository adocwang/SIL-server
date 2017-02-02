<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/29/17
 * Time: 19:47
 */

namespace AppBundle;


use Symfony\Component\HttpFoundation\JsonResponse;

class ApiJsonResponse extends JsonResponse
{
    public function __construct($code = 0, $info = '', $data = null)
    {
        if (empty($data)) {
            $data = new \stdClass();
        }
        if ($info == '') {
            switch ($code) {
                case 0:
                    $info = 'ok';
                    break;
                case 400:
                    $info = 'request format error';
                    break;
                case 401:
                    $info = 'api not exists';
                    break;
                case 402:
                    $info = 'extra error';
                    break;
                case 403:
                    $info = 'parameters not enough';
                    break;
                case 404:
                    $info = 'parameters error';
                    break;
                case 405:
                    $info = 'need token';
                    break;
                case 406:
                    $info = 'token error';
                    break;
                case 500:
                    $info = 'unexpected server error';
                    break;
                case 501:
                    $info = 'meet a line should never be reached';
                    break;
            }
        }
        $return = [
            'code' => $code,
            'info' => $info,
            'data' => $data
        ];
        parent::__construct($return, 200);
    }
}