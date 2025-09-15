<?php

namespace Go\Client\OpenAi\Reader;
interface ModelResponseInterface
{
    public function create(array $messages, string $instructions = null, array $tools = []): array;

    public function createForAgent(array $messages): array;
}
