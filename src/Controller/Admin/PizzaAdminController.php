<?php

namespace App\Controller\Admin;

use App\Entity\Pizza;
use App\Repository\PizzaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pizzas')]
#[IsGranted('ROLE_ADMIN')]
class PizzaAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_pizza_index')]
    public function index(PizzaRepository $repository): Response
    {
        return $this->render('admin/pizza/index.html.twig', [
            'pizzas' => $repository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_pizza_edit')]
    public function edit(Pizza $pizza, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $pizza->setName($request->request->get('name'));
            $pizza->setDescription($request->request->get('description'));
            $pizza->setPrice($request->request->get('price'));
            $pizza->setImage($request->request->get('image'));
            $pizza->setIsAvailable($request->request->get('is_available') ? true : false);
            $pizza->setPosition($request->request->get('position', 0));
            
            $em->flush();
            
            $this->addFlash('success', 'Pizza modifiée');
            return $this->redirectToRoute('app_admin_pizza_index');
        }
        
        return $this->render('admin/pizza/edit.html.twig', ['pizza' => $pizza]);
    }
}
