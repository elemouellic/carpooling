<?php

namespace App\Security;

use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminRoleChecker
{
    private TokenAuth $tokenAuth;

    public function __construct(TokenAuth $tokenAuth)
    {
        $this->tokenAuth = $tokenAuth;
    }

    public function checkAdminRole(): JsonResponse|null
    {
        try {
            $this->tokenAuth->checkAdminRole();
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        }

        return null;
    }
}