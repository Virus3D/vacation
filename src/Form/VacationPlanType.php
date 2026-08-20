<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\VacationPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VacationPlanType extends AbstractType
{
    /**
     * @inheritDoc
     */
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
                'status',
                ChoiceType::class,
                [
                    'label'   => 'Статус',
                    'choices' => [
                        'Запланирован' => 'planned',
                        'Утверждён'    => 'approved',
                        'Отклонён'     => 'rejected',
                    ],
                    'attr'    => ['class' => 'form-control'],
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
                'data_class' => VacationPlan::class,
            ]
        );
    }// end configureOptions()
}// end class
