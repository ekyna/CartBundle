<?php

namespace Ekyna\Bundle\CartBundle\Model;

use Ekyna\Component\Sale\Order\OrderInterface;

/**
 * Interface CartProviderInterface
 * @package Ekyna\Bundle\CartBundle\Model
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface CartProviderInterface
{
    /**
     * Stores the cart.
     * 
     * @param OrderInterface $cart
     */
    public function setCart(OrderInterface $cart);

    /**
     * Clears the current cart.
     */
    public function clearCart();

    /**
     * Creates a new cart.
     *
     * @return OrderInterface
     */
    public function newCart();

    /**
     * Returns the stored cart.
     * 
     * @return OrderInterface
     */
    public function getCart();
}
