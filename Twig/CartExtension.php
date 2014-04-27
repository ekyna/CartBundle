<?php

namespace Ekyna\Bundle\CartBundle\Twig;

use Ekyna\Bundle\CartBundle\Model\CartProviderInterface;
use Ekyna\Component\Sale\Product\ProductInterface;
use Symfony\Component\Form\FormView;

/**
 * CartExtension.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartExtension extends \Twig_Extension
{
    /**
     * @var \Ekyna\Bundle\CartBundle\Model\CartProviderInterface
     */
    protected $cartProvider;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Twig_Environment
     */
    protected $twig;


    /**
     * Constructor.
     *
     * @param \Ekyna\Bundle\CartBundle\Model\CartProviderInterface $cartProvider
     * @param \Twig_Environment $twig
     */
    public function __construct(CartProviderInterface $cartProvider, array $options)
    {
        $this->cartProvider = $cartProvider;

        $this->options = array_merge(array(
        	'widget_template'           => 'EkynaCartBundle:Cart:_widget.html.twig',
        	'summary_template'          => 'EkynaCartBundle:Cart:_summary.html.twig',
            'add_to_cart_form_template' => 'EkynaCartBundle:Cart:_add_to_cart_form.html.twig',
        ), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twig = $environment;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('cart_widget', array($this, 'renderCartWidget'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('cart_summary', array($this, 'renderCartSummary'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('add_to_cart_form', array($this, 'renderAddToCartForm'), array('is_safe' => array('html'))),
        );
    }

    /**
     * Renders the cart widget.
     * 
     * @param array $options
     *
     * @param string $template
     */
    public function renderCartWidget(array $options = array())
    {
        $template = array_key_exists('template', $options) ? $options['template'] : $this->options['widget_template'];
        $cart = array_key_exists('cart', $options) ? $options['cart'] : $this->cartProvider->getCart();

        return $this->twig->render($template, array('cart' => $cart));
    }

    /**
     * Renders the cart summary.
     * 
     * @param array $options
     *
     * @return string
     */
    public function renderCartSummary(array $options = array())
    {
        $template = array_key_exists('template', $options) ? $options['template'] : $this->options['summary_template'];
        $cart = array_key_exists('cart', $options) ? $options['cart'] : $this->cartProvider->getCart();

        return $this->twig->render($template, array('cart' => $cart));
    }

    /**
     * Renders the "Add to cart" form.
     * 
     * @param \Symfony\Component\Form\FormView               $form
     * @param \Ekyna\Component\Sale\Product\ProductInterface $product
     * @param array                                          $options
     *
     * @return string
     */
    public function renderAddToCartForm(FormView $form, ProductInterface $product, array $options = array())
    {
        $template = array_key_exists('template', $options) ? $options['template'] : $this->options['add_to_cart_form_template'];

        return $this->twig->render($template, array(
            'form'     => $form,
            'product' => $product,
            'options'  => $product->getOptionsGroups()
        ));
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'ekyna_cart';
    }
}
