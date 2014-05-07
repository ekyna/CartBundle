<?php

namespace Ekyna\Bundle\CartBundle\Listener;

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
     * Order post content change event handler.
     * 
     * @param \Ekyna\Bundle\OrderBundle\Event\OrderEvent $event
     */
    public function onOrderPostContentChange(OrderEvent $event)
    {
        $order = $event->getOrder();
        if ($order->getType() == OrderInterface::TYPE_CART) {
            $this->provider->setCart($order);
        }
    }

    /**
     * Order post state change event handler.
     *
     * @param \Ekyna\Bundle\OrderBundle\Event\OrderEvent $event
     */
    public function onOrderPostStateChange(OrderEvent $event)
    {
        $order = $event->getOrder();
        $cart  = $this->provider->getCart();
        if ($order->getId() == $cart->getId() && $order->getType() != OrderInterface::TYPE_CART) {
            $this->provider->clearCart();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
    	return array(
    		OrderEvents::POST_CONTENT_CHANGE => array('onOrderPostContentChange', 0),
    		OrderEvents::POST_STATE_CHANGE   => array('onOrderPostStateChange',   0),
    	);
    }
}
