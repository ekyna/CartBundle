<?php

namespace Ekyna\Bundle\CartBundle\Twig;

use Ekyna\Bundle\CartBundle\Model\CartProviderInterface;

/**
 * Class CartExtension
 * @package Ekyna\Bundle\CartBundle\Twig
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartExtension extends \Twig_Extension
{
    /**
     * @var CartProviderInterface
     */
    protected $cartProvider;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Twig_Environment
     */
    protected $twig;


    /**
     * Constructor.
     *
     * @param CartProviderInterface $cartProvider
     * @param array $config
     */
    public function __construct(CartProviderInterface $cartProvider, array $config)
    {
        $this->cartProvider = $cartProvider;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twig = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        return [
            'ekyna_cart_config' => $this->config,
        ];
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('cart_widget', [$this, 'renderCartWidget'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('cart_summary', [$this, 'renderCartSummary'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders the cart widget.
     * 
     * @param array $options
     *
     * @return string
     */
    public function renderCartWidget(array $options = [])
    {
        $template = array_key_exists('template', $options) ? $options['template'] : $this->config['templates']['widget'];
        $cart = array_key_exists('cart', $options) ? $options['cart'] : $this->cartProvider->getCart();

        return $this->twig->render($template, ['cart' => $cart]);
    }

    /**
     * Renders the cart summary.
     * 
     * @param array $options
     *
     * @return string
     */
    public function renderCartSummary(array $options = [])
    {
        $template = array_key_exists('template', $options) ? $options['template'] : $this->config['templates']['summary'];
        $cart = array_key_exists('cart', $options) ? $options['cart'] : $this->cartProvider->getCart();

        return $this->twig->render($template, ['cart' => $cart]);
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
