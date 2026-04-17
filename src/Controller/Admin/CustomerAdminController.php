<?php

namespace App\Controller\Admin;

use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/customers')]
#[IsGranted('ROLE_ADMIN')]
class CustomerAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_customer_index')]
    public function index(CustomerRepository $repository): Response
    {
        return $this->render('admin/customer/index.html.twig', [
            'customers' => $repository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
}
