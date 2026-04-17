<?php

namespace App\Controller\Admin;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ingredients')]
#[IsGranted('ROLE_ADMIN')]
class IngredientAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_ingredient_index')]
    public function index(IngredientRepository $repository): Response
    {
        return $this->render('admin/ingredient/index.html.twig', [
            'ingredients' => $repository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_admin_ingredient_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ingredient = new Ingredient();
        
        if ($request->isMethod('POST')) {
            $ingredient->setName($request->request->get('name'));
            $ingredient->setPrice($request->request->get('price'));
            $ingredient->setIsAllergen($request->request->get('is_allergen') ? true : false);
            $ingredient->setAllergenType($request->request->get('allergen_type'));
            $ingredient->setPosition($request->request->get('position', 0));
            
            $em->persist($ingredient);
            $em->flush();
            
            $this->addFlash('success', 'Ingrédient ajouté avec succès');
            return $this->redirectToRoute('app_admin_ingredient_index');
        }
        
        return $this->render('admin/ingredient/new.html.twig', [
            'ingredient' => $ingredient,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_ingredient_edit')]
    public function edit(Ingredient $ingredient, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $ingredient->setName($request->request->get('name'));
            $ingredient->setPrice($request->request->get('price'));
            $ingredient->setIsAllergen($request->request->get('is_allergen') ? true : false);
            $ingredient->setAllergenType($request->request->get('allergen_type'));
            $ingredient->setPosition($request->request->get('position', 0));
            
            $em->flush();
            
            $this->addFlash('success', 'Ingrédient modifié avec succès');
            return $this->redirectToRoute('app_admin_ingredient_index');
        }
        
        return $this->render('admin/ingredient/edit.html.twig', [
            'ingredient' => $ingredient,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_ingredient_delete')]
    public function delete(Ingredient $ingredient, EntityManagerInterface $em): Response
    {
        $em->remove($ingredient);
        $em->flush();
        
        $this->addFlash('success', 'Ingrédient supprimé');
        return $this->redirectToRoute('app_admin_ingredient_index');
    }
}
