<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/19/17
 * Time: 02:31
 */

namespace AppBundle\Service;


use JPush\Client as JPushClient;
use JPush\Exceptions\APIRequestException;
use Symfony\Component\Config\Definition\Exception\Exception;
use xmpush\Builder as MiPushBuilder;
use xmpush\Constants as MiPushConstants;
use xmpush\Sender as MiPushSender;

class PushService
{
    public static function pushMessage($title, $content, $data, $aliasList = 'all')
    {
        self::pushAndroid($title, $content, $data, $aliasList);
        self::pushIos($title, $data, $aliasList);
    }

    public static function pushIos($title, $payload, $aliasList)
    {
        $app_key = "fb3e46f0c4cb7ee18df527b7";
        $master_secret = "59d491b4dc008817216957bf";
        $client = new JPushClient($app_key, $master_secret, null);
        $push = $client->push();
        $push->setOptions(null, null, null, true);
        $push->setPlatform('ios');
        if ($aliasList == 'all') {
            $push->addAllAudience();
        } else {
            $push->addAlias($aliasList);
        }
        $push->iosNotification($title, [
            'extras' => ['type' => $payload]
        ]);
        try {
            $res = $push->send();
        } catch (APIRequestException $e) {
            return false;
        }
        if ($res['body']['msg_id']) {
            return true;
        }
        return false;
//        print_r($push->send());
//        exit;
    }

    public static function pushAndroid($title, $desc, $payload, $aliasList)
    {
        $secret = 'NM5Nramps6XtKkaPT5tXjw==';
        $package = 'com.siliconvalleybank';
        MiPushConstants::setPackage($package);
        MiPushConstants::setSecret($secret);

        $sender = new MiPushSender();

// message1 演示自定义的点击行为
        $message1 = new MiPushBuilder();
        $message1->title($title);  // 通知栏的title
        $message1->description($desc); // 通知栏的descption
        $message1->passThrough(0);  // 这是一条通知栏消息，如果需要透传，把这个参数设置成1,同时去掉title和descption两个参数
        $message1->payload(json_encode(['type' => $payload])); // 携带的数据，点击后将会通过客户端的receiver中的onReceiveMessage方法传入。
        $message1->extra(MiPushBuilder::notifyForeground, 1); // 应用在前台是否展示通知，如果不希望应用在前台时候弹出通知，则设置这个参数为0
        $message1->notifyId(2); // 通知类型。最多支持0-4 5个取值范围，同样的类型的通知会互相覆盖，不同类型可以在通知栏并存
        $message1->notifyType(-1);
        $message1->build();
        if ($aliasList == 'all') {
            @$res = $sender->broadcastAll($message1);

        } else {
            @$res = $sender->sendToAliases($message1, $aliasList);
//            print_r($res->getRaw());exit;
        }
        if (!empty($res) && $res->getErrorCode() == 0) {
            return true;
        }
        return false;
    }
}