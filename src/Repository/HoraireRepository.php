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
        //if the places were there, they would be checked in the 2 queries to take only horaire where there is at least one place left
        $departureDay = $departureDateTime->format('Y-m-d');
        $nextDay = $departureDateTime->modify('+ 1 day');
        $departureTime =$departureDateTime->format('H:i:s');

        //we select the trains (departing after time given by user) AND (going from departure destination OR coming to arrival destination)
        $firstPass = $this->createQueryBuilder('h')
//            ->select('f.id as fromId')
//            ->addSelect('t.id as toId')
//            ->addSelect('h')
            ->where('(h.day = :depart_day AND h.depart_at >= :depart_time) OR h.day = :next_depart_day') //departing after time given by user
            ->andWhere('h.from = :from_place OR h.to = :to_place') //going from departure destination OR coming to arrivale destination
            ->setParameter('depart_time', $departureTime)
            ->setParameter('depart_day', $departureDay)
            ->setParameter('next_depart_day', $nextDay)
            ->setParameter('from_place', $originalFromPlace)
            ->setParameter('to_place', $originalToPlace)
//            ->innerJoin('h.from', 'f')
//            ->innerJoin('h.to', 't')
            ->orderBy('h.depart_at', 'ASC')
            ->getQuery()
            ->getResult();

        $structDepartHoraire = [];
        $structArrivalHoraire = [];
        $arrivalPlaceIdArray = [$originalToPlace->getId()];
        $departPlaceIdArray = [$originalFromPlace->getId()];

        //we fill our array with the result of our first db query. $arrivalPlaceArray will contain all the possible train destination we're interested in, and
        //$departPlaceArray all the possible train departure we're interested in.

        //$structDepartHoraire and $structArrivalHoraire are array of horaire grouped by their origin or arrival gare (this will be useful in last loop)
        foreach ($firstPass as  $horaire){
//            /** @var Horaire $horaire */ $horaire = $horaireAndComp[0];
            if ($horaire->getFrom() === $originalFromPlace){
                $toPlace = $horaire->getTo();
                if (!in_array($toPlace, $departPlaceIdArray)){
                    $departPlaceIdArray[] = $toPlace->getId();
                    $structDepartHoraire[$toPlace->getName()] = [$horaire];
                }else{
                    $structDepartHoraire[$toPlace->getName()][] = $horaire;
                }
            }else{
                $fromPlace = $horaire->getFrom();
                if (!in_array($fromPlace, $arrivalPlaceIdArray)){
                    $arrivalPlaceIdArray[] = $fromPlace->getId();
                    $structArrivalHoraire[$fromPlace->getName()] = [$horaire];
                }else{
                    $structArrivalHoraire[$fromPlace->getName()][] = $horaire;
                }
            }
        }

        //we select the trains (departing after time given by user) AND (going from one of the interesting departure destination AND coming to one of the interesting arrival destination)
        $intermediateHoraireArray = $this->createQueryBuilder('h')
            ->where('(h.day = :depart_day AND h.depart_at >= :depart_time) OR h.day = :next_depart_day') //departing after time given by user
            ->andWhere('h.from IN (:from_place_id_array) AND h.to IN (:to_place_id_array)')
            ->setParameter('depart_time', $departureTime)
            ->setParameter('depart_day', $departureDay)
            ->setParameter('next_depart_day', $nextDay)
            ->setParameter('from_place_id_array', $departPlaceIdArray)
            ->setParameter('to_place_id_array', $arrivalPlaceIdArray)
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
                    }
                }
            }
        }

        $horaireStruct = [];
        foreach ($trajetArray as $trajet){
            $horaireStruct = array_merge($horaireStruct, $this->generateHoraireArray($trajet));
        }

        //$horaireStruct = [[convenientHoraire], [$convenientHoraire1, $convenientHoraireCorr], [$convenientHoraire1, $convenientHoraireCorr, $convenientHoraireFinal]]
        usort($horaireStruct, array($this, "trajectSort"));
//        $this->echo_memory_peak_usage();
//        $this->echo_memory_peak_usage(true);
        return $horaireStruct;
    }

//    protected function echo_memory_peak_usage($real_usage=false) {
//        $mem_usage = memory_get_peak_usage($real_usage);
//
//        if ($mem_usage < 1024)
//            echo $mem_usage." bytes";
//        elseif ($mem_usage < 1048576)
//            echo round($mem_usage/1024,2)." kilobytes";
//        else
//            echo round($mem_usage/1048576,2)." megabytes";
//
//        echo "<br/>";
//    }

    protected function generateHoraireArray($traject){//a traject is an array of array of horaire all having same from and to
        $ret = [];
        $firstPass = true;
        foreach ($traject as /**@var Horaire[] $arrayHoraire */ $arrayHoraire){
            if ($firstPass){//the first time, we fill our ret with the first part of the horaire
                $firstPass = false;
                foreach ($arrayHoraire as $horaire) {
                    $ret[] = [$horaire];
                }
            }else{//the other time, we add the correspondance horaire to all the arrays currently in $ret
                $ret = $this->expandHoraireArrayWithNextCorr($ret, $arrayHoraire);
            }
        }
        return $ret;
    }

    protected function expandHoraireArrayWithNextCorr($structHoraire, $arrayCorr){
        $ret = [];
        foreach ($structHoraire as $arrayHoraire){
            foreach ($arrayCorr as $additionalHoraire) {
                if ($this->checkChainability($arrayHoraire, $additionalHoraire)){
                    $ret[] = array_merge($arrayHoraire, [$additionalHoraire]);
                }
            }
        }
        return $ret;
    }

    protected static function trajectSort(/** @var Horaire[] $traject1 */ $traject1, /** @var Horaire[] $traject2 */ $traject2){
        //if traject1 is better, return -1
        $traject1Time = self::getTrajectTime($traject1);
        $traject2Time = self::getTrajectTime($traject2);
        return ($traject1Time < $traject2Time) ? -1 : 1;
    }

    protected static function getTrajectTime(/** @var Horaire[] $traject */ $traject){
        $ret = end($traject)->getArriveAt()->diff($traject[0] ->getDepartAt());
        if (end($traject)->getDay() !== $traject[0]->getDay()){
            $e = new \DateTime('00:00');
            $f = clone $e;
            $e->add($ret);
            $e->modify('+1 day');
            $ret = $f->diff($e);
        }
        return $ret;
    }

    protected function isDestinationTheOne($destinationName, $finalDestinationName){
        return $destinationName === $finalDestinationName;
    }

    protected function checkChainability(/** @var Horaire[] $arrayHoraire */ $arrayHoraire, Horaire $horaire){
        $lastHoraire = end($arrayHoraire);
        if ($lastHoraire->getDay() < $horaire->getDay()){//a bit too large maybe? (correspondance with 3 days of difference...)
            return true;
        }
        return $lastHoraire->getArriveAt() < $horaire->getDepartAt();
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
