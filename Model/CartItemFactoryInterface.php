<?php

namespace Ekyna\Bundle\CartBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Ekyna\Component\Sale\Product\ProductInterface;

/**
 * CartItemFactoryInterface.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface CartItemFactoryInterface
{
    /**
     * Creates and returns an OrderItem from the given request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Ekyna\Component\Sale\Order\OrderItemInterface
     */
    public function createItemFromRequest(Request $request);

    /**
     * Returns an OrderItem created from the given Product and optionnal options.
     *
     * @param \Ekyna\Component\Sale\Product\ProductInterface $product
     * @param \Ekyna\Component\Sale\Product\OptionInterface[] $options An array of options
     *
     * @return \Ekyna\Component\Sale\Order\OrderItemInterface
     */
    public function createItemFromProduct(ProductInterface $product, array $options = array());

    /**
     * Returns a "Add to cart" form.
     *
     * @param \Ekyna\Component\Sale\Product\ProductInterface  $product
     * @param integer                                         $quantity
     * @param array                                           $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function buildAddForm(ProductInterface $product = null, $quantity = 1, array $options = array());
}
