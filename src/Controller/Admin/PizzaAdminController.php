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
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/pizzas')]
#[IsGranted('ROLE_ADMIN')]
class PizzaAdminController extends AbstractController
{
    private const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const UPLOAD_DIR = 'images/';

    private function handleImageUpload(?UploadedFile $file, ?string $currentImage): ?string
    {
        if (!$file) {
            return $currentImage;
        }

        // Size check
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $this->addFlash('error', 'Image trop volumineuse (max 2 Mo)');
            return $currentImage;
        }

        // MIME validation via finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getPathname());
        if (!in_array($mimeType, self::ALLOWED_MIMES)) {
            $this->addFlash('error', 'Type d\'image non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
            return $currentImage;
        }

        // Sanitize filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitized = strtolower(preg_replace('/[^a-z0-9]/', '-', $originalName));
        $extension = $file->guessExtension();
        $filename = $sanitized . '-' . uniqid() . '.' . $extension;

        // Delete old image if exists and different
        if ($currentImage) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . self::UPLOAD_DIR . $currentImage;
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        // Upload
        $file->move($this->getParameter('kernel.project_dir') . '/public/' . self::UPLOAD_DIR, $filename);

        return $filename;
    }

    #[Route('/new', name: 'app_admin_pizza_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $pizza = new Pizza();
            $pizza->setName($request->request->get('name', ''));
            $pizza->setDescription($request->request->get('description', ''));
            $pizza->setPrice($request->request->get('price', 0));
            $pizza->setIsAvailable($request->request->get('is_available') ? true : false);
            $pizza->setPosition($request->request->get('position', 0));

            $imageFile = $request->files->get('imageFile');
            $filename = $this->handleImageUpload($imageFile, null);
            if ($filename) {
                $pizza->setImage($filename);
            }

            $em->persist($pizza);
            $em->flush();

            $this->addFlash('success', 'Pizza créée avec succès.');
            return $this->redirectToRoute('app_admin_pizza_index');
        }

        return $this->render('admin/pizza/new.html.twig', []);
    }

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
            $pizza->setIsAvailable($request->request->get('is_available') ? true : false);
            $pizza->setPosition($request->request->get('position', 0));

            $imageFile = $request->files->get('imageFile');
            $filename = $this->handleImageUpload($imageFile, $pizza->getImage());
            if ($filename !== null) {
                $pizza->setImage($filename);
            }

            $em->flush();

            $this->addFlash('success', 'Pizza modifiée');
            return $this->redirectToRoute('app_admin_pizza_index');
        }

        return $this->render('admin/pizza/edit.html.twig', ['pizza' => $pizza]);
    }
}
