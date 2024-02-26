<?php

namespace App\Security;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class TokenAuth extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{

    private EntityManagerInterface $em;
    private TokenUserProvider $tokenUserProvider;
    private Security $security;

    public function __construct(EntityManagerInterface $em, TokenUserProvider $tokenUserProvider, Security $security)
    {
        $this->em = $em;
        $this->tokenUserProvider = $tokenUserProvider;
        $this->security = $security;

    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $content = ['error' => 'Authentication Required'];
        return new Response(json_encode($content), Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     * @param Request $request The request object.
     * @return bool|null True if the authenticator should be used, null otherwise
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     * @param Request $request The request object.
     * return Passport The data that will be passed to getUser()
     *
     */
    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');
        if (null === $apiToken) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }
        return new SelfValidatingPassport(new UserBadge($apiToken));
    }

    public function getUserFromToken(?string $token): UserInterface
    {
        // Check if the token is null
        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        // Load the user using the token
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            // Throw an authentication exception if the user was not found
            throw new CustomUserMessageAuthenticationException("Failed to load user from token: " . $e->getMessage());
        }
        // If no exception is thrown, return the user
        return $user;
    }

    /**
     * @throws Exception
     */
    public function checkAdminRole(): void
    {

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new Exception('Access denied');
        }
    }

    /**
     * Called on every request to decide if the authenticator should be
     * used for this request. Returning false will cause this authenticator
     * to be skipped.
     * @param Request $request The request.
     * @param TokenInterface $token The token.
     * @param string $firewallName The name of the firewall.
     * @return Response|null The response, or null if the authenticator should continue
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    /**
     * Called if authentication executed, but failed.
     * @param Request $request The request object.
     * @param AuthenticationException $exception The exception that caused the authentication to fail.
     * @return Response|null The response to send, return null to continue request processing
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];
        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }


}

{

}