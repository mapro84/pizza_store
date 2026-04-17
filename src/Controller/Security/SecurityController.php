<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectAfterLogin();
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'is_admin' => false,
        ]);
    }

    #[Route('/login-check', name: 'app_login_check')]
    public function loginCheck(): Response
    {
        throw new \LogicException('This code should never be reached.');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - handled by logout key.');
    }

    #[Route('/admin/login', name: 'app_admin_login')]
    public function adminLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectAfterLogin();
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'is_admin' => true,
        ]);
    }

    #[Route('/admin/login-check', name: 'app_admin_login_check')]
    public function adminLoginCheck(): Response
    {
        throw new \LogicException('This code should never be reached.');
    }

    #[Route('/admin/logout', name: 'app_admin_logout')]
    public function adminLogout(): void
    {
        throw new \LogicException('This method can be blank - handled by logout key.');
    }

    private function redirectAfterLogin(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->redirectToRoute('app_homepage');
    }
}
