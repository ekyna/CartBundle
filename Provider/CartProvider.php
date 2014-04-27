<?php

namespace Ekyna\Bundle\CartBundle\Provider;

use Ekyna\Bundle\OrderBundle\Entity\OrderRepository;
use Ekyna\Bundle\CartBundle\Model\CartProviderInterface;
use Ekyna\Component\Sale\Order\OrderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * CartProvider.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartProvider implements CartProviderInterface
{
    const KEY = 'cart_id';

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;

    /**
     * @var \Ekyna\Bundle\OrderBundle\Entity\OrderRepository
     */
    protected $repository;

    /**
     * @var \Ekyna\Component\Sale\Order\OrderInterface
     */
    protected $cart;

    /**
     * @var string
     */
    protected $key;


    /**
     * Constructor.
     * 
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Ekyna\Bundle\OrderBundle\Entity\OrderRepository           $repository
     */
    public function __construct(SessionInterface $session, OrderRepository $repository)
    {
        $this->session = $session;
        $this->repository = $repository;
        $this->key = self::KEY;
    }

    /**
     * {@inheritdoc}
     */
    public function setCart(OrderInterface $cart)
    {
        $this->cart = $cart;
        $this->session->set($this->key, $cart->getId());
    }

    public function unsetCart()
    {
        $this->session->set($this->key, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getCart()
    {
        if(null === $this->cart) {
            if(null !== $cartId = $this->session->get($this->key, null)) {
                $this->setCart($this->repository->findCart($cartId));
            }
            if(null === $this->cart) {
                $this->setCart($this->repository->createNew());
            }
        }

        return $this->cart;
    }
}
