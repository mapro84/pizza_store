<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $success = false;
        $error = null;
        $formData = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'subject' => '',
            'message' => '',
        ];

        if ($request->isMethod('POST')) {
            $formData = [
                'name' => $request->request->get('name', ''),
                'email' => $request->request->get('email', ''),
                'phone' => $request->request->get('phone', ''),
                'subject' => $request->request->get('subject', ''),
                'message' => $request->request->get('message', ''),
            ];

            if (empty($formData['name']) || empty($formData['email']) || empty($formData['message'])) {
                $error = 'Veuillez remplir tous les champs obligatoires.';
            } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Veuillez entrer une adresse email valide.';
            } else {
                try {
                    $email = (new Email())
                        ->from($formData['email'])
                        ->to('contact@bellanapoli.fr')
                        ->cc('mapro84@gmail.com')
                        ->replyTo($formData['email'])
                        ->subject('[' . $formData['subject'] . '] Message de ' . $formData['name'])
                        ->html($this->renderEmailBody($formData));

                    $mailer->send($email);
                    $success = true;
                    $formData = [
                        'name' => '',
                        'email' => '',
                        'phone' => '',
                        'subject' => '',
                        'message' => '',
                    ];
                } catch (\Exception $e) {
                    $success = true;
                    $formData = [
                        'name' => '',
                        'email' => '',
                        'phone' => '',
                        'subject' => '',
                        'message' => '',
                    ];
                }
            }
        }

        return $this->render('contact/index.html.twig', [
            'formData' => $formData,
            'success' => $success,
            'error' => $error,
        ]);
    }

    private function renderEmailBody(array $data): string
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #dc3545;">Nouveau message de contact</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Nom:</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($data['name']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Email:</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><a href="mailto:' . htmlspecialchars($data['email']) . '">' . htmlspecialchars($data['email']) . '</a></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Téléphone:</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($data['phone'] ?: 'Non renseigné') . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Sujet:</strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($data['subject'] ?: 'Non renseigné') . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px;" colspan="2"><strong>Message:</strong></td>
                </tr>
                <tr>
                    <td style="padding: 10px;" colspan="2">' . nl2br(htmlspecialchars($data['message'])) . '</td>
                </tr>
            </table>
            <p style="color: #666; font-size: 12px; margin-top: 20px;">
                Envoyé le ' . date('d/m/Y à H:i') . ' depuis le site Bella Napoli
            </p>
        </div>
        ';
    }
}
