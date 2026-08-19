<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

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
                'calculate',
                SubmitType::class,
                [
                    'label' => 'Рассчитать',
                    'attr'  => ['class' => 'btn btn-primary'],
                ]
            )
            ->add(
                'add',
                SubmitType::class,
                [
                    'label' => 'Добавить отпуск',
                    'attr'  => ['class' => 'btn btn-success'],
                ]
            );
    }// end buildForm()
}// end class
