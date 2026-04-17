<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/customers')]
#[IsGranted('ROLE_ADMIN')]
class CustomerAdminController extends AbstractController
{
    #[Route('/new', name: 'app_admin_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setUsername($request->request->get('username', ''));
            $user->setEmail($request->request->get('email', ''));
            $user->setFirstName($request->request->get('firstName', ''));
            $user->setLastName($request->request->get('lastName', ''));
            $user->setPhone($request->request->get('phone', ''));
            $user->setRoles(['ROLE_USER']);

            $password = $request->request->get('password', 'changeme');
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $em->persist($user);

            $customer = new Customer();
            $customer->setUser($user);
            $customer->setAddress($request->request->get('address', ''));
            $customer->setPostalCode($request->request->get('postalCode', ''));
            $customer->setCity($request->request->get('city', ''));
            $customer->setLoyaltyPoints($request->request->get('loyaltyPoints', 0));
            $customer->setIsVip($request->request->get('isVip') ? true : false);

            $em->persist($customer);
            $em->flush();

            $this->addFlash('success', 'Client créé avec succès.');
            return $this->redirectToRoute('app_admin_customer_index');
        }

        return $this->render('admin/customer/new.html.twig', []);
    }

    #[Route('/', name: 'app_admin_customer_index')]
    public function index(CustomerRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $customers = $repository->findBy([], ['createdAt' => 'DESC']);
        $deleteTokens = [];
        foreach ($customers as $customer) {
            $deleteTokens[$customer->getId()] = $csrfTokenManager->getToken('delete' . $customer->getId())->getValue();
        }

        return $this->render('admin/customer/index.html.twig', [
            'customers' => $customers,
            'deleteTokens' => $deleteTokens,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Customer $customer, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $customer->setFirstName($request->request->get('firstName', ''));
            $customer->setLastName($request->request->get('lastName', ''));
            $customer->setPhone($request->request->get('phone', ''));
            $customer->setAddress($request->request->get('address', ''));
            $customer->setPostalCode($request->request->get('postalCode', ''));
            $customer->setCity($request->request->get('city', ''));
            $customer->setLoyaltyPoints($request->request->get('loyaltyPoints', 0));
            $customer->setIsVip($request->request->get('isVip') ? true : false);

            $em->flush();
            $this->addFlash('success', 'Client modifié avec succès.');
            return $this->redirectToRoute('app_admin_customer_index');
        }

        return $this->render('admin/customer/edit.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_customer_delete')]
    public function delete(Customer $customer, EntityManagerInterface $em): Response
    {
        $user = $customer->getUser();
        $em->remove($customer);
        $em->flush();

        if ($user) {
            $em->remove($user);
            $em->flush();
        }

        $this->addFlash('success', 'Client supprimé avec succès.');
        return $this->redirectToRoute('app_admin_customer_index');
    }
}
