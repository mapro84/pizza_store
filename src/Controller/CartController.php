<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;

final class CartController extends AbstractController
{
    #[Route('/panier', name: 'app_cart')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $cart = $request->getSession()->get('cart', []);
        $total = 0;
        
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $customer = null;
        $user = $this->getUser();
        if ($user) {
            $customer = $em->getRepository(\App\Entity\Customer::class)
                ->findOneBy(['user' => $user]);
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
            'customer' => $customer,
        ]);
    }

    #[Route('/panier/utiliser-points', name: 'app_cart_use_loyalty', methods: ['POST'])]
    public function useLoyaltyPoints(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $user]);

        if (!$customer || $customer->getLoyaltyPoints() <= 0) {
            return $this->redirectToRoute('app_cart');
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $discount = min($customer->getLoyaltyPoints(), $total);
        $session->set('loyalty_discount', $discount);
        $session->set('loyalty_used', true);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/annuler-points', name: 'app_cart_cancel_loyalty', methods: ['POST'])]
    public function cancelLoyaltyPoints(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('loyalty_discount');
        $session->remove('loyalty_used');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        $pizzas = $this->getPizzas();
        $pizza = $pizzas[$id] ?? null;
        
        if ($pizza) {
            $size = $request->request->get('size', 'moyenne');
            $priceMultiplier = match($size) {
                'petite' => 0.8,
                'moyenne' => 1.0,
                'grande' => 1.4,
                default => 1.0,
            };
            
            $cartKey = $id . '_' . $size;
            
            if (isset($cart[$cartKey])) {
                $cart[$cartKey]['quantity']++;
            } else {
                $cart[$cartKey] = [
                    'id' => $id,
                    'name' => $pizza['name'],
                    'price' => $pizza['price'] * $priceMultiplier,
                    'size' => $size,
                    'quantity' => 1,
                    'image' => $pizza['image'],
                ];
            }
            
            $session->set('cart', $cart);
            $session->set('cart_count', count($cart));
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/supprimer/{key}', name: 'app_cart_remove')]
    public function remove(string $key, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        if (isset($cart[$key])) {
            unset($cart[$key]);
            $session->set('cart', $cart);
            $session->set('cart_count', count($cart));
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/quantite/{key}/{quantity}', name: 'app_cart_update')]
    public function update(string $key, int $quantity, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        if (isset($cart[$key])) {
            if ($quantity <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['quantity'] = $quantity;
            }
            $session->set('cart', $cart);
            $session->set('cart_count', count($cart));
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/commande', name: 'app_checkout')]
    public function checkout(Request $request, EntityManagerInterface $em): Response
    {
        $cart = $request->getSession()->get('cart', []);
        $total = 0;
        
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();
        $prefilledData = [
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
        ];
        
        if ($user) {
            $prefilledData['email'] = $user->getEmail() ?: '';
            $prefilledData['firstName'] = $user->getFirstName() ?: '';
            $prefilledData['lastName'] = $user->getLastName() ?: '';
            
            $customer = $this->getCustomerFromUser($user, $em);
            if ($customer) {
                $prefilledData['phone'] = $customer->getPhone() ?: '';
                $prefilledData['address'] = $customer->getAddress() ?: '';
            }
        }

        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart,
            'total' => $total,
            'prefilled' => $prefilledData,
            'is_logged_in' => $user !== null,
        ]);
    }
    
    private function getCustomerFromUser($user, EntityManagerInterface $em): ?\App\Entity\Customer
    {
        if (!$user) {
            return null;
        }
        
        return $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $user]);
    }

    #[Route('/commande/process', name: 'app_checkout_process', methods: ['POST'])]
    public function checkoutProcess(Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }

        $firstName = $request->request->get('firstName', '');
        $lastName = $request->request->get('lastName', '');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $address = $request->request->get('address');
        $paymentMethod = $request->request->get('payment_method');
        $createAccount = $request->request->get('create_account') === '1';
        $username = $request->request->get('username', '');
        $password = $request->request->get('password');

        $session->set('order_firstName', $firstName);
        $session->set('order_lastName', $lastName);
        $session->set('order_email', $email);
        $session->set('order_phone', $phone);
        $session->set('order_address', $address);
        $session->set('order_create_account', $createAccount);
        $session->set('order_username', $username);
        $session->set('order_password', $password);

        if ($paymentMethod === 'carte') {
            return $this->redirectToRoute('app_payment_test');
        }

        return $this->redirectToRoute('app_order_confirm', ['_route' => 'direct']);
    }

    #[Route('/commande/confirmation', name: 'app_order_confirm', methods: ['GET', 'POST'])]
    public function confirm(
        Request $request, 
        \Doctrine\ORM\EntityManagerInterface $em,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher,
        \Symfony\Component\Mailer\MailerInterface $mailer,
        \Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface $params
    ): Response {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }

        $paymentMethod = $session->get('payment_method', 'especes');
        if ($request->request->has('payment_method')) {
            $paymentMethod = $request->request->get('payment_method');
        }
        
        $firstName = $request->request->get('firstName') ?: $session->get('order_firstName', '');
        $lastName = $request->request->get('lastName') ?: $session->get('order_lastName', '');
        $email = $request->request->get('email') ?: $session->get('order_email', '');
        $address = $request->request->get('address') ?: $session->get('order_address', '');
        $phone = $request->request->get('phone') ?: $session->get('order_phone', '');
        $createAccount = $session->get('order_create_account', false);
        $username = $session->get('order_username', '');
        $password = $session->get('order_password', '');

        $fullName = trim($firstName . ' ' . $lastName);

        $accountCreated = false;
        if ($createAccount && $email && $password) {
            $existingUser = $em->getRepository(\App\Entity\User::class)
                ->findOneBy(['email' => $email]);
            
            if (!$existingUser) {
                $user = new \App\Entity\User();
                $user->setUsername($username ?: $email);
                $user->setEmail($email);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setPhone($phone);
                $hashedPassword = $hasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $user->setRoles(['ROLE_USER']);
                
                $em->persist($user);
                $em->flush();
                
                $customer = new \App\Entity\Customer();
                $customer->setUser($user);
                $customer->setAddress($address);
                $customer->setLoyaltyPoints(0);
                
                $em->persist($customer);
                $em->flush();
                
                $accountCreated = true;
            }
        }

        $userForLookup = $em->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => $email]);
        $customer = $userForLookup ? $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $userForLookup]) : null;

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $loyaltyDiscount = (float) $session->get('loyalty_discount', 0);
        $total = max(0, $subtotal - $loyaltyDiscount);

        $orderNumber = 'BN' . date('YmdHis');
        $order = new \App\Entity\Order();
        $order->setOrderNumber($orderNumber);
        $order->setCustomer($customer);
        $order->setStatus('confirmed');
        $order->setTotalAmount($total);
        $order->setDiscountAmount($loyaltyDiscount > 0 ? $loyaltyDiscount : null);
        $order->setPaymentMethod($paymentMethod);
        $order->setCustomerName($fullName);
        $order->setCustomerEmail($email);
        $order->setCustomerPhone($phone);
        $order->setDeliveryAddress($address);
        $order->setDeliveryNotes($session->get('order_notes', ''));
        
        if ($customer) {
            $order->setDeliveryCity($customer->getCity());
            $order->setDeliveryZipCode($customer->getPostalCode());
        }

        $em->persist($order);
        $em->flush();

        foreach ($cart as $key => $item) {
            $orderItem = new \App\Entity\OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setPizzaName($item['name']);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setUnitPrice($item['price']);
            $orderItem->setTotalPrice($item['price'] * $item['quantity']);
            $orderItem->setSize($item['size']);
            $em->persist($orderItem);
        }
        
        $em->flush();

        if ($customer) {
            if ($loyaltyDiscount > 0) {
                $customer->setLoyaltyPoints(max(0, $customer->getLoyaltyPoints() - (int) $loyaltyDiscount));
            }
            
            $earnedPoints = $accountCreated ? 10 : (int) floor($total);
            $customer->addLoyaltyPoints($earnedPoints);
            $em->flush();
        }

        try {
            $paymentMethodLabel = match($paymentMethod) {
                'carte' => 'Carte bancaire',
                'especes' => 'Espèces',
                'paypal' => 'PayPal',
                'swish' => 'Swish',
                default => $paymentMethod,
            };

            $emailBody = $this->renderView('email/order_confirmation.html.twig', [
                'customerName' => $fullName,
                'orderNumber' => $orderNumber,
                'orderDate' => new \DateTimeImmutable(),
                'paymentMethod' => $paymentMethodLabel,
                'totalAmount' => $total,
                'loyaltyEarned' => $accountCreated ? 10 : (int) floor($total),
            ]);

            $email = (new Email())
                ->from($params->get('app.mailer_from') ?: 'contact@bellanapoli.fr')
                ->to($email)
                ->cc($params->get('app.mailer_cc') ?: 'mapro84@gmail.com')
                ->subject('Confirmation de commande - Bella Napoli')
                ->html($emailBody);

            $mailer->send($email);
        } catch (\Exception $e) {
        }

        $session->remove('cart');
        $session->remove('cart_count');
        $session->remove('order_firstName');
        $session->remove('order_lastName');
        $session->remove('order_email');
        $session->remove('order_address');
        $session->remove('order_phone');
        $session->remove('order_create_account');
        $session->remove('order_username');
        $session->remove('order_password');
        $session->remove('payment_method');
        $session->remove('order_notes');
        $session->remove('loyalty_discount');
        $session->remove('loyalty_used');

        return $this->render('cart/confirmation.html.twig', [
            'order_number' => $orderNumber,
            'payment_method' => $paymentMethod,
            'name' => $fullName,
            'account_created' => $accountCreated,
            'loyalty_earned' => $accountCreated ? 10 : (int) floor($total),
            'loyalty_discount' => $loyaltyDiscount,
        ]);
    }

    private function getPizzas(): array
    {
        return [
            1 => ['name' => 'Margherita', 'price' => 10.90, 'image' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=400'],
            2 => ['name' => 'Pepperoni', 'price' => 12.90, 'image' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=400'],
            3 => ['name' => 'Quattro Formaggi', 'price' => 14.90, 'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400'],
            4 => ['name' => 'Diavola', 'price' => 13.90, 'image' => 'https://images.unsplash.com/photo-1604382355076-af4b0eb60143?w=400'],
            5 => ['name' => 'Calzone', 'price' => 15.90, 'image' => 'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?w=400'],
            6 => ['name' => 'Végétarienne', 'price' => 13.90, 'image' => 'https://images.unsplash.com/photo-1511689660979-10d2b1aada49?w=400'],
        ];
    }
}
