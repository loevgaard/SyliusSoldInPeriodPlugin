<?php

declare(strict_types=1);

namespace Setono\SyliusItemsSoldPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class SetonoSyliusItemsSoldExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $defaultCalculatorDefinition = new Definition(
            'Setono\SyliusItemsSoldPlugin\Calculator\DefaultCalculator', [
            new Reference('sylius.repository.order_item'),
            $config['checkout_states'],
            $config['payment_states'],
            $config['shipping_states'],
            $config['interval'],
        ]);

        $container->setDefinition(
            'setono_sylius_items_sold.calculator.default',
            $defaultCalculatorDefinition
        );

        // If cache is not specified
        if (!array_key_exists('cache', $config)) {
            $container->setAlias(
                'setono_sylius_items_sold.calculator',
                'setono_sylius_items_sold.calculator.default'
            )->setPublic(true);
        } else {
            $cachedCalculatorDefinition = new Definition(
                'Setono\SyliusItemsSoldPlugin\Calculator\CacheableCalculator', [
                new Reference('setono_sylius_items_sold.calculator.default'),
                new Reference($config['cache']['service']),
                $config['cache']['ttl'],
            ]);

            $container->setDefinition(
                'setono_sylius_items_sold.calculator.cached',
                $cachedCalculatorDefinition
            );

            $container->setAlias(
                'setono_sylius_items_sold.calculator',
                'setono_sylius_items_sold.calculator.cached'
            )->setPublic(true);
        }
    }
}
