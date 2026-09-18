<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

/**
 * Holds the MCP tools this server exposes and resolves a `tools/call` name to its tool.
 *
 * The tool set is injected rather than built here, so the registry stays free of `new` and the
 * advertised surface is defined in one place in the service wiring.
 */
class ToolRegistry implements ToolRegistryInterface
{
    /**
     * @var string
     */
    protected const DESCRIPTOR_KEY_NAME = 'name';

    /**
     * @var string
     */
    protected const DESCRIPTOR_KEY_DESCRIPTION = 'description';

    /**
     * @var string
     */
    protected const DESCRIPTOR_KEY_INPUT_SCHEMA = 'inputSchema';

    /**
     * @var array<string, \Demo\Glue\McpCommerce\Tool\ToolInterface>
     */
    protected array $toolsIndexedByName = [];

    /**
     * @param iterable<\Demo\Glue\McpCommerce\Tool\ToolInterface> $tools
     */
    public function __construct(iterable $tools)
    {
        foreach ($tools as $tool) {
            $this->toolsIndexedByName[$tool->getName()] = $tool;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getToolDescriptors(): array
    {
        $toolDescriptors = [];

        foreach ($this->toolsIndexedByName as $tool) {
            $toolDescriptors[] = [
                static::DESCRIPTOR_KEY_NAME => $tool->getName(),
                static::DESCRIPTOR_KEY_DESCRIPTION => $tool->getDescription(),
                static::DESCRIPTOR_KEY_INPUT_SCHEMA => $tool->getInputSchema(),
            ];
        }

        return $toolDescriptors;
    }

    public function findToolByName(string $name): ?ToolInterface
    {
        return $this->toolsIndexedByName[$name] ?? null;
    }
}
