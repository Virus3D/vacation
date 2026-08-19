<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VacationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    'label'  => 'Дата начала',
                    'widget' => 'single_text',
                    'html5'  => false,
                    'format' => 'dd.MM.yyyy',
                    'attr'   => [
                        'class'        => 'form-control datepicker',
                        'autocomplete' => 'off',
                        'placeholder'  => 'дд.мм.гггг',
                    ],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    'label'  => 'Дата окончания',
                    'widget' => 'single_text',
                    'html5'  => false,
                    'format' => 'dd.MM.yyyy',
                    'attr'   => [
                        'class'        => 'form-control datepicker',
                        'autocomplete' => 'off',
                        'placeholder'  => 'дд.мм.гггг',
                    ],
                ]
            )
            ->add(
                'auto_calculate',
                CheckboxType::class,
                [
                    'label'    => 'Автоматически рассчитать использование дней',
                    'required' => false,
                    'data'     => true,
                    'attr'     => ['class' => 'form-check-input auto-calculate-checkbox'],
                ]
            )
            ->add(
                'details',
                CollectionType::class,
                [
                    'entry_type'    => VacationDetailType::class,
                    'allow_add'     => true,
                    'allow_delete'  => true,
                    'by_reference'  => false,
                    'required'      => false,
                    'label'         => false,
                    'entry_options' => [
                        'work_years_choices' => $options['work_years_choices'],
                    ],
                    'prototype'     => true,
                ]
            )
            ->add(
                'calculate',
                SubmitType::class,
                [
                    'label' => 'Рассчитать',
                    'attr'  => ['class' => 'btn btn-primary'],
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'Добавить отпуск',
                    'attr'  => ['class' => 'btn btn-success'],
                ]
            );
    }// end buildForm()

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'work_years_choices' => [],
            ]
        );
        $resolver->setAllowedTypes('work_years_choices', 'array');
    }// end configureOptions()
}// end class
