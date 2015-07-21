<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Ekyna\Bundle\OrderBundle\Form\Type\OrderItemType;

/**
 * Class CartItemType
 * @package Ekyna\Bundle\CartBundle\Form\Type
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartItemType extends OrderItemType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    	return 'ekyna_cart_item';
    }

    /**
     * Returns the fields definitions.
     *
     * @param array $options
     * @return array
     */
    protected function getFields(array $options)
    {
        return array(
            array('quantity', 'integer', array('attr' => array('min' => 1))),
        );
    }
}
