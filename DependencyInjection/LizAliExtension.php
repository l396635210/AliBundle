<?php

namespace Liz\AliBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class LizAliExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $def = $container->getDefinition('liz.service.ali_sms');
        $def->replaceArgument(0, $config['sms']['access_key'])
            ->replaceArgument(1, $config['sms']['access_key_secret'])
            ->replaceArgument(3, $config['sms']['account_id'])
        ;
        $def = $container->getDefinition('liz.service.ali_pay');
        $def->replaceArgument(1, $config['sms']['access_key'])
            ->replaceArgument(2, $config['sms']['access_key_secret'])
        ;
    }
}
