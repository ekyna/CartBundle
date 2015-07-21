<?php

namespace Ekyna\Bundle\CartBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Ekyna\Bundle\CartBundle\DependencyInjection
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ekyna_cart');

        $rootNode
            ->children()
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base')->defaultValue('EkynaCartBundle::base.html.twig')->end()
                        ->scalarNode('email')->defaultValue('EkynaCartBundle::email.html.twig')->end()
                        ->scalarNode('widget')->defaultValue('EkynaCartBundle:Cart:_widget.html.twig')->end()
                        ->scalarNode('summary')->defaultValue('EkynaCartBundle:Cart:_summary.html.twig')->end()
//                        ->scalarNode('add_to_cart')->defaultValue('EkynaCartBundle:Cart:_add_to_cart.html.twig')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
