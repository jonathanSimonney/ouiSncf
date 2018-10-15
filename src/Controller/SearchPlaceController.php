<?php

namespace App\Controller;

use App\Entity\Horaire;
use App\Entity\Place;
use App\Form\SearchFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class SearchPlaceController extends AbstractController
{
    /**
     * @Route("/search/place", name="search_place")
     * @Method({"GET", "POST"})
     */
    public function index(Request $request)
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fromPlace = $form->getData()["departure"];
            $toPlace = $form->getData()["destination"];
            $beginningAtTime = $form->getData()["from_time"];

            $results = $this->getDoctrine()
                ->getRepository(Horaire::class)
                ->findFromToBeginningAt($fromPlace, $toPlace, $beginningAtTime);

            return $this->render('search_place/result.html.twig', [
                'controller_name' => 'SearchPlaceController',
                'results' => $results,
            ]);
        }
        return $this->render('search_place/index.html.twig', [
            'controller_name' => 'SearchPlaceController',
            'form' => $form->createView(),
        ]);
    }
}
