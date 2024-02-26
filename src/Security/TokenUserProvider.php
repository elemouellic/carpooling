<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class TokenUserProvider implements UserProviderInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    /**
     * Loads the user for the given username.
     * @param string $username The username
     * @return UserInterface The user
     * @throws Exception If the user is not found
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['login' => $username]);

        if (!$user) {
            throw new Exception('User not found');
        }

        return $user;
    }

    /**
     * Refreshes the user after being reloaded from the session.
     * @param UserInterface $user The User
     * @return UserInterface The User
     * @throws Exception If the user is not found
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new InvalidArgumentException('Expected instance of App\Entity\User');
        }

        return $this->loadUserByUsername($user->getLogin());
    }

    /**
     * Whether this provider supports the given user class.
     * @param string $class The User class name
     * @return bool True if the class is supported, false otherwise
     */
    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    /**
     * Loads the user for the given identifier.
     * @param string $identifier The user identifier
     * @return UserInterface The user
     * @throws CustomUserMessageAuthenticationException If the user is not found
     */
public function loadUserByIdentifier(string $identifier): UserInterface
{
    $user = $this->em->getRepository(User::class)->findOneBy(['token' => $identifier]);

    if (!$user) {
        throw new CustomUserMessageAuthenticationException('User not found');
    }

    return $user;
}




}