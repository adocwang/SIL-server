<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/3/17
 * Time: 00:20
 */

namespace AppBundle\Listener;


use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->getResponse()->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept, extra, Cache-Control, X-Requested-With');
        $event->getResponse()->headers->set('Access-Control-Allow-Origin', '*');
        $event->getResponse()->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, OPTIONS');
    }
}