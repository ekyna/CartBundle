<?php

namespace Ekyna\Bundle\CartBundle\Provider;

use Ekyna\Bundle\CartBundle\Model\CartProviderInterface;
use Ekyna\Bundle\OrderBundle\Entity\OrderRepository;
use Ekyna\Component\Sale\Order\OrderInterface;
use Ekyna\Component\Sale\Order\OrderTypes;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class CartProvider
 * @package Ekyna\Bundle\CartBundle\Provider
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartProvider implements CartProviderInterface
{
    const KEY = 'cart_id';

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var OrderRepository
     */
    protected $repository;

    /**
     * @var OrderInterface
     */
    protected $cart;

    /**
     * @var string
     */
    protected $key;


    /**
     * Constructor.
     *
     * @param SessionInterface $session
     * @param OrderRepository $repository
     * @param string $key
     */
    public function __construct(SessionInterface $session, OrderRepository $repository, $key = self::KEY)
    {
        $this->session = $session;
        $this->repository = $repository;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function setCart(OrderInterface $cart)
    {
        $this->cart = $cart;
        $this->cart->setType(OrderTypes::TYPE_CART);
        $this->session->set($this->key, $cart->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function clearCart()
    {
        $this->cart = null;
        $this->session->set($this->key, null);
    }

    /**
     * {@inheritdoc}
     */
    public function newCart()
    {
        $this->clearCart();
        $this->setCart($this->repository->createNew(OrderTypes::TYPE_CART));

        return $this->cart;
    }

    /**
     * {@inheritdoc}
     */
    public function getCart()
    {
        if (null === $this->cart) {
            if (null !== $cartId = $this->session->get($this->key, null)) {
                /** @var \Ekyna\Component\Sale\Order\OrderInterface $cart */
                if (null !== $cart = $this->repository->findOneBy(array('id' => $cartId, 'type' => OrderTypes::TYPE_CART))) {
                    $this->setCart($cart);
                }
            }
            if (null === $this->cart) {
                $this->newCart();
            }
        }

        return $this->cart;
    }
}
