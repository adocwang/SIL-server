<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/26/17
 * Time: 04:29
 */

namespace AppBundle;

use Symfony\Component\HttpFoundation\Request;

class JsonRequest extends Request
{
    private $data = null;

    public function initialize(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);
        if (!empty($content)) {
            $this->data = json_decode($content, true);
        }
    }

    public function getData()
    {
        if ($this->data == null) {
            $content = parent::getContent();
            if (!empty($content)) {
                $this->data = json_decode($content, true);
            }
            if (empty($this->data)) {
                $this->data = $this->query->all();
            }
        }
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getExtra($key = '')
    {
        $extra = $this->headers->get('extra');
        if (!empty($extra)) {
            $extra = json_decode($extra, true);
            if (empty($key)) {
                return $extra;
            }
            if (!empty($extra[$key])) {
                return $extra[$key];
            } else {
                return '';
            }
        }
        return [];
    }
}