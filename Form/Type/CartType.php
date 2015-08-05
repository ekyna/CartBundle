<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Ekyna\Bundle\AdminBundle\Form\Type\ResourceFormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CartType
 * @package Ekyna\Bundle\CartBundle\Form\Type
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartType extends ResourceFormType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('items', 'ekyna_order_order_items', array(
                'type'         => 'ekyna_cart_item',
                'allow_delete' => false,
                'allow_sort'   => false,
            ))
            ->add('save', 'submit', array(
                'label' => 'ekyna_cart.button.save',
            ))
            ->add('saveAndContinue', 'submit', array(
                'label' => 'ekyna_cart.button.save_and_continue',
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    	return 'ekyna_cart';
    }
}
