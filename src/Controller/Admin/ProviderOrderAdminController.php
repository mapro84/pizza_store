<?php

namespace App\Controller\Admin;

use App\Entity\ProviderOrder;
use App\Entity\ProviderOrderItem;
use App\Repository\ProviderRepository;
use App\Repository\ProviderOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/provider-orders')]
#[IsGranted('ROLE_ADMIN')]
class ProviderOrderAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_provider_order_index')]
    public function index(ProviderOrderRepository $repository): Response
    {
        return $this->render('admin/provider_order/index.html.twig', [
            'orders' => $repository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_provider_order_new')]
    public function new(Request $request, ProviderRepository $providerRepo, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $order = new ProviderOrder();
            $provider = $providerRepo->find($request->request->get('provider_id'));
            
            if (!$provider) {
                $this->addFlash('error', 'Fournisseur non trouvé');
                return $this->redirectToRoute('app_admin_provider_order_new');
            }
            
            $order->setProvider($provider);
            $order->setNotes($request->request->get('notes'));
            $order->setStatus(ProviderOrder::STATUS_PENDING);
            
            $items = $request->request->get('items', []);
            foreach ($items as $itemData) {
                if (!empty($itemData['name'])) {
                    $item = new ProviderOrderItem();
                    $item->setName($itemData['name']);
                    $item->setItemType($itemData['type'] ?? 'ingredient');
                    $item->setQuantity($itemData['quantity'] ?? 1);
                    $item->setUnitPrice($itemData['price'] ?? 0);
                    $item->setUnit($itemData['unit'] ?? 'Unité');
                    $order->addItem($item);
                }
            }
            
            $order->calculateTotal();
            
            $em->persist($order);
            $em->flush();
            
            $this->addFlash('success', 'Commande créée');
            return $this->redirectToRoute('app_admin_provider_order_index');
        }
        
        return $this->render('admin/provider_order/new.html.twig', [
            'providers' => $providerRepo->findActiveProviders(),
        ]);
    }

    #[Route('/{id}/show', name: 'app_admin_provider_order_show')]
    public function show(ProviderOrder $order): Response
    {
        return $this->render('admin/provider_order/show.html.twig', ['order' => $order]);
    }

    #[Route('/{id}/status', name: 'app_admin_provider_order_status', methods: ['POST'])]
    public function updateStatus(ProviderOrder $order, Request $request, EntityManagerInterface $em): Response
    {
        $order->setStatus($request->request->get('status'));
        if ($request->request->get('status') === 'delivered') {
            $order->setDeliveredAt(new \DateTimeImmutable());
        }
        $em->flush();
        
        $this->addFlash('success', 'Statut mis à jour');
        return $this->redirectToRoute('app_admin_provider_order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_admin_provider_order_delete')]
    public function delete(ProviderOrder $order, EntityManagerInterface $em): Response
    {
        $em->remove($order);
        $em->flush();
        $this->addFlash('success', 'Commande supprimée');
        return $this->redirectToRoute('app_admin_provider_order_index');
    }
}
