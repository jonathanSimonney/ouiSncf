<?php

namespace App\Repository;

use App\Entity\Destination;
use App\Entity\Horaire;
use App\Entity\Place;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Horaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method Horaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method Horaire[]    findAll()
 * @method Horaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HoraireRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Horaire::class);
    }

    /**
     * @param Destination $originalFromPlace
     * @param Destination $originalToPlace
     * @param $departureDateTime
     */
    public function findFromToBeginningAt(Destination $originalFromPlace, Destination $originalToPlace, $departureDateTime){
        //if the places were there, they would be checked in the 2 queries to take only horaire where there isat least one place left
        $departureDay =$departureDateTime->format('Y-m-d');
        $departureTime =$departureDateTime->format('H:i:s');

        //we select the trains (departing after time given by user) AND (going from departure destination OR coming to arrival destination)
        $firstPass = $this->createQueryBuilder('h')
            ->where('(h.day = :depart_day AND h.depart_at >= :depart_time) OR h.day > :depart_day') //departing after time given by user
            ->andWhere('h.from = :from_place OR h.to = :to_place') //going from departure destination OR coming to arrivale destination
            ->setParameter('depart_time', $departureTime)
            ->setParameter('depart_day', $departureDay)
            ->setParameter('from_place', $originalFromPlace)
            ->setParameter('to_place', $originalToPlace)
            ->orderBy('h.depart_at', 'ASC')
            ->getQuery()
            ->getResult();

        $structDepartHoraire = [];
        $structArrivalHoraire = [];
        $arrivalPlaceArray = [$originalToPlace];
        $departPlaceArray = [$originalFromPlace];

        //we fill our array with the result of our first db query. $arrivalPlaceArray will contain all the possible train destination we're interested in, and
        //$departPlaceArray all the possible train departure we're interested in.

        //$structDepartHoraire and $structArrivalHoraire are array of horaire grouped by their origin or arrival gare (this will be useful in last loop)
        foreach ($firstPass as /** @var Horaire $horaire */ $horaire){
            if ($horaire->getFrom()->getId() === $originalFromPlace->getId()){
                $toPlace = $horaire->getTo();
                if (!in_array($toPlace, $departPlaceArray)){
                    $departPlaceArray[] = $toPlace;
                    $structDepartHoraire[$toPlace->getName()] = [$horaire];
                }else{
                    $structDepartHoraire[$toPlace->getName()][] = $horaire;
                }
            }else{
                $fromPlace = $horaire->getFrom();
                if (!in_array($fromPlace, $arrivalPlaceArray)){
                    $arrivalPlaceArray[] = $fromPlace;
                    $structArrivalHoraire[$fromPlace->getName()] = [$horaire];
                }else{
                    $structArrivalHoraire[$fromPlace->getName()][] = $horaire;
                }
            }
        }

        //we select the trains (departing after time given by user) AND (going from one of the interesting departure destination AND coming to one of the interesting arrival destination)
        $intermediateHoraireArray = $this->createQueryBuilder('h')
            ->where('(h.day = :depart_day AND h.depart_at >= :depart_time) OR h.day > :depart_day')
            ->andWhere('h.from IN (:from_place_array) AND h.to IN (:to_place_array)')
            ->setParameter('depart_time', $departureTime)
            ->setParameter('depart_day', $departureDay)
            ->setParameter('from_place_array', $departPlaceArray)
            ->setParameter('to_place_array', $arrivalPlaceArray)
            ->orderBy('h.depart_at', 'ASC')
            ->getQuery()
            ->getResult()
            ;

        $structIntermediateHoraire = [];

        //we iterate over the new horaires gotten to organise them in an array where $structIntermediateHoraire[$fromPlace][$toPlace] = [horaire1FromTo, horaire2FromTo, ...]
        foreach ($intermediateHoraireArray as /** @var Horaire $intermediateHoraire */ $intermediateHoraire){
            $fromPlace = $intermediateHoraire->getFrom()->getName();
            $toPlace = $intermediateHoraire->getTo()->getName();
            if (array_key_exists($fromPlace, $structIntermediateHoraire)){
                if (array_key_exists($toPlace, $structIntermediateHoraire[$fromPlace])) {
                    $structIntermediateHoraire[$fromPlace][$toPlace][] = $intermediateHoraire;
                }else{
                    $structIntermediateHoraire[$fromPlace][$toPlace] = [$intermediateHoraire];
                }
            }else{
                $structIntermediateHoraire[$fromPlace][$toPlace] = [$intermediateHoraire];
            }
        }

        $trajetArray = [];
        //we get with our three arrays ($structDepartHoraire, $structIntermediateHoraire, $structArrivalHoraire) all the traject chaining which are possible a traject being an array of the horaireArray possible from one destination to another
        foreach ($structDepartHoraire as $destinationPlaceName => $departHoraireArray){
            if ($this->isDestinationTheOne($destinationPlaceName, $originalToPlace->getName())){
                $trajetArray[] = [$departHoraireArray];
            }elseif(array_key_exists($destinationPlaceName, $structIntermediateHoraire)){
                $firstCorrTrajectArray = $structIntermediateHoraire[$destinationPlaceName];
                foreach ($firstCorrTrajectArray as $trajectCorrDestName => $corrTrajectHoraireArray){
                    if ($this->isDestinationTheOne($trajectCorrDestName, $originalToPlace->getName())) {
                        $trajetArray[] = [$departHoraireArray, $corrTrajectHoraireArray];
                    }elseif (array_key_exists($trajectCorrDestName, $structArrivalHoraire)){
                        $finalHoraireArray = $structArrivalHoraire[$trajectCorrDestName];
                        $trajetArray[] = [$departHoraireArray, $corrTrajectHoraireArray, $finalHoraireArray];
                    }  //chercher la dernière correspondance dans $arrivalHoraireArray, vérifier la chainabilité, et l'ajouter à trajectArray.
                }
            }
        }

        var_dump(count($trajetArray));
        die;

        $horaireStruct = [];
        foreach ($trajetArray as $trajet){
            $horaireStruct = array_merge($horaireStruct, $this->generateHoraireArray($trajet));
        }

        //$horaireStruct = [[convenientHoraire], [$convenientHoraire1, $convenientHoraireCorr], [$convenientHoraire1, $convenientHoraireCorr, $convenientHoraireFinal]]
        return $horaireStruct;
    }

    protected function generateHoraireArray($traject){//a traject is an array of array of horaire all having same from and to
        //todo
    }

    protected function isDestinationTheOne($destinationName, $finalDestinationName){
        return $destinationName === $finalDestinationName;
    }


//    /**
//     * @return Horaire[] Returns an array of Horaire objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Horaire
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
