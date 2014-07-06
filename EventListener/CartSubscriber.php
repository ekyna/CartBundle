<?php

namespace Ekyna\Bundle\CartBundle\EventListener;

use Ekyna\Bundle\CartBundle\Model\CartProviderInterface;
use Ekyna\Bundle\OrderBundle\Event\OrderEvent;
use Ekyna\Bundle\OrderBundle\Event\OrderEvents;
use Ekyna\Component\Sale\Order\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * CartSubscriber.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Ekyna\Bundle\CartBundle\Model\CartProviderInterface
     */
    private $provider;

    /**
     * Constructor.
     *
     * @param \Ekyna\Bundle\CartBundle\Model\CartProviderInterface $provider
     */
    public function __construct(CartProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Content change event handler.
     * 
     * @param \Ekyna\Bundle\OrderBundle\Event\OrderEvent $event
     */
    public function onContentChange(OrderEvent $event)
    {
        $order = $event->getOrder();
        if ($order->getType() == OrderInterface::TYPE_CART) {
            $this->provider->setCart($order);
        }
    }

    /**
     * State change event handler.
     *
     * @param \Ekyna\Bundle\OrderBundle\Event\OrderEvent $event
     */
    public function onStateChange(OrderEvent $event)
    {
        $order = $event->getOrder();
        $cart  = $this->provider->getCart();
        if ($order->getId() == $cart->getId() && $order->getType() != OrderInterface::TYPE_CART) {
            $this->provider->clearCart();
        }
    }

    /**
     * Delete event handler.
     * 
     * @param OrderEvent $event
     */
    public function onDelete(OrderEvent $event)
    {
        $order = $event->getOrder();
        if ($order->getType() == OrderInterface::TYPE_CART) {
            $this->provider->clearCart();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
    	return array(
    		OrderEvents::CONTENT_CHANGE => array('onContentChange', -1024),
    		OrderEvents::STATE_CHANGE   => array('onStateChange',   -1024),
    		OrderEvents::DELETE         => array('onDelete',        -1024),
    	);
    }
}
