<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'fullName',
                TextType::class,
                [
                    'label' => 'ФИО',
                    'attr'  => ['class' => 'form-control'],
                ]
            )
            ->add(
                'hireDate',
                DateType::class,
                [
                    'label'  => 'Дата трудоустройства',
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
                'baseVacationDays',
                IntegerType::class,
                [
                    'label' => 'Основной отпуск (дней)',
                    'attr'  => [
                        'class' => 'form-control',
                        'min'   => 0,
                    ],
                ]
            )
            ->add(
                'additionalVacationDays',
                IntegerType::class,
                [
                    'label' => 'Дополнительный отпуск (дней)',
                    'attr'  => [
                        'class' => 'form-control',
                        'min'   => 0,
                    ],
                ]
            )
            ->add(
                'vacationEntitlements',
                CollectionType::class,
                [
                    'entry_type'    => VacationEntitlementType::class,
                    'allow_add'     => true,
                    'allow_delete'  => true,
                    'by_reference'  => false,
                    'label'         => 'Фиксированные дополнительные дни',
                    'entry_options' => ['label' => false],
                ]
            );
        ;
    }// end buildForm()

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Employee::class,
            ]
        );
    }// end configureOptions()
}// end class
