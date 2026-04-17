<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {

        $id = 03;
        $nom = 'Pizza Romana';
        $isActive = true;
        $ingredients = ['Mozarella', 'Sauce tomate basilic', 'Poivre blanc'];

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'id' => $id,
            'nom' => $nom,
            'is_active' => $isActive,
            'ingredients' => $ingredients,
        ]);
    }
}
