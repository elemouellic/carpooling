<?php

namespace App\Controller;

use App\Entity\User;
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
        // Récupérez les données de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifiez si les champs login et password sont présents
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

            // Générez un token de session aléatoire et définissez-le pour l'utilisateur
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
            $result = "Error while creating the user: " . $e->getMessage();
        }
        return new JsonResponse($result);
    }

    /**
     * @throws RandomException
     */
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Récupérez les données de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifiez si les champs login et password sont présents
        if (!isset($data['login']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Missing login or password',
            ], 400);
        }

        // Get the login and password from the request data for security
        $login = $data['login'];
        $password = $data['password'];

        // Récupérez l'utilisateur à partir du login
        $user = $em->getRepository(User::class)->findOneBy(['login' => $login]);

        // Vérifiez si l'utilisateur existe et si le mot de passe est correct
        if ($user && $passwordHasher->isPasswordValid($user, $password)) {
            // Si les informations d'identification sont correctes, générez un token de session et renvoyez-le avec l'ID de l'utilisateur
            $token = bin2hex(random_bytes(10)); // Générez un token de session aléatoire
            $user->setToken($token);
            $em->flush();

            return $this->json([
                'token' => $token,
                'user_id' => $user->getId(),
            ]);
        } else {
            // Si les informations d'identification ne sont pas correctes, renvoyez un message d'erreur
            return $this->json([
                'error' => 'Login or password incorrect',
            ], 401);
        }
    }

}