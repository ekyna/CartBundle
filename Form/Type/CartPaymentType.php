<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * Class CartPaymentType
 * @package Ekyna\Bundle\CartBundle\Form
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartPaymentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'ekyna_order_order_payment';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ekyna_cart_payment';
    }
}
