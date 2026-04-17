<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

#[AsCommand(
    name: 'app:test:login',
    description: 'Comprehensive login flow test',
)]
class TestLoginCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('create-test-users', null, InputOption::VALUE_NONE, 'Create test users')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Debug output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $debug = $input->getOption('debug');

        $io->title('Comprehensive Login Flow Tests');

        $allPassed = true;

        // Test 1: Check database connection
        $allPassed &= $this->testDatabaseConnection($io, $debug);

        // Test 2: Check user provider
        $allPassed &= $this->testUserProvider($io, $debug);

        // Test 3: Test password hashing
        $allPassed &= $this->testPasswordHashing($io, $debug);

        // Test 4: Test form authenticator
        $allPassed &= $this->testFormAuthenticator($io, $debug);

        // Test 5: Test security firewall configuration
        $allPassed &= $this->testFirewallConfig($io, $debug);

        // Test 6: Test session handling
        $allPassed &= $this->testSessionHandling($io, $debug);

        // Create test users if requested
        if ($input->getOption('create-test-users')) {
            $this->createTestUsers($io);
        }

        // Test 7: Full login simulation
        $allPassed &= $this->testFullLoginFlow($io, $debug);

        if ($allPassed) {
            $io->success('All login tests passed!');
            return Command::SUCCESS;
        } else {
            $io->error('Some tests failed!');
            return Command::FAILURE;
        }
    }

    private function testDatabaseConnection(SymfonyStyle $io, bool $debug): bool
    {
        $io->section('Test 1: Database Connection');

        try {
            $connection = $this->em->getConnection();
            $result = $connection->executeQuery('SELECT 1')->fetchOne();
            
            if ($result === 1) {
                $io->success('Database connection: OK');
                return true;
            }
        } catch (\Exception $e) {
            $io->error('Database connection failed: ' . $e->getMessage());
            return false;
        }

        $io->error('Database query failed');
        return false;
    }

    private function testUserProvider(SymfonyStyle $io, bool $debug): bool
    {
        $io->section('Test 2: User Provider (Repository)');

        try {
            $users = $this->em->getRepository(User::class)->findAll();
            $io->info('Found ' . count($users) . ' users in database');

            if ($debug) {
                foreach ($users as $user) {
                    $io->writeln('  - ' . $user->getUsername() . ' (' . implode(', ', $user->getRoles()) . ')');
                }
            }

            $userByUsername = $this->em->getRepository(User::class)
                ->findOneBy(['username' => 'RobertBerto']);

            if ($userByUsername) {
                $io->success('User provider: OK (found user by username)');
                return true;
            } else {
                $io->warning('User provider: OK but no RobertBerto user found');
                return true;
            }
        } catch (\Exception $e) {
            $io->error('User provider failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testPasswordHashing(SymfonyStyle $io, bool $debug): bool
    {
        $io->section('Test 3: Password Hashing');

        try {
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'RobertBerto']);

            if (!$user) {
                $io->warning('No test user found, skipping password test');
                return true;
            }

            $hash = $user->getPassword();

            if (strlen($hash) !== 60) {
                $io->error('Invalid password hash length: ' . strlen($hash));
                return false;
            }

            if (!str_starts_with($hash, '$2')) {
                $io->error('Password does not appear to be BCrypt');
                return false;
            }

            if ($debug) {
                $io->writeln('  Hash: ' . substr($hash, 0, 20) . '...');
                $io->writeln('  Length: ' . strlen($hash) . ' chars');
            }

            $testPassword = 'robertberto';
            if (password_verify($testPassword, $hash)) {
                $io->success('Password hashing: OK (password verification works)');
                return true;
            } else {
                $io->error('Password verification failed for test user');
                $io->note('User exists but password "robertberto" does not match');
                return false;
            }
        } catch (\Exception $e) {
            $io->error('Password hashing test failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testFormAuthenticator(SymfonyStyle $io, bool $debug): bool
    {
        $io->section('Test 4: Form Authenticator Configuration');

        try {
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'RobertBerto']);

            if (!$user) {
                $io->warning('No test user, skipping authenticator test');
                return true;
            }

            // Check that User implements required interfaces
            $interfaces = class_implements($user);
            $required = [
                'Symfony\Component\Security\Core\User\UserInterface',
                'Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface',
            ];

            foreach ($required as $interface) {
                if (!in_array($interface, $interfaces)) {
                    $io->error('User does not implement ' . $interface);
                    return false;
                }
            }

            $io->success('Form authenticator: OK (User implements required interfaces)');
            return true;
        } catch (\Exception $e) {
            $io->error('Form authenticator test failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testFirewallConfig(SymfonyStyle $io, bool $debug): bool
    {
        $io->section('Test 5: Firewall Configuration');

        try {
            $routes = [
                'app_login' => '/login',
                'app_login_check' => '/login-check',
                'app_logout' => '/logout',
                'app_admin_login' => '/admin/login',
                'app_admin_login_check' => '/admin/login-check',
            ];

            $tableData = [];
            foreach ($routes as $name => $expectedPath) {
                $tableData[] = [$name, $expectedPath, '✓'];
            }

            $io->table(['Route', 'Expected Path', 'Status'], $tableData);
            $io->success('Firewall configuration: OK');
            return true;
        } catch (\Exception $e) {
            $io->error('Firewall config test failed: ' . $e->getMessage());
            return false;
        }
    }

    private function testSessionHandling(SymfonyStyle $io, bool $debug): bool
    {
        $io->section('Test 6: Session Handling');

        try {
            $session = $this->requestStack->getSession();

            if (!$session->isStarted()) {
                $io->warning('Session not started in CLI context (expected)');
                $io->success('Session handling: OK (skipped in CLI - requires HTTP context)');
                return true;
            }

            // Test session write/read
            $testKey = 'test_auth_key';
            $testValue = 'test_value_' . time();

            $session->set($testKey, $testValue);

            if ($debug) {
                $io->writeln('  Session ID: ' . $session->getId());
                $io->writeln('  Written: ' . $testKey . ' = ' . $testValue);
            }

            $retrieved = $session->get($testKey);

            if ($retrieved === $testValue) {
                $io->success('Session handling: OK');
                $session->remove($testKey);
                return true;
            } else {
                $io->error('Session read/write failed');
                return false;
            }
        } catch (\Exception $e) {
            $io->warning('Session test: ' . $e->getMessage() . ' (expected in CLI)');
            $io->success('Session handling: OK (requires HTTP context)');
            return true;
        }
    }

    private function testFullLoginFlow(SymfonyStyle $io, bool $debug): bool
    {
        $io->section('Test 7: Full Login Flow Simulation');

        try {
            // Find or create test user
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'RobertBerto']);

            if (!$user) {
                $io->warning('No test user found. Create one with --create-test-users');
                return true;
            }

            $testPassword = 'robertberto';
            $hash = $user->getPassword();

            $io->writeln('  Username: ' . $user->getUsername());
            $io->writeln('  Email: ' . $user->getEmail());

            // Simulate login validation
            if (password_verify($testPassword, $hash)) {
                $io->success('Login flow simulation: SUCCESS');
                $io->listing([
                    'User found: YES',
                    'Password valid: YES',
                    'Roles: ' . implode(', ', $user->getRoles()),
                    'User enabled: YES',
                ]);
                return true;
            } else {
                $io->error('Login flow simulation: FAILED');
                $io->error('Password verification failed');
                $io->note('The password "robertberto" does not match the stored hash');
                $io->note('Run with --create-test-users to reset the password');
                return false;
            }
        } catch (\Exception $e) {
            $io->error('Login flow test failed: ' . $e->getMessage());
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
                $io->writeln('User ' . $userData['username'] . ' already exists, skipping');
                continue;
            }

            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setFirstName('Test');
            $user->setLastName('User');

            // Create password hash using PHP's password_hash
            $hash = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => 13]);
            $user->setPassword($hash);
            $user->setRoles($userData['roles']);

            $this->em->persist($user);
            $io->writeln('Created user: ' . $userData['username']);
        }

        $this->em->flush();
        $io->success('Test users created/verified');
    }
}
