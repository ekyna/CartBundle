<?php

namespace Ekyna\Bundle\CartBundle\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Ekyna\Bundle\CartBundle\Model\CartItemFactoryInterface;
use Ekyna\Bundle\OrderBundle\Entity\OrderItem;
use Ekyna\Bundle\OrderBundle\Entity\OrderItemOption;
use Ekyna\Component\Sale\Product\OptionInterface;
use Ekyna\Component\Sale\Product\ProductInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * CartItemFactory.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartItemFactory implements CartItemFactoryInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * Constructor.
     * 
     * @param \Symfony\Component\Form\FormFactoryInterface               $formFactory
     * @param \Doctrine\ORM\EntityManagerInterface                       $entityManager
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator
     */
    public function __construct(FormFactoryInterface $formFactory, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function createItemFromRequest(Request $request)
    {
        $product = null;
        if(null !== $productId = $request->request->get('ekyna_add_cart_item', array('product' => null))['product']) {
            $product = $this->entityManager->getRepository('EkynaProductBundle:AbstractProduct')->find($productId);
        }
    	$form = $this->buildAddForm($product);

    	$form->handleRequest($request);
    	if (! $form->isValid()) {
    	    // Exception ?
    	}

    	$product = $form->get('product')->getData();

	    $item = $this->createItemFromProduct($product);
	    $item
	       ->setQuantity($form->get('quantity')->getData())
	    ;

	    foreach ($product->getOptionsGroups() as $group) {
	        if (null !== $option = $form->get('option-'.$group)->getData()) {
	            if ($product->hasOption($option)) {
    	            $itemOption = $this->createItemOptionFromOption($option);
    	            $item->addOption($itemOption);
	            }
	        }
	    }

	    return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function createItemFromProduct(ProductInterface $product, array $options = array(), array $extras = array())
    {
        $item = new OrderItem();

        $item
            ->setProduct($product)
            ->setDesignation($product->getDesignation())
            ->setReference($product->getReference())
            ->setPrice($product->getPrice())
            ->setTax($product->getTax())
            ->setWeight($product->getWeight())
            ->setQuantity(1)
            ->setExtras($extras)
        ;

        foreach ($options as $option) {
            $itemOption = $this->createItemOptionFromOption($option);
            $item->addOption($itemOption);
        }

        return $item;
    }

    /**
     * Returns an OrderItemOption created from the given Option.
     *
     * @param \Ekyna\Component\Sale\Product\OptionInterface $option
     *
     * @return \Ekyna\Component\Sale\Order\OrderItemOptionInterface
     */
    protected function createItemOptionFromOption(OptionInterface $option)
    {
        $itemOption = new OrderItemOption();

        $itemOption
            ->setOption($option)
            ->setDesignation($option->getDesignation())
            ->setReference($option->getReference())
            ->setPrice($option->getPrice())
            ->setTax($option->getTax())
            ->setWeight($option->getWeight())
        ;

        return $itemOption;
    }

    /**
     * {@inheritdoc}
     */
    public function buildAddForm(ProductInterface $product = null, $quantity = 1, array $options = array())
    {
        $form = $this->formFactory->create(
            'ekyna_add_cart_item',
            array(
                'product' => $product,
                'quantity' => 1
            ),
            array_merge(array(
                'em'     => $this->entityManager,
                'action' => $this->urlGenerator->generate('ekyna_cart_add_item')
            ), $options)
        );

        return $form;
    }
}
