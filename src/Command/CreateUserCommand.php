<?php

namespace App\Command;

use App\Entity\User;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    aliases: ['app:add-user'],
    hidden: false
)]
class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a new user.')
            ->setHelp('This command allows you to create a user...');
    }

    /**
     * @throws RandomException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setLogin('emmanuel');
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            'administrateur' // Remplacez ceci par le mot de passe en clair
        ));

        $user->setRoles(['ROLE_ADMIN']);

        // Générez un token de session aléatoire et définissez-le pour l'utilisateur
        $token = bin2hex(random_bytes(10));
        $user->setToken($token);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('User created with token: ' . $token);

        return Command::SUCCESS;
    }
}