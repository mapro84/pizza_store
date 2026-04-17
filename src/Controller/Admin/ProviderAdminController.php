<?php

namespace App\Controller\Admin;

use App\Entity\Provider;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/providers')]
#[IsGranted('ROLE_ADMIN')]
class ProviderAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_provider_index')]
    public function index(ProviderRepository $repository): Response
    {
        return $this->render('admin/provider/index.html.twig', [
            'providers' => $repository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_provider_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $provider = new Provider();
            $provider->setName($request->request->get('name'));
            $provider->setType($request->request->get('type'));
            $provider->setContactName($request->request->get('contact_name'));
            $provider->setEmail($request->request->get('email'));
            $provider->setPhone($request->request->get('phone'));
            $provider->setAddress($request->request->get('address'));
            $provider->setPostalCode($request->request->get('postal_code'));
            $provider->setCity($request->request->get('city'));
            $provider->setNotes($request->request->get('notes'));
            $provider->setIsActive(true);
            
            $em->persist($provider);
            $em->flush();
            
            $this->addFlash('success', 'Fournisseur ajouté');
            return $this->redirectToRoute('app_admin_provider_index');
        }
        
        return $this->render('admin/provider/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_admin_provider_edit')]
    public function edit(Provider $provider, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $provider->setName($request->request->get('name'));
            $provider->setType($request->request->get('type'));
            $provider->setContactName($request->request->get('contact_name'));
            $provider->setEmail($request->request->get('email'));
            $provider->setPhone($request->request->get('phone'));
            $provider->setAddress($request->request->get('address'));
            $provider->setPostalCode($request->request->get('postal_code'));
            $provider->setCity($request->request->get('city'));
            $provider->setNotes($request->request->get('notes'));
            
            $em->flush();
            
            $this->addFlash('success', 'Fournisseur modifié');
            return $this->redirectToRoute('app_admin_provider_index');
        }
        
        return $this->render('admin/provider/edit.html.twig', ['provider' => $provider]);
    }

    #[Route('/{id}/delete', name: 'app_admin_provider_delete')]
    public function delete(Provider $provider, EntityManagerInterface $em): Response
    {
        $em->remove($provider);
        $em->flush();
        $this->addFlash('success', 'Fournisseur supprimé');
        return $this->redirectToRoute('app_admin_provider_index');
    }
}
