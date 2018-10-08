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

            var_dump(count($results));
            die;
            return $this->render('search_place/result.html.twig', [
                'controller_name' => 'SearchPlaceController',
                'result' => $results,
            ]);
        }
        return $this->render('search_place/index.html.twig', [
            'controller_name' => 'SearchPlaceController',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/search/place", name="result_place")
     *
     */
    public function getSearchResult(Request $request)
    {
        $form = $this->createForm(SearchFormType::class);


        $results = $this->getDoctrine()
            ->getRepository(Place::class)
            ->findFromToBeginningAt($from_id, $to_id, $beginning_at_time);

        return $this->render('search_place/result.html.twig', [
            'controller_name' => 'SearchPlaceController',
            'result' => $results,
        ]);
    }
}
