<?php

namespace Ekyna\Bundle\CartBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ekyna\Bundle\CartBundle\Model\CartProviderInterface;
use Ekyna\Bundle\CartBundle\Events\CartEvents;
use Ekyna\Bundle\CartBundle\Events\CartEvent;
use Ekyna\Bundle\OrderBundle\Model\UpdaterInterface;

/**
 * CartSubscriber
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Ekyna\Bundle\OrderBundle\Model\UpdaterInterface
     */
    private $updater;

    /**
     * @var \Ekyna\Bundle\CartBundle\Model\CartProviderInterface
     */
    private $provider;

    /**
     * Constructor.
     *
     * @param \Ekyna\Bundle\OrderBundle\Model\UpdaterInterface     $updater
     * @param \Ekyna\Bundle\CartBundle\Model\CartProviderInterface $provider
     */
    public function __construct(UpdaterInterface $updater, CartProviderInterface $provider)
    {
        $this->updater = $updater;
        $this->provider = $provider;
    }

    /**
     * Cart updated event handler
     * 
     * @param \Ekyna\Bundle\CartBundle\Events\CartEvent $event
     */
    public function onCartUpdated(CartEvent $event)
    {
        $cart = $event->getCart();
        $this->updater->update($cart);
    }

    /**
     * Cart saved event handler.
     * 
     * @param \Ekyna\Bundle\CartBundle\Events\CartEvent $event
     */
    public function onCartSaved(CartEvent $event)
    {
        $cart = $event->getCart();
        $this->provider->setCart($cart);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
    	return array(
    		CartEvents::UPDATED => array('onCartUpdated', 0),
    		CartEvents::SAVED   => array('onCartSaved', 0),
    	);
    }
}
