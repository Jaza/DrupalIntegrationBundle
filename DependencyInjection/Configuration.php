<?php

namespace Jaza\DrupalIntegrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jaza_drupalintegration')->children()
            ->variableNode('drupal_root')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_base_url')
              ->defaultValue(null)
              ->end()
            ->booleanNode('drupal_is_embedded')
              ->defaultTrue()
              ->end()
            ->variableNode('drupal_menu_active_item')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_wysiwyg_input_format_id')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_css_includes')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_get_author_username_methodname')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_get_author_uid_methodname')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_admin_roles')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_homelink_route')
              ->defaultValue(null)
              ->end()
            ->variableNode('drupal_homelink_text')
              ->defaultValue(null)
              ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
