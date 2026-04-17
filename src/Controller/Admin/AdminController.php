<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Ingredient;
use App\Entity\Pizza;
use App\Entity\Provider;
use App\Entity\ProviderOrder;
use App\Entity\Customer;
use App\Repository\UserRepository;
use App\Repository\IngredientRepository;
use App\Repository\PizzaRepository;
use App\Repository\ProviderRepository;
use App\Repository\ProviderOrderRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        IngredientRepository $ingredientRepo,
        PizzaRepository $pizzaRepo,
        ProviderRepository $providerRepo,
        ProviderOrderRepository $orderRepo,
        CustomerRepository $customerRepo
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'ingredients' => $ingredientRepo->count([]),
                'pizzas' => $pizzaRepo->count([]),
                'providers' => $providerRepo->count(['isActive' => true]),
                'orders' => $orderRepo->count([]),
                'customers' => $customerRepo->count([]),
            ],
            'recent_orders' => $orderRepo->findRecentOrders(5),
            'pending_orders' => $orderRepo->findPendingOrders(),
        ]);
    }

    #[Route('/logout', name: 'app_admin_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank');
    }
}
