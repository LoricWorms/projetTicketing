<?php

namespace App\Form;

use App\Entity\Devis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * Class TicketType
 * 
 * Ce formulaire gère la création et la modification des devis.
 */
class DevisType extends AbstractType
{
    /**
     * Configure le formulaire.
     * 
     * @param FormBuilderInterface $builder Le constructeur de formulaire.
     * @param array $options Les options du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', TextType::class, ['label' => 'Client'])
            ->add('date_jour', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false])
            ->add('quantite', TextType::class, ['label' => 'Quantité', 'required' => false])
            ->add('unite', TextType::class, ['label' => 'Unité', 'required' => false])
            ->add('prixUnitHT', TextType::class, ['label' => 'Prix Unitaire HT', 'required' => false])
            ->add('totalHT', TextType::class, ['label' => 'Total HT', 'required' => false])
            ->add('TVA', TextType::class, ['label' => 'TVA', 'required' => false])
            ->add('TTC', TextType::class, ['label' => 'TTC', 'required' => false])

        ;
    }

    /**
     * Configure les options du formulaire.
     * 
     * @param OptionsResolver $resolver Le résolveur d'options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Devis::class,
        ]);
    }
}
