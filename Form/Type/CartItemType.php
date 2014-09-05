<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Ekyna\Bundle\AdminBundle\Form\Type\ResourceFormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * CartItemType
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartItemType extends ResourceFormType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', 'integer', array('attr' => array('min' => 1)))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    	return 'ekyna_cart_item';
    }
}
