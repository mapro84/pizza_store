<?php

namespace App\Controller;

use App\Repository\PizzaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PizzaController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(): Response
    {
        return $this->render('pizza/homepage.html.twig');
    }

    #[Route('/menu', name: 'app_pizza_index')]
    public function index(PizzaRepository $repository): Response
    {
        return $this->render('pizza/index.html.twig', [
            'pizzas' => $repository->findAll(),
        ]);
    }

    #[Route('/menu/{id}', name: 'app_pizza_show', requirements: ['id' => '\d+'])]
    public function show(int $id, PizzaRepository $repository): Response
    {
        $pizza = $repository->find($id);

        if (!$pizza) {
            throw $this->createNotFoundException('Pizza non trouvée');
        }

        return $this->render('pizza/show.html.twig', [
            'pizza' => $pizza,
        ]);
    }
}