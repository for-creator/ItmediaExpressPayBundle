<?php

namespace Itmedia\ExpressPayBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ItmediaExpressPayExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('itmedia_express_pay.token', $config['token']);
        $container->setParameter('itmedia_express_pay.api_signature', $config['api_signature']);
        $container->setParameter('itmedia_express_pay.api_secret', $config['api_secret']);
        $container->setParameter('itmedia_express_pay.notification_signature', $config['notification_signature']);
        $container->setParameter('itmedia_express_pay.notification_secret', $config['notification_secret']);
        $container->setParameter('itmedia_express_pay.base_url', $config['base_url']);
        $container->setParameter('itmedia_express_pay.version', $config['version']);
        $container->setParameter('itmedia_express_pay.return_url', $config['return_url']);
        $container->setParameter('itmedia_express_pay.fail_url', $config['fail_url']);
    }
}
