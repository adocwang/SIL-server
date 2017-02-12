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
     * @return bool
     */
    public function sendSysMessage(User $toUser, $title, $content)
    {
        return $this->writeMessage($toUser, $title, $content, null);
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
     * @param User $fromUser
     * @return bool
     */
    private function writeMessage(User $toUser, $title, $content, User $fromUser)
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
        $this->em->persist($message);
        $this->em->flush();
        return true;
    }
}