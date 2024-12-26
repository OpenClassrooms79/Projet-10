<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends AbstractController
{
    public function index(string $title, string $message, int $id): Response
    {
        return $this->render('error/index.html.twig', [
            'title' => $title,
            'message' => sprintf($message, $id),
        ]);
    }
}
