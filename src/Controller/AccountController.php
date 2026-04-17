<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\PasswordValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/compte')]
class AccountController extends AbstractController
{
    #[Route('/', name: 'app_account')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $user]);

        $orders = [];
        $totalSpent = 0;
        if ($customer) {
            $orders = $em->getRepository(Order::class)
                ->findByCustomer($customer->getId());
            foreach ($orders as $order) {
                $totalSpent += (float) $order->getTotalAmount();
            }
        } else {
            $orders = $em->getRepository(Order::class)
                ->findByEmail($user->getEmail());
            foreach ($orders as $order) {
                $totalSpent += (float) $order->getTotalAmount();
            }
        }

        return $this->render('account/index.html.twig', [
            'customer' => $customer,
            'orders' => $orders,
            'total_spent' => $totalSpent,
        ]);
    }

    #[Route('/profil', name: 'app_account_profile')]
    public function profile(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $user]);

        $orders = [];
        $totalSpent = 0;
        $cumulativePoints = 0;
        if ($customer) {
            $orders = $em->getRepository(Order::class)
                ->findByCustomer($customer->getId());
            foreach ($orders as $order) {
                $totalSpent += (float) $order->getTotalAmount();
                $cumulativePoints += (int) floor((float) $order->getTotalAmount());
            }
        } else {
            $orders = $em->getRepository(Order::class)
                ->findByEmail($user->getEmail());
            foreach ($orders as $order) {
                $totalSpent += (float) $order->getTotalAmount();
                $cumulativePoints += (int) floor((float) $order->getTotalAmount());
            }
        }

        return $this->render('account/profile.html.twig', [
            'customer' => $customer,
            'user' => $user,
            'total_orders' => count($orders),
            'total_spent' => $totalSpent,
            'cumulative_points' => $cumulativePoints,
        ]);
    }

    #[Route('/commandes', name: 'app_account_orders')]
    public function orders(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $user]);

        $orders = [];
        $totalSpent = 0;
        $cumulativePoints = 0;
        if ($customer) {
            $orders = $em->getRepository(Order::class)
                ->findByCustomer($customer->getId());
            foreach ($orders as $order) {
                $totalSpent += (float) $order->getTotalAmount();
                $cumulativePoints += (int) floor((float) $order->getTotalAmount());
            }
        } else {
            $orders = $em->getRepository(Order::class)
                ->findByEmail($user->getEmail());
            foreach ($orders as $order) {
                $totalSpent += (float) $order->getTotalAmount();
                $cumulativePoints += (int) floor((float) $order->getTotalAmount());
            }
        }

        usort($orders, function($a, $b) {
            return $b->getOrderedAt() <=> $a->getOrderedAt();
        });

        return $this->render('account/orders.html.twig', [
            'orders' => $orders,
            'total_orders' => count($orders),
            'total_spent' => $totalSpent,
            'cumulative_points' => $cumulativePoints,
            'loyalty_points' => $customer?->getLoyaltyPoints() ?? 0,
        ]);
    }

    #[Route('/commande/{id}', name: 'app_account_order')]
    public function order(int $id, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(Order::class)->find($id);
        
        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        $user = $this->getUser();
        $customer = $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $user]);

        if (!$customer || ($order->getCustomer() && $order->getCustomer()->getId() !== $customer->getId())) {
            if (!$customer || $order->getCustomerEmail() !== $user->getEmail()) {
                throw $this->createAccessDeniedException();
            }
        }

        $orderItems = $em->getRepository(\App\Entity\OrderItem::class)
            ->findBy(['order' => $order]);

        return $this->render('account/order.html.twig', [
            'order' => $order,
            'orderItems' => $orderItems,
        ]);
    }

    #[Route('/modifier', name: 'app_account_edit', methods: ['GET', 'POST'])]
    public function edit(
        \Symfony\Component\HttpFoundation\Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $em->getRepository(\App\Entity\Customer::class)
            ->findOneBy(['user' => $user]);

        if (!$customer) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $user->setFirstName($request->request->get('firstName', ''));
            $user->setLastName($request->request->get('lastName', ''));
            $user->setPhone($request->request->get('phone', ''));
            $customer->setAddress($request->request->get('address', ''));
            $customer->setPostalCode($request->request->get('postalCode', ''));
            $customer->setCity($request->request->get('city', ''));
            $customer->setFavoritePaymentMethod($request->request->get('favoritePaymentMethod'));
            
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_account_profile');
        }

        return $this->render('account/edit.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        \Symfony\Component\HttpFoundation\Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        PasswordValidator $passwordValidator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password', '');
            $newPassword = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_account_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_account_password');
            }

            if ($passwordHasher->isPasswordValid($user, $newPassword)) {
                $this->addFlash('error', 'Le nouveau mot de passe doit être différent du mot de passe actuel.');
                return $this->redirectToRoute('app_account_password');
            }

            $errors = $passwordValidator->validate($newPassword);
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_account_password');
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_account_profile');
        }

        return $this->render('account/password.html.twig');
    }
}
