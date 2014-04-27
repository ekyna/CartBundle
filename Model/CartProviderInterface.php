<?php

namespace Ekyna\Bundle\CartBundle\Model;

use Ekyna\Component\Sale\Order\OrderInterface;

/**
 * CartProviderInterface
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface CartProviderInterface
{
    /**
     * Stores the cart
     * 
     * @param \Ekyna\Component\Sale\Order\OrderInterface $cart
     */
    public function setCart(OrderInterface $cart);

    /**
     * Returns the stored cart.
     * 
     * @return \Ekyna\Component\Sale\Order\OrderInterface
     */
    public function getCart();
}
