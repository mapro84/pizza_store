<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

final class ArticleController extends AbstractController
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

    #[Route('/articles', name: 'app_articles')]
    public function list(): Response
    {
        return new Response('Liste des articles en magasin : ');
    }

    #[Route('/article/{id}', name: 'app_article_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return new Response('Affichage de l\'article ID : ' . $id);
    }

    #[Route('/article/add', name: 'app_article_add', methods: ['POST', 'GET'])]
    public function add(Request $request, LoggerInterface $logger): Response
    {
        $newarticle = null;

        if ($request->isMethod('POST')) {
            $newarticle = $request->request->get('newarticle');

            // ✅ Check if integer
            if (!ctype_digit((string) $newarticle)) {
                $logger->error('L\'article n\'est pas un entier', [
                    'article' => $newarticle,
                    'date' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $newarticle = (int) $newarticle;
                // ✅ Logger AVANT le return
                $logger->info('Nouvel article ajouté', [
                    'article' => $newarticle,
                    'date' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $this->render('article/add.html.twig', [
            'newarticle' => $newarticle ?? 'Aucun article ajouté',
        ]);
    }
}
