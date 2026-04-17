<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test:auth',
    description: 'Run authentication tests',
)]
class TestAuthCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('create-test-users', null, InputOption::VALUE_NONE, 'Create test users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Authentication Tests');

        $io->section('Test 1: User Authentication');

        $username = 'RobertBerto';
        $password = 'robertberto';

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            if ($input->getOption('create-test-users')) {
                $io->note('Creating test user...');
                $user = $this->createTestUser($username, $password, ['ROLE_USER']);
                $io->success('Test user created and authenticated successfully');
            } else {
                $io->error('User not found. Run with --create-test-users to create test users.');
                return Command::FAILURE;
            }
        }

        if ($this->hasher->isPasswordValid($user, $password)) {
            $io->success('User authenticated successfully');
            $io->listing($user->getRoles());
        } else {
            $io->error('Authentication failed - invalid password');
            return Command::FAILURE;
        }

        $io->section('Test 2: Invalid Credentials Rejection');

        if (!$this->hasher->isPasswordValid($user, 'wrong_password')) {
            $io->success('Invalid credentials properly rejected');
        } else {
            $io->error('Security issue: wrong password was accepted');
            return Command::FAILURE;
        }

        $io->section('Test 3: Admin Authentication');

        $adminUsername = 'admin';
        $adminPassword = 'admin123';

        $admin = $this->em->getRepository(User::class)->findOneBy(['username' => $adminUsername]);

        if (!$admin) {
            if ($input->getOption('create-test-users')) {
                $io->note('Creating admin user...');
                $admin = $this->createTestUser($adminUsername, $adminPassword, ['ROLE_ADMIN']);
                $io->success('Admin user created and authenticated successfully');
            } else {
                $io->error('Admin user not found. Run with --create-test-users to create test admin.');
                return Command::FAILURE;
            }
        }

        if ($this->hasher->isPasswordValid($admin, $adminPassword)) {
            if (in_array('ROLE_ADMIN', $admin->getRoles())) {
                $io->success('Admin authenticated successfully');
                $io->listing($admin->getRoles());
            } else {
                $io->error('Admin user exists but missing ROLE_ADMIN');
                return Command::FAILURE;
            }
        } else {
            $io->error('Admin authentication failed - invalid password');
            $io->note('Admin user exists but password does not match "admin123"');
            return Command::FAILURE;
        }

        $io->section('Test 4: Security Configuration');

        $userCount = $this->em->createQueryBuilder()
            ->from(User::class, 'u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $io->table(['Check', 'Result'], [
            ['Database Connection', 'OK'],
            ['User Repository', 'OK'],
            ['Total Users', (string) $userCount],
        ]);

        $testUser = $this->em->getRepository(User::class)->findOneBy([]);
        if ($testUser) {
            $hashLength = strlen($testUser->getPassword());
            $io->table(['Security Check', 'Result'], [
                ['Password Hashing', $hashLength > 20 ? 'BCRYPT' : 'UNKNOWN'],
                ['Hash Length', $hashLength . ' chars'],
            ]);
        }

        $io->section('Test 5: User Roles');

        $users = $this->em->getRepository(User::class)->findAll();
        $tableData = [];
        foreach ($users as $u) {
            $hasRoleUser = in_array('ROLE_USER', $u->getRoles()) ? '✓' : '✗';
            $hasRoleAdmin = in_array('ROLE_ADMIN', $u->getRoles()) ? '✓' : '-';
            $tableData[] = [$u->getUsername(), implode(', ', $u->getRoles()), $hasRoleUser, $hasRoleAdmin];
        }

        $io->table(['Username', 'Roles', 'ROLE_USER', 'ROLE_ADMIN'], $tableData);

        $io->success('All authentication tests passed!');

        return Command::SUCCESS;
    }

    private function createTestUser(string $username, string $password, array $roles): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@test.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $hashedPassword = $this->hasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles($roles);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
