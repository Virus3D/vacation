<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\VacationDetail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VacationDetailType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'workYear',
                ChoiceType::class,
                [
                    'label'       => 'Рабочий год',
                    'choices'     => $options['work_years_choices'],
                    'mapped'      => false,
                    'placeholder' => 'Выберите год',
                    'attr'        => ['class' => 'form-control work-year-select'],
                ]
            )
            ->add(
                'vacationType',
                ChoiceType::class,
                [
                    'label'   => 'Тип дней',
                    'choices' => [
                        'Основной'       => 'main',
                        'Дополнительный' => 'additional',
                    ],
                    'attr'    => ['class' => 'form-control vacation-type-select'],
                ]
            )
            ->add(
                'daysUsed',
                IntegerType::class,
                [
                    'label' => 'Количество дней',
                    'attr'  => [
                        'class' => 'form-control days-used',
                        'min'   => 1,
                    ],
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
                'data_class'         => VacationDetail::class,
                'work_years_choices' => [],
            ]
        );
        $resolver->setAllowedTypes('work_years_choices', 'array');
    }// end configureOptions()
}// end class
