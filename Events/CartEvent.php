<?php

namespace Ekyna\Bundle\CartBundle\Events;

use Ekyna\Component\Sale\Order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * CartEvent
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartEvent extends Event
{
    /**
     * @var \Ekyna\Component\Sale\Order\OrderInterface
     */
    protected $cart;

    /**
     * Constructor
     * 
     * @param \Ekyna\Component\Sale\Order\OrderInterface $cart
     */
    public function __construct(OrderInterface $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Returns the cart
     * 
     * @return \Ekyna\Component\Sale\Order\OrderInterface
     */
    public function getCart()
    {
        return $this->cart;
    }
}
