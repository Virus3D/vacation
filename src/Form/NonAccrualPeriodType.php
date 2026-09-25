<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\NonAccrualPeriod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NonAccrualPeriodType extends AbstractType
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
                    'label'  => 'С',
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
                    'label'  => 'По',
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
                'type',
                ChoiceType::class,
                [
                    'label'   => 'Тип',
                    'choices' => [
                        'Отпуск по уходу за ребенком'    => 'parental',
                        'Отпуск без сохранения зарплаты' => 'unpaid',
                        'Другое'                         => 'other',
                    ],
                    'attr'    => ['class' => 'form-control'],
                ]
            )
            ->add(
                'comment',
                TextType::class,
                [
                    'label'    => 'Комментарий',
                    'required' => false,
                    'attr'     => ['class' => 'form-control'],
                ]
            );
    }// end buildForm()

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => NonAccrualPeriod::class]);
    }// end configureOptions()
}// end class
