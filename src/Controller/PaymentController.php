<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentController extends AbstractController
{
    private array $testCards = [
        '4242424242424242' => ['name' => 'Visa Test', 'status' => 'success'],
        '4000000000000002' => ['name' => 'Visa Declined', 'status' => 'declined'],
        '4000000000009995' => ['name' => 'Visa Insufficient Funds', 'status' => 'insufficient_funds'],
        '5555555555554444' => ['name' => 'Mastercard Test', 'status' => 'success'],
        '5200828282828210' => ['name' => 'Mastercard 3D Secure', 'status' => 'success'],
        '378282246310005' => ['name' => 'American Express Test', 'status' => 'success'],
    ];

    #[Route('/paiement/test', name: 'app_payment_test', methods: ['GET', 'POST'])]
    public function testPayment(Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $total = 0;
        
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }

        if ($request->isMethod('POST')) {
            $cardNumber = preg_replace('/\s+/', '', $request->request->get('card_number', ''));
            $expiry = $request->request->get('expiry', '');
            $cvv = $request->request->get('cvv', '');
            $cardName = $request->request->get('card_name', '');

            if (!$this->validateCard($cardNumber, $expiry, $cvv)) {
                $session->set('payment_error', 'Les informations de la carte sont invalides.');
                return $this->redirectToRoute('app_payment_test');
            }
            
            $cardKey = substr($cardNumber, 0, 16);
            if (!isset($this->testCards[$cardKey])) {
                $session->set('payment_error', 'Carte de test non reconnue. Utilisez un numéro de carte de test valide.');
                return $this->redirectToRoute('app_payment_test');
            }
            
            $cardStatus = $this->testCards[$cardKey]['status'];
            
            if ($cardStatus === 'declined') {
                $session->set('payment_error', 'Votre carte a été refusée. Veuillez contacter votre banque.');
                return $this->redirectToRoute('app_payment_test');
            }
            
            if ($cardStatus === 'insufficient_funds') {
                $session->set('payment_error', 'Fonds insuffisants sur votre carte.');
                return $this->redirectToRoute('app_payment_test');
            }
            
            $session->remove('payment_error');
            $session->set('payment_method', 'carte');
            return $this->redirectToRoute('app_order_confirm');
        }

        return $this->render('payment/test.html.twig', [
            'total' => $total,
            'test_cards' => $this->testCards,
        ]);
    }

    private function validateCard(string $number, string $expiry, string $cvv): bool
    {
        if (!ctype_digit($number) || strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
            return false;
        }

        if (!ctype_digit($cvv) || strlen($cvv) < 3 || strlen($cvv) > 4) {
            return false;
        }

        return $this->luhnCheck($number);
    }

    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        
        return $sum % 10 === 0;
    }
}
