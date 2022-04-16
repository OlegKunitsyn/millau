<?php

namespace App\Controller;

use App\Service\SendGridService;
use App\Type\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    public function login(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('route_user_dashboard');
        }
        return $this->render('user/login.html.twig', ['form' => $this->createForm(LoginType::class)->createView()]);
    }

    public function logout(): void
    {
    }

    public function dashboard(SendGridService $service): Response
    {
        $domains = $service->getRegisteredDomains();
        return $this->render('user/dashboard.html.twig', [
            'domains' => $domains
        ]);
    }
}
