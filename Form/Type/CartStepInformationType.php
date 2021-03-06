<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

/**
 * Class CartStepInformationType
 * @package Ekyna\Bundle\CartBundle\Form\Type
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartStepInformationType extends AbstractType
{
    /**
     * @var string
     */
    protected $dataClass;


    /**
     * Constructor.
     * 
     * @param string $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            /** @var \Ekyna\Component\Sale\Order\OrderInterface $cart */
            $cart = $event->getData();
            $form = $event->getForm();

            if (!$cart || null === $cart->getId() || $cart->isEmpty()) {
                throw new \RuntimeException('CartStepInformationType can\'t be used with a non persisted or empty cart.');
            }

            $user = $cart->getUser();
            if (null === $user) {
                $form
                    ->add('identity', 'ekyna_user_identity')
                    ->add('email', 'email', [
                        'label' => 'ekyna_core.field.email',
                    ])
                    ->add('invoiceAddress', 'ekyna_user_address', [
                        'label' => 'ekyna_order.order.field.invoice_address',
                    ])
                ;
                if ($cart->requiresShipment()) {
                    $form
                        ->add('sameAddress', 'checkbox', [
                            'label' => 'ekyna_order.order.field.same_address',
                            'required' => false,
                        ])
                        ->add('deliveryAddress', 'ekyna_user_address', [
                            'label' => 'ekyna_order.order.field.delivery_address',
                            'required' => false,
                        ])
                    ;
                }
            } else {
                $form->add('invoiceAddress', 'ekyna_user_address_choice', [
                    'label' => 'ekyna_order.order.field.invoice_address',
                    'user'  => $user,
                ]);
                if ($cart->requiresShipment()) {
                    $form
                        ->add('sameAddress', 'checkbox', [
                            'label' => 'ekyna_order.order.field.same_address',
                            'required' => false,
                        ])
                        ->add('deliveryAddress', 'ekyna_user_address_choice', [
                            'label' => 'ekyna_order.order.field.delivery_address',
                            'required' => false,
                            'user'  => $user,
                        ])
                    ;
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class'        => $this->dataClass,
                'validation_groups' => ['Default', 'Information']
            ])
            ->setRequired(['data_class'])
            ->setAllowedTypes('data_class', 'string')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    	return 'ekyna_cart_step_information';
    }
}
