<?php

namespace App\Controller;

use App\Entity\Categorie;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CategorieController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {

        $categories = $this->getDoctrine()
                           ->getRepository(Categorie::class)
                           ->getAll();
        return $this->render('categorie/index.html.twig', [
        'categories' => $categories,
        ]);
    }
    
}

