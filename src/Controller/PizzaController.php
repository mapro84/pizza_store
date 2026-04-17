<?php

namespace App\Controller;

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
    public function index(): Response
    {
        $pizzas = [
            [
                'id' => 1,
                'name' => 'Margherita',
                'category' => 'Classique',
                'price' => 10.90,
                'image' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=400',
                'description' => 'La pizza traditionnelle napolitaine avec sauce tomate San Marzano, mozzarella di bufala et basilic frais.',
                'ingredients' => ['Sauce tomate', 'Mozzarella', 'Basilic', 'Huile d\'olive'],
            ],
            [
                'id' => 2,
                'name' => 'Pepperoni',
                'category' => 'Classique',
                'price' => 12.90,
                'image' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=400',
                'description' => 'Pizzaохотя avec pepperoni épicé et mozzarella fondue.',
                'ingredients' => ['Sauce tomate', 'Mozzarella', 'Pepperoni', 'Piments'],
            ],
            [
                'id' => 3,
                'name' => 'Quattro Formaggi',
                'category' => 'Classique',
                'price' => 14.90,
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400',
                'description' => 'Délices de quatre fromages: mozzarella, gorgonzola, parmesan et taleggio.',
                'ingredients' => ['Mozzarella', 'Gorgonzola', 'Parmesan', 'Taleggio'],
            ],
            [
                'id' => 4,
                'name' => 'Diavola',
                'category' => 'Classique',
                'price' => 13.90,
                'image' => 'https://images.unsplash.com/photo-1604382355076-af4b0eb60143?w=400',
                'description' => 'Pour les amateurs de spicy: saucisse calabraise et piments forts.',
                'ingredients' => ['Sauce tomate', 'Mozzarella', 'Saucisse calabraise', 'Piments'],
            ],
            [
                'id' => 5,
                'name' => 'Calzone',
                'category' => 'Spéciale',
                'price' => 15.90,
                'image' => 'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?w=400',
                'description' => 'Pizza repliée avec jambon, champignons et œuf.',
                'ingredients' => ['Sauce tomate', 'Mozzarella', 'Jambon', 'Champignons', 'Œuf'],
            ],
            [
                'id' => 6,
                'name' => 'Végétarienne',
                'category' => 'Spéciale',
                'price' => 13.90,
                'image' => 'https://images.unsplash.com/photo-1511689660979-10d2b1aada49?w=400',
                'description' => 'Un festival de légumes frais: courgettes, aubergines, poivrons et olives.',
                'ingredients' => ['Sauce tomate', 'Mozzarella', 'Courgettes', 'Aubergines', 'Poivrons', 'Olives'],
            ],
        ];

        return $this->render('pizza/index.html.twig', [
            'pizzas' => $pizzas,
        ]);
    }

    #[Route('/menu/{id}', name: 'app_pizza_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $pizzas = [
            1 => [
                'id' => 1,
                'name' => 'Margherita',
                'category' => 'Classique',
                'price' => 10.90,
                'image' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=800',
                'description' => 'La Margherita est la pizza traditionnelle napolitaine, créée en 1889 en l\'honneur de la Reine Margherita. Elle symbolise le drapeau italien avec ses couleurs: rouge (tomate), blanc (mozzarella) et vert (basilic). Notre version utilise des tomates San Marzano AOC et de la mozzarella di bufala Campana DOP.',
                'ingredients' => ['Sauce tomate San Marzano', 'Mozzarella di bufala', 'Basilic frais', 'Huile d\'olive extravierge'],
            ],
            2 => [
                'id' => 2,
                'name' => 'Pepperoni',
                'category' => 'Classique',
                'price' => 12.90,
                'image' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=800',
                'description' => 'La Pepperoni est devenue un classique américain-italien. Notre pepperoni est préparé artisanalement avec un mélange d\'épices spéciales qui lui donnent ce goût fumé et légèrement épicé irrésistible.',
                'ingredients' => ['Sauce tomate', 'Mozzarella', 'Pepperoni artisanal', 'Flocons de piment rouge'],
            ],
            3 => [
                'id' => 3,
                'name' => 'Quattro Formaggi',
                'category' => 'Classique',
                'price' => 14.90,
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800',
                'description' => 'Un mélange parfait de quatre fromages italien. Chaque fromage apporte sa propre texture et saveur: la douceur de la mozzarella, la force du gorgonzola, la finesse du parmesan et la crémeux du taleggio.',
                'ingredients' => ['Mozzarella', 'Gorgonzola', 'Parmesan Reggiano', 'Taleggio'],
            ],
        ];

        $pizza = $pizzas[$id] ?? $pizzas[1];

        return $this->render('pizza/show.html.twig', [
            'pizza' => $pizza,
        ]);
    }
}
