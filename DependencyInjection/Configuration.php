<?php

namespace Itmedia\ExpressPayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('itmedia_express_pay');

        $rootNode
            ->children()
                ->scalarNode('token')
                    ->info('API access token')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('api_signature')
                    ->info('Use signature for API')
                    ->defaultFalse()
                ->end()
                ->scalarNode('api_secret')
                    ->info('Secret word for API')
                    ->defaultValue(null)
                ->end()
                ->booleanNode('notification_signature')
                    ->info('Use signature for notifications')
                    ->defaultFalse()
                ->end()
                ->scalarNode('notification_secret')
                    ->info('Secret word for notifications')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('base_url')
                    ->info('API base URL including version')
                    ->defaultValue('https://api.express-pay.by/v1/')
                ->end()
                ->scalarNode('version')
                    ->info('API version')
                    ->defaultValue('1')
                ->end()
                ->scalarNode('return_url')
                    ->info('Return URL for card payment')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('fail_url')
                    ->info('Fail URL for card payment')
                    ->defaultValue(null)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
