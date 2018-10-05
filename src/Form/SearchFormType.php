<?php

namespace App\Form;

use App\Entity\Destination;
use App\Repository\DestinationRepository;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('departure', EntityType::class, array(
                'class' => Destination::class,
                'choice_label' => 'name',
                'query_builder' => function (DestinationRepository $dr) {
                    return $dr->createQueryBuilder('d')
                        ->orderBy('d.name', 'ASC');
                },
            ))
            ->add('destination', EntityType::class, array(
                'class' => Destination::class,
                'choice_label' => 'name',
                'query_builder' => function (DestinationRepository $dr) {
                    return $dr->createQueryBuilder('d')
                        ->orderBy('d.name', 'ASC');
                },
            ))
            ->add('from_time', DateTimeType::class, array('data' => (new \DateTime())->modify(' +2 hours')))//add time to be current time
            ->add('search', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
