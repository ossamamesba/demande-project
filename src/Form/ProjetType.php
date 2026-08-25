<?php

namespace App\Form;

use App\Entity\Projet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProjetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeDemande', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Type de demande',
                'choices' => [
                    'Créer une demande' => 'creer',
                    'Activer une demande' => 'activer',
                    'Désactiver une demande' => 'desactiver',
                ],
                'placeholder' => 'Choisir une action',
            ])
            ->add('lienProjet', UrlType::class, [
                'label' => 'Lien (projects)',
                'required' => false,
                'attr' => ['placeholder' => 'https://...'],
            ])
            ->add('dataFile', FileType::class, [
                'mapped' => false,
                'label' => 'Data (fichier Excel/CSV)',
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '10M',
                        mimeTypes: [
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ],
                        mimeTypesMessage: 'Merci de déposer un fichier Excel ou CSV valide.',
                    ),
                ],
            ])
            ->add('sao', TextType::class, [
                'label' => 'SAO',
                'required' => false,
            ])
            ->add('codeSap', TextType::class, [
                'label' => 'Code SAP',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Projet::class,
        ]);
    }
}