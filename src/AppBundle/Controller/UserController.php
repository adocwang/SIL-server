<?php

namespace AppBundle\Controller;

use AppBundle\JsonRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller
{

    public function setPasswordAction(JsonRequest $request)
    {
        $data = $request->getData();
        //check notnull data fields
        if (empty($data['phone']) && empty($data['id'])) {
            return new JsonResponse(['code' => 1003, 'info' => 'need phone or id', 'data' => new \stdClass()]);
        }
        if (empty($data['password'])) {
            return new JsonResponse(['code' => 1003, 'info' => 'need  password', 'data' => new \stdClass()]);
        }

    }
}
