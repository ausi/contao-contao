<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\AbstractConfigCommand;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\PrototypedArrayNode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ConfigEditorCommand extends AbstractConfigCommand
{
    protected static $defaultName = 'contao:config-editor';
    protected static $defaultDescription = 'Get editor information for the container configuration';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // The configurations to edit
        // Probably provided by the manager plugins
        $configurations = [
            'contao',
            'nelmio_security.clickjacking.paths',
            'framework.mailer.transports',
        ];

        $filter = array_map(
            static function ($configPath) {
                return substr($configPath, 0, strpos($configPath, '.') ?: null);
            },
            $configurations
        );

        $editorInfo = [];

        foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
            $key = $bundle->getName();

            if (!\in_array($key, $filter, true) && ($extension = $bundle->getContainerExtension())) {
                $key = $extension->getAlias();
            }

            if (\in_array($key, $filter, true)) {
                $editorInfo[$key] = $this->getInfoFromBundle($bundle, array_filter(array_map(
                    static function ($configPath) use ($key) {
                        if ($configPath === $key) {
                            return '';
                        }

                        if (0 === strncmp($configPath, $key.'.', \strlen($key) + 1)) {
                            return substr($configPath, \strlen($key) + 1);
                        }

                        return null;
                    },
                    $configurations
                ), static function ($configPath) {
                    return null !== $configPath;
                }));
            }
        }

        $io->writeln(json_encode($editorInfo, JSON_PRETTY_PRINT));

        return 0;
    }

    private function getInfoFromBundle(BundleInterface $bundle, array $paths): array
    {
        $extension = $bundle->getContainerExtension();

        if ($extension instanceof ConfigurationInterface) {
            $configuration = $extension;
        } else {
            $configuration = $extension->getConfiguration([], $this->getContainerBuilder($this->getApplication()->getKernel()));
        }

        $this->validateConfiguration($extension, $configuration);

        $fields = [];

        foreach ($this->getNodesFromPaths($configuration->getConfigTreeBuilder()->buildTree(), $paths) as $node) {
            $nodePath = $node->getPath();
            $nodePath = ltrim(substr($nodePath, strpos($nodePath, '.') ?: 999).'.', '.');
            $fields = array_merge($fields, $this->getFieldsFromNode($node, $nodePath));
        }

        return [
            'name' => $bundle->getName(),
            'alias' => $extension->getAlias(),
            'fields' => $fields,
        ];
    }

    private function getNodesFromPaths(ArrayNode $node, array $paths): array
    {
        if (\in_array('', $paths, true)) {
            return [$node];
        }

        $nodes = [];

        foreach ($paths as $path) {
            $path = explode('.', $path);
            $child = $node->getChildren()[$path[0]];
            array_shift($path);

            if (\count($path)) {
                $child = $this->getNodesFromPaths($child, [implode('.', $path)])[0];
            }
            $nodes[] = $child;
        }

        return $nodes;
    }

    private function getFieldsFromNode(NodeInterface $node, $fieldPrefix = ''): array
    {
        if ($node instanceof PrototypedArrayNode || !$node instanceof ArrayNode) {
            return [
                substr($fieldPrefix, 0, -1) => $this->getConfigFromNode($node),
            ];
        }

        $fields = [];

        foreach ($node->getChildren() as $name => $child) {
            $fullName = $fieldPrefix.$name;

            if ($child instanceof ArrayNode && !$node instanceof PrototypedArrayNode && \count($child->getChildren())) {
                $fields = array_merge($fields, $this->getFieldsFromNode($child, $fullName.'.'));
                continue;
            }
            $fields[$fullName] = $this->getConfigFromNode($child);
        }

        return $fields;
    }

    private function getConfigFromNode(BaseNode $node): array
    {
        $config = [
            'attributes' => $node->getAttributes(),
            'type' => preg_replace('/^Symfony\\\\Component\\\\Config\\\\Definition\\\\/', '', \get_class($node)),
            'required' => $node->isRequired(),
        ];

        if ($node->isDeprecated()) {
            $config['deprecation'] = $node->getDeprecation($node->getName(), $node->getPath());
        }

        if ($node->hasDefaultValue()) {
            $config['default'] = $node->getDefaultValue();
        }

        if ($node instanceof PrototypedArrayNode) {
            $config['key'] = $node->getKeyAttribute();
            $config['prototype'] = $this->getConfigFromNode($node->getPrototype());

            if ($node->getPrototype() instanceof ArrayNode && !$node->getPrototype() instanceof PrototypedArrayNode) {
                $config['prototype']['fields'] = $this->getFieldsFromNode($node->getPrototype());
            }
        }

        return $config;
    }
}
