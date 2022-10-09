<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DaanvanberkelOpenidConnectBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('openid_configuration_url')->end()
                ->scalarNode('client_id')->end()
                ->scalarNode('client_secret')->end()
                ->scalarNode('redirect_uri')->end()
                ->scalarNode('scopes')->end()
                ->integerNode('openid_configuration_ttl')->defaultValue(3600)->end()
                ->scalarNode('audience')->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.yml');

        $container->services()
            ->get('daanvanberkel_openid_connect.openid_settings')
            ->arg(0, $config['openid_configuration_url'])
            ->arg(1, $config['client_id'])
            ->arg(2, $config['client_secret'])
            ->arg(3, $config['redirect_uri'])
            ->arg(4, $config['scopes'])
            ->arg(5, $config['openid_configuration_ttl'])
            ->arg(6, $config['audience']);
    }
}
