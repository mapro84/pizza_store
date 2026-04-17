<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Customer;
use App\Entity\User;
use Doctrine\ORM\EntityManager;

class OrderWorkflowTest
{
    private EntityManager $em;
    private $hasher;
    private array $config;

    public function __construct(EntityManager $em, $hasher, array $config)
    {
        $this->em = $em;
        $this->hasher = $hasher;
        $this->config = $config;
    }

    private function hashPassword(User $user, string $plainPassword): string
    {
        if ($this->hasher instanceof \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface) {
            return $this->hasher->hashPassword($user, $plainPassword);
        }
        if ($this->hasher instanceof \Symfony\Component\PasswordHasher\PasswordHasherInterface) {
            return $this->hasher->hash($plainPassword);
        }
        return password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    public function runAll(): void
    {
        echo "=== Order Workflow Tests ===\n\n";

        $this->testCreateOrder();
        $this->testConfirmOrder();
        $this->testPayment();
    }

    public function testCreateOrder(): void
    {
        echo "TEST 1: Create Order\n";
        echo str_repeat('-', 40) . "\n";

        $cartItems = $this->config['cart'];
        $cart = [];

        foreach ($cartItems as $index => $item) {
            $key = $item['id'] . '_' . $item['size'];
            $cart[$key] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'size' => $item['size'],
                'quantity' => $item['quantity'],
                'image' => 'https://example.com/pizza.jpg',
            ];
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        $total = round($total, 2);

        echo "Cart items:\n";
        foreach ($cart as $key => $item) {
            echo "  - {$item['name']} ({$item['size']}) x{$item['quantity']} = €" . number_format($item['price'] * $item['quantity'], 2) . "\n";
        }
        echo "Total: €" . number_format($total, 2) . "\n";

        $orderNumber = 'BN' . date('YmdHis');
        echo "Generated order number: {$orderNumber}\n";
        echo "SUCCESS: Order data prepared for checkout\n\n";
    }

    public function testConfirmOrder(): void
    {
        echo "TEST 2: Confirm Order\n";
        echo str_repeat('-', 40) . "\n";

        $userConfig = $this->config['test_user'];
        $cartItems = $this->config['cart'];
        $paymentConfig = $this->config['payment'];

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $userConfig['username']]);
        if (!$user) {
            echo "User '{$userConfig['username']}' not found. Creating test user...\n";
            $user = $this->createTestUser($userConfig);
        }

        $customer = $this->em->getRepository(Customer::class)->findOneBy(['user' => $user]);
        if (!$customer) {
            echo "Customer not found. Creating test customer...\n";
            $customer = $this->createTestCustomer($user, $userConfig);
        }

        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $loyaltyDiscount = 0;
        $total = max(0, $subtotal - $loyaltyDiscount);

        $orderNumber = 'BN' . date('YmdHis') . rand(100, 999);
        $fullName = $userConfig['first_name'] . ' ' . $userConfig['last_name'];

        $order = new Order();
        $order->setOrderNumber($orderNumber);
        $order->setCustomer($customer);
        $order->setStatus('confirmed');
        $order->setTotalAmount($total);
        $order->setPaymentMethod($paymentConfig['method']);
        $order->setCustomerName($fullName);
        $order->setCustomerEmail($user->getEmail());
        $order->setCustomerPhone($userConfig['phone']);
        $order->setDeliveryAddress($userConfig['address']);
        $order->setDeliveryZipCode($userConfig['postal_code']);
        $order->setDeliveryCity($userConfig['city']);

        $this->em->persist($order);

        foreach ($cartItems as $item) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setPizzaName($item['name']);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setUnitPrice($item['price']);
            $orderItem->setTotalPrice($item['price'] * $item['quantity']);
            $orderItem->setSize($item['size']);
            $this->em->persist($orderItem);
        }

        $this->em->flush();

        echo "Order created:\n";
        echo "  Order Number: {$orderNumber}\n";
        echo "  Customer: {$customer->getUser()->getUsername()}\n";
        echo "  Total: €" . number_format($total, 2) . "\n";
        echo "  Status: confirmed\n";
        echo "  Payment: {$paymentConfig['method']}\n";
        echo "SUCCESS: Order confirmed and saved to database\n\n";
    }

    public function testPayment(): void
    {
        echo "TEST 3: Process Payment\n";
        echo str_repeat('-', 40) . "\n";

        $testCards = [
            $this->config['test_cards']['valid'] => ['name' => 'Visa Test', 'status' => 'success'],
            $this->config['test_cards']['declined'] => ['name' => 'Visa Declined', 'status' => 'declined'],
        ];
        $paymentConfig = $this->config['payment'];

        foreach ($testCards as $cardNumber => $cardInfo) {
            echo "Testing card: {$cardInfo['name']} ({$cardNumber})\n";

            $isValid = $this->validateCard($cardNumber, $paymentConfig['expiry'], $paymentConfig['cvv']);
            if (!$isValid) {
                echo "  Result: INVALID CARD\n";
                continue;
            }

            $result = match ($cardInfo['status']) {
                'success' => 'PAYMENT APPROVED',
                'declined' => 'PAYMENT DECLINED',
                'insufficient_funds' => 'INSUFFICIENT FUNDS',
                default => 'UNKNOWN',
            };

            echo "  Result: {$result}\n";
        }

        echo "\nSUCCESS: Payment processing simulation complete\n\n";
    }

    private function createTestUser(array $config): User
    {
        $user = new User();
        $user->setUsername($config['username']);
        $user->setEmail($config['email']);
        $user->setFirstName($config['first_name']);
        $user->setLastName($config['last_name']);
        $user->setPhone($config['phone']);
        $hashedPassword = $this->hashPassword($user, $config['password']);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createTestCustomer(User $user, array $config): Customer
    {
        $customer = new Customer();
        $customer->setUser($user);
        $customer->setAddress($config['address']);
        $customer->setCity($config['city']);
        $customer->setPostalCode($config['postal_code']);
        $customer->setLoyaltyPoints(0);

        $this->em->persist($customer);
        $this->em->flush();

        return $customer;
    }

    private function validateCard(string $number, string $expiry, string $cvv): bool
    {
        if (!ctype_digit($number) || strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
            return false;
        }

        if (!ctype_digit($cvv) || strlen($cvv) < 3 || strlen($cvv) > 4) {
            return false;
        }

        return $this->luhnCheck($number);
    }

    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];

            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}