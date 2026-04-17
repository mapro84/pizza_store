<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        if ($customer) {
            $orders = $em->getRepository(Order::class)
                ->findByCustomer($customer->getId());
        } else {
            $orders = $em->getRepository(Order::class)
                ->findByEmail($user->getEmail());
        }

        return $this->render('account/index.html.twig', [
            'customer' => $customer,
            'orders' => $orders,
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
            
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/edit.html.twig', [
            'customer' => $customer,
        ]);
    }
}
