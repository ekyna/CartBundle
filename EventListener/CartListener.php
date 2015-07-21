<?php

namespace Ekyna\Bundle\CartBundle\EventListener;

use Ekyna\Bundle\CartBundle\Model\CartProviderInterface;
use Ekyna\Bundle\OrderBundle\Event\OrderEvent;
use Ekyna\Bundle\OrderBundle\Event\OrderEvents;
use Ekyna\Component\Sale\Order\OrderTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CartListener
 * @package Ekyna\Bundle\CartBundle\EventListener
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartListener implements EventSubscriberInterface
{
    /**
     * @var CartProviderInterface
     */
    protected $provider;

    /**
     * Constructor.
     *
     * @param CartProviderInterface $provider
     */
    public function __construct(CartProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Order post content change event handler.
     * 
     * @param OrderEvent $event
     */
    public function onPostContentChange(OrderEvent $event)
    {
        $order = $event->getOrder();
        if ($order->getType() == OrderTypes::TYPE_CART) {
            $this->provider->setCart($order);
        }
    }

    /**
     * Order post state change event handler.
     *
     * @param OrderEvent $event
     */
    public function onPostStateChange(OrderEvent $event)
    {
        $order = $event->getOrder();
        $cart  = $this->provider->getCart();
        if ($order->getId() === $cart->getId() && $order->getType() !== OrderTypes::TYPE_CART) {
            $this->provider->clearCart();
        }
    }

    /**
     * Post delete event handler.
     * 
     * @param OrderEvent $event
     */
    public function onPostDelete(OrderEvent $event)
    {
        $order = $event->getOrder();
        if ($order->getType() === OrderTypes::TYPE_CART) {
            $this->provider->clearCart();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
    	return array(
    		OrderEvents::CONTENT_CHANGE => array('onPostContentChange', -1024),
    		OrderEvents::STATE_CHANGE   => array('onPostStateChange',   -1024),
    		OrderEvents::POST_DELETE    => array('onPostDelete',    -1024),
    	);
    }
}
