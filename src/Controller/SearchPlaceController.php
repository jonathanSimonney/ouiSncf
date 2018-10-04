<?php

namespace App\Controller;

use App\Form\SearchFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SearchPlaceController extends AbstractController
{
    /**
     * @Route("/search/place", name="search_place")
     */
    public function index()
    {
        $form = $this->createForm(SearchFormType::class);

        return $this->render('search_place/index.html.twig', [
            'controller_name' => 'SearchPlaceController',
            'form' => $form->createView(),
        ]);
    }
}
