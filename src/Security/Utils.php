<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class Utils
{

    /**
     * Control the current user and the user associated with the student
     * to check if they are the same when the ROLE_USER is used
     * @param TokenAuth $tokenAuth
     * @param $request
     * @param $student
     * @return JsonResponse
     */
    public static function checkUser(TokenAuth $tokenAuth, $request, $student): JsonResponse
    {
        try {
            $token = $request->headers->get('X-AUTH-TOKEN');
            $user = $tokenAuth->getUserFromToken($token);
        } catch (CustomUserMessageAuthenticationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        // Check if $student is null
        if ($student === null) {
            return new JsonResponse(['error' => 'Student not found'], 404);
        }

        // Get the user associated with the student
        $studentUser = $student->getRegister();

        // Check if the user from the token is the same as the user associated with the student
        if ($user !== $studentUser) {
            return new JsonResponse([
                'error' => 'You do not have permission to access this content',
            ], 403);
        }

        return new JsonResponse(['success' => 'User is valid'], 200);
    }
}