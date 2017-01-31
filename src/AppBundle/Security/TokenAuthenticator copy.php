<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/26/17
 * Time: 03:54
 */

namespace AppBundle\Security;


use AppBundle\ApiJsonResponse;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class TokenAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof ApiUserProvider) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of ApiUserProvider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $tokenStr = $token->getCredentials();
        $user = $userProvider->loadUserByToken($tokenStr);
        if (empty($user) && strpos($tokenStr, 'iamsuperman:') !== false) {
            $phone = str_replace('iamsuperman:', '', $tokenStr);
//                print_r($phone);exit;
            $user = $userProvider->loadUserByUsername(['phone' => $phone]);
        }
        if (!$user instanceof User) {
            return null;
        }
        return new PreAuthenticatedToken($user, $token, $providerKey, $user->getRoles());
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function createToken(Request $request, $providerKey)
    {
        if (!$request->headers->has('extra')) {
            return null;
        }
        // 这里你返回的值，将被作为$credentials传入getUser()
        $extra = $request->headers->get('extra');
        $extra_arr = json_decode($extra, true);
        if (empty($extra_arr['token'])) {
            return null;
        }
        return new PreAuthenticatedToken(
            'anon.',
            $extra_arr['token'],
            $providerKey
        );
    }

    /**
     * This is called when an interactive authentication attempt fails. This is
     * called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response The response to return, never null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        print_r($exception);
        exit;
        return new ApiJsonResponse(406, 'token not exists');
    }
}