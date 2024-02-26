<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }


    #[Route('/register', name: 'app_user_add', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Get the data from the request
        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (!isset($data['login']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Missing login or password',
            ], 400);
        }

        // Get the login and password from the request data for security
        $login = $data['login'];
        $password = $data['password'];

        try {
            $user = new User();
            $user->setLogin($login);
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                $password
            ));

            $user->setRoles(['ROLE_USER']);

            // Generate a random token for the user
            $token = bin2hex(random_bytes(10));
            $user->setToken($token);

            $em->persist($user);
            $em->flush();
            $result = [
                'message' => "User created",
                'token' => $token,
                'user_id' => $user->getId(),
            ];
        } catch (RandomException|Exception $e) {
            return new JsonResponse(["error" => "Error while creating the user: " . $e->getMessage()], 400);
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse(["error" => "Error while creating the user: Login already exists"], 409);
        }
        return new JsonResponse($result);
    }

    /**
     * @throws RandomException
     */
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Get the data from the request
        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (!isset($data['login']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Missing login or password',
            ], 400);
        }

        // Get the login and password from the request data for security
        $login = $data['login'];
        $password = $data['password'];

        // Get the user from the database using the login
        $user = $em->getRepository(User::class)->findOneBy(['login' => $login]);

        // Check if the user exists and if the password is correct
        if ($user && $passwordHasher->isPasswordValid($user, $password)) {
            // If the credentials are correct, generate a new token for the user
            $token = bin2hex(random_bytes(10)); // Generate a random token
            $user->setToken($token);
            $em->flush();

            return $this->json([
                'token' => $token,
                'user_id' => $user->getId(),
            ]);
        } else {
            // If the credentials are incorrect, return an error
            return $this->json([
                'error' => 'Login or password incorrect',
            ], 401);
        }
    }

}