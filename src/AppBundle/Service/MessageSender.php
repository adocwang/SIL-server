<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 15:29
 */

namespace AppBundle\Service;


use AppBundle\Entity\Message;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class MessageSender
{
    private $tokenStorage;
    private $em;

    public function __construct(TokenStorage $tokenStorage, EntityManager $em)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    /**
     * @param $toUser User
     * @param $title string
     * @param $content string
     * @param array $type
     * @return bool
     */
    public function sendSysMessage(User $toUser, $title, $content, $type = [])
    {
        return $this->writeMessage($toUser, $title, $content, $type, null);
    }

    /**
     * @param $toUser User
     * @param $title string
     * @param $content string
     * @return bool
     */
    public function sendUserMessage(User $toUser, $title, $content)
    {
        return $this->writeMessage($toUser, $title, $content, $this->tokenStorage->getToken()->getUser());
    }

    /**
     * @param User $toUser
     * @param $title
     * @param $content
     * @param $type array
     * @param User $fromUser
     * @return bool
     */
    private function writeMessage(User $toUser, $title, $content, $type, User $fromUser = null)
    {
        if (empty($toUser) || !$toUser instanceof User) {
            return false;
        }

        $message = new Message();
        $message->setTitle($title);
        $message->setToUser($toUser);
        $message->setContent($content);
        if (!empty($fromUser)) {
            $message->setFromUser($fromUser);
        }
        $message->setType($type);
        $this->em->persist($message);
        $this->em->flush();
        if ($toUser->getPlatform() == 'ios') {
            PushService::pushIos($title, $type, $toUser->getPhone());
        } else {
            PushService::pushMessage($title, $content, $type, $toUser->getPhone());
        }
        return true;
    }
}