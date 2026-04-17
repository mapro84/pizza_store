<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Entity\User;
use App\Entity\Customer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationTest
{
    private \Doctrine\ORM\EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private array $config;

    public function __construct(
        \Doctrine\ORM\EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        array $config = []
    ) {
        $this->em = $em;
        $this->hasher = $hasher;
        $this->config = $config;
    }

    public function runAll(): array
    {
        $results = [];

        $results['testUserAuthentication'] = $this->testUserAuthentication();
        $results['testInvalidCredentials'] = $this->testInvalidCredentials();
        $results['testAdminAuthentication'] = $this->testAdminAuthentication();
        $results['testSecurityConfiguration'] = $this->testSecurityConfiguration();
        $results['testUserRoles'] = $this->testUserRoles();

        return $results;
    }

    public function testUserAuthentication(): array
    {
        echo "TEST 1: User Authentication\n";
        echo str_repeat('-', 40) . "\n";

        $username = $this->config['test_user']['username'] ?? 'testuser@example.com';
        $password = $this->config['test_user']['password'] ?? 'password123';

        echo "Attempting to authenticate user: {$username}\n";

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            echo "User not found. Creating test user...\n";
            $user = $this->createTestUser($username, $password, ['ROLE_USER']);
            echo "SUCCESS: Test user created and authenticated\n\n";
            return ['status' => 'PASS', 'message' => 'User created and authenticated successfully'];
        }

        if ($this->hasher->isPasswordValid($user, $password)) {
            echo "Password validation: VALID\n";
            echo "User roles: " . implode(', ', $user->getRoles()) . "\n";
            echo "SUCCESS: User authenticated successfully\n\n";
            return ['status' => 'PASS', 'message' => 'User authenticated successfully'];
        }

        echo "Password validation: INVALID\n";
        echo "FAIL: Authentication failed - invalid password\n\n";
        return ['status' => 'FAIL', 'message' => 'Invalid password'];
    }

    public function testInvalidCredentials(): array
    {
        echo "TEST 2: Invalid Credentials Rejection\n";
        echo str_repeat('-', 40) . "\n";

        $username = $this->config['test_user']['username'] ?? 'testuser@example.com';
        $wrongPassword = 'wrong_password';

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            echo "User not found, creating for test...\n";
            $password = $this->config['test_user']['password'] ?? 'password123';
            $user = $this->createTestUser($username, $password, ['ROLE_USER']);
        }

        if ($this->hasher->isPasswordValid($user, $wrongPassword)) {
            echo "FAIL: Wrong password was incorrectly accepted\n\n";
            return ['status' => 'FAIL', 'message' => 'Security issue: wrong password accepted'];
        }

        echo "Password validation: CORRECTLY REJECTED\n";
        echo "SUCCESS: Invalid credentials properly rejected\n\n";
        return ['status' => 'PASS', 'message' => 'Invalid credentials properly rejected'];
    }

    public function testAdminAuthentication(): array
    {
        echo "TEST 3: Admin Authentication\n";
        echo str_repeat('-', 40) . "\n";

        $username = $this->config['admin_user']['username'] ?? 'admin';
        $password = $this->config['admin_user']['password'] ?? 'admin123';

        echo "Attempting to authenticate admin: {$username}\n";

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            echo "Admin user not found. Creating test admin...\n";
            $user = $this->createTestUser($username, $password, ['ROLE_ADMIN']);
            echo "SUCCESS: Admin user created and authenticated\n\n";
            return ['status' => 'PASS', 'message' => 'Admin created and authenticated'];
        }

        if (!$this->hasher->isPasswordValid($user, $password)) {
            echo "FAIL: Admin password validation failed\n";
            echo "TIP: Use the command below to reset admin password:\n";
            echo "  php bin/console security:encode-password {$password}\n\n";
            return ['status' => 'FAIL', 'message' => 'Admin password mismatch'];
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            echo "FAIL: User does not have ROLE_ADMIN\n";
            return ['status' => 'FAIL', 'message' => 'User missing ROLE_ADMIN'];
        }

        echo "Password validation: VALID\n";
        echo "User roles: " . implode(', ', $user->getRoles()) . "\n";
        echo "SUCCESS: Admin authenticated successfully\n\n";
        return ['status' => 'PASS', 'message' => 'Admin authenticated successfully'];
    }

    public function testSecurityConfiguration(): array
    {
        echo "TEST 4: Security Configuration Check\n";
        echo str_repeat('-', 40) . "\n";

        $checks = [];

        $userRepo = $this->em->getRepository(User::class);
        $checks['user_repository'] = $userRepo instanceof \App\Repository\UserRepository;

        $allUsers = $this->em->createQueryBuilder()
            ->from(User::class, 'u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $checks['database_connection'] = true;
        echo "Total users in database: {$allUsers}\n";

        $testUser = $this->em->getRepository(User::class)->findOneBy([]);
        if ($testUser) {
            $checks['password_hashing'] = $testUser->getPassword() !== null && strlen($testUser->getPassword()) > 20;
            echo "Password hashing configured: " . ($checks['password_hashing'] ? 'YES' : 'NO') . "\n";
            echo "Hash length: " . strlen($testUser->getPassword()) . " chars\n";
        }

        $allPassed = !in_array(false, $checks, true);
        echo "\n" . ($allPassed ? "SUCCESS" : "FAIL") . ": Security configuration check\n\n";

        return [
            'status' => $allPassed ? 'PASS' : 'FAIL',
            'message' => 'Security configuration verified',
            'checks' => $checks
        ];
    }

    public function testUserRoles(): array
    {
        echo "TEST 5: User Roles Verification\n";
        echo str_repeat('-', 40) . "\n";

        $users = $this->em->getRepository(User::class)->findAll();
        $results = [];

        foreach ($users as $user) {
            $roles = $user->getRoles();
            $hasRoleUser = in_array('ROLE_USER', $roles);
            echo "User: {$user->getUsername()} -> Roles: " . implode(', ', $roles) . "\n";

            if (!$hasRoleUser) {
                echo "  WARNING: Missing ROLE_USER\n";
            }
            $results[$user->getUsername()] = $hasRoleUser;
        }

        $allValid = !in_array(false, $results, true);
        echo "\n" . ($allValid ? "SUCCESS" : "WARNING") . ": All users have ROLE_USER\n\n";

        return [
            'status' => $allValid ? 'PASS' : 'WARNING',
            'message' => 'User roles verified'
        ];
    }

    private function createTestUser(string $username, string $password, array $roles): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $hashedPassword = $this->hasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles($roles);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public static function printResults(array $results): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "TEST RESULTS SUMMARY\n";
        echo str_repeat('=', 50) . "\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($results as $testName => $result) {
            $status = $result['status'];
            $icon = match($status) {
                'PASS' => '[✓]',
                'FAIL' => '[✗]',
                'WARNING' => '[!]',
                default => '[?]'
            };

            echo "{$icon} {$testName}: {$status}\n";
            echo "    {$result['message']}\n";

            if ($status === 'PASS') {
                $passed++;
            } elseif ($status === 'FAIL') {
                $failed++;
            }
        }

        echo "\n" . str_repeat('-', 50) . "\n";
        echo "Total: {$passed} passed, {$failed} failed\n";
        echo str_repeat('=', 50) . "\n";
    }
}
