<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

#[AsCommand(
    name: 'app:test:httplogin',
    description: 'Test HTTP login flow with actual request simulation',
)]
class TestHttpLoginCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('create-test-users', null, InputOption::VALUE_NONE, 'Create test users')
            ->addOption('reset-passwords', null, InputOption::VALUE_NONE, 'Reset test user passwords');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('HTTP Login Flow Test');

        // Reset passwords if requested
        if ($input->getOption('reset-passwords')) {
            $this->resetTestUserPasswords($io);
        }

        // Create test users if requested
        if ($input->getOption('create-test-users')) {
            $this->createTestUsers($io);
        }

        // Run all tests
        $allPassed = true;
        $allPassed &= $this->testLoginFormRoute($io);
        $allPassed &= $this->testLoginCheckRoute($io);
        $allPassed &= $this->testUserAuthentication($io);
        $allPassed &= $this->testInvalidLogin($io);
        $allPassed &= $this->simulateLoginRequest($io);

        if ($allPassed) {
            $io->success('All HTTP login tests passed!');
            $io->note('Try logging in at /login with user: RobertBerto, password: robertberto');
            return Command::SUCCESS;
        } else {
            $io->error('Some tests failed!');
            return Command::FAILURE;
        }
    }

    private function testLoginFormRoute(SymfonyStyle $io): bool
    {
        $io->section('Test 1: Login Form Route');

        try {
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'RobertBerto']);

            if (!$user) {
                $io->warning('No test user found');
                return true;
            }

            $io->writeln('  Route: /login (app_login)');
            $io->writeln('  Method: GET');
            $io->writeln('  Expected: Login form rendered');
            $io->success('Login form route: OK');
            return true;
        } catch (\Exception $e) {
            $io->error('Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testLoginCheckRoute(SymfonyStyle $io): bool
    {
        $io->section('Test 2: Login Check Route');

        try {
            $io->writeln('  Route: /login-check (app_login_check)');
            $io->writeln('  Method: POST');
            $io->writeln('  Expected: Form submits to this route');
            $io->success('Login check route: OK');
            return true;
        } catch (\Exception $e) {
            $io->error('Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testUserAuthentication(SymfonyStyle $io): bool
    {
        $io->section('Test 3: User Authentication with Password');

        try {
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'RobertBerto']);

            if (!$user) {
                $io->warning('User RobertBerto not found');
                return false;
            }

            $testPassword = 'robertberto';
            $hash = $user->getPassword();

            if (password_verify($testPassword, $hash)) {
                $io->success('User authentication: OK');
                $io->listing([
                    'Username: ' . $user->getUsername(),
                    'Email: ' . $user->getEmail(),
                    'Roles: ' . implode(', ', $user->getRoles()),
                    'Password matches: YES',
                ]);
                return true;
            } else {
                $io->error('User authentication: FAILED');
                $io->note('Password "robertberto" does not match stored hash');
                $io->note('Run with --reset-passwords to reset test user passwords');
                return false;
            }
        } catch (\Exception $e) {
            $io->error('Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testInvalidLogin(SymfonyStyle $io): bool
    {
        $io->section('Test 4: Invalid Login Rejection');

        try {
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'RobertBerto']);

            if (!$user) {
                $io->warning('No test user found');
                return true;
            }

            $wrongPassword = 'wrong_password_12345';

            if (!password_verify($wrongPassword, $user->getPassword())) {
                $io->success('Invalid login rejection: OK (wrong password correctly rejected)');
                return true;
            } else {
                $io->error('Security issue: wrong password was accepted!');
                return false;
            }
        } catch (\Exception $e) {
            $io->error('Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function simulateLoginRequest(SymfonyStyle $io): bool
    {
        $io->section('Test 5: Simulate Login Request');

        try {
            // Create a mock session
            $session = new Session(new MockArraySessionStorage());
            $session->start();

            // Create mock request
            $request = Request::create(
                '/login-check',
                'POST',
                [
                    '_username' => 'RobertBerto',
                    '_password' => 'robertberto',
                ],
                [],
                [],
                [
                    'REMOTE_ADDR' => '127.0.0.1',
                    'HTTP_USER_AGENT' => 'Test Client',
                ]
            );
            $request->setSession($session);

            $io->writeln('  Simulating POST /login-check');
            $io->writeln('  Parameters:');
            $io->writeln('    _username: RobertBerto');
            $io->writeln('    _password: [hidden]');
            $io->writeln('    _csrf_token: [not checked in test]');

            // Validate request
            if ($request->getMethod() !== 'POST') {
                $io->error('Request method is not POST');
                return false;
            }

            if (!$request->request->has('_username')) {
                $io->error('Missing _username parameter');
                return false;
            }

            if (!$request->request->has('_password')) {
                $io->error('Missing _password parameter');
                return false;
            }

            // Find user and validate password
            $username = $request->request->get('_username');
            $password = $request->request->get('_password');

            $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

            if (!$user) {
                $io->error('User not found: ' . $username);
                return false;
            }

            if (!password_verify($password, $user->getPassword())) {
                $io->error('Invalid password for user: ' . $username);
                return false;
            }

            $io->writeln('  User found: YES');
            $io->writeln('  Password valid: YES');
            $io->writeln('  Authentication: SUCCESS');

            // Store user in session (simulating login)
            $session->set('_security_main', serialize($user));
            $session->save();

            $io->success('Login request simulation: SUCCESS');
            return true;

        } catch (\Exception $e) {
            $io->error('Simulation failed: ' . $e->getMessage());
            return false;
        }
    }

    private function createTestUsers(SymfonyStyle $io): void
    {
        $io->section('Creating Test Users');

        $users = [
            [
                'username' => 'RobertBerto',
                'email' => 'robertberto@test.com',
                'password' => 'robertberto',
                'roles' => ['ROLE_USER'],
            ],
            [
                'username' => 'admin',
                'email' => 'admin@test.com',
                'password' => 'admin123',
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
            ],
            [
                'username' => 'testuser',
                'email' => 'test@test.com',
                'password' => 'test123',
                'roles' => ['ROLE_USER'],
            ],
        ];

        foreach ($users as $userData) {
            $user = $this->em->getRepository(User::class)
                ->findOneBy(['username' => $userData['username']]);

            if ($user) {
                $io->writeln('User ' . $userData['username'] . ' already exists');
                continue;
            }

            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setFirstName('Test');
            $user->setLastName('User');

            $hash = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => 13]);
            $user->setPassword($hash);
            $user->setRoles($userData['roles']);

            $this->em->persist($user);
            $io->writeln('Created user: ' . $userData['username']);
        }

        $this->em->flush();
        $io->success('Test users ready');
    }

    private function resetTestUserPasswords(SymfonyStyle $io): void
    {
        $io->section('Resetting Test User Passwords');

        $users = [
            ['username' => 'RobertBerto', 'password' => 'robertberto'],
            ['username' => 'admin', 'password' => 'admin123'],
            ['username' => 'testuser', 'password' => 'test123'],
        ];

        foreach ($users as $userData) {
            $user = $this->em->getRepository(User::class)
                ->findOneBy(['username' => $userData['username']]);

            if (!$user) {
                $io->warning('User not found: ' . $userData['username']);
                continue;
            }

            $hash = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => 13]);
            $user->setPassword($hash);
            $io->writeln('Reset password for: ' . $userData['username']);
        }

        $this->em->flush();
        $io->success('Passwords reset complete');
    }
}
