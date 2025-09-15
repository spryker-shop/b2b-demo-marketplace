<?php

namespace Go\Client\OpenAi\Reader;

interface ToolExecutor {
    public function execute(string $toolName, array $args): array;
    public function bindOperationMap(array $opmap): void;
}
