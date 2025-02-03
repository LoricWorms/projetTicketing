<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    '1-A faire' => '1-A faire',
                    '2-En cours' => '2-En cours',
                    '3-Prêt pour livraison' => '3-Prêt pour livraison',
                    '4-Terminé' => '4-Terminé',
                    '5-En attente' => '5-En attente',
                    '6-URGENT' => '6-URGENT',
                    '7-Litige' => '7-Litige',
                ],
                'expanded' => false, // false pour une liste déroulante, true pour des boutons radio
                'multiple' => false, // false pour une seule sélection
            ])
            ->add('CGV_DECH', ChoiceType::class, [
                'label' => 'CGV DECH',
                'choices' => [
                    'O' => 'O',
                    'N' => 'N',
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'radio-group'],
            ])
            ->add('client', TextType::class, ['label' => 'Client'])
            ->add('date_jour', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('TECH', TextType::class, ['label' => 'TECH', 'required' => false])
            ->add('numero_client', TextType::class, [
                'label' => 'Numéro',
                'attr' => ['class' => 'phone-input'], // Ajout d'une classe pour le ciblage du js
            ])
            ->add('details', TextareaType::class, ['label' => 'Détails/Symptomes', 'required' => false])
            ->add('materiel', TextType::class, ['label' => 'Matériel/Marque', 'required' => false])
            ->add('prestations', TextType::class, ['label' => 'Préstation Proposée', 'required' => false])
            ->add('accepte', ChoiceType::class, [
                'label' => 'Accepté',
                'choices' => [
                    'O' => 'O',
                    'N' => 'N',
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'radio-group'],
            ])
            ->add('resultat', TextType::class, ['label' => 'Résultat', 'required' => false])
            ->add('tarif', TextType::class, ['label' => 'Tarif', 'required' => false])
            ->add('prevenu', ChoiceType::class, [
                'label' => 'Prévenu',
                'choices' => [
                    'O' => 'O',
                    'N' => 'N',
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'radio-group'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
