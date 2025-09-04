<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Worker;

class WorkerStats
{
    private array $queueTasksQty = [];

    private array $storeTasksQty = [];

    private array $errorsQty = [];

    private array $cyclesQty = [];

    private array $procQty = [];

    public function addCycle(): self
    {
        $this->cyclesQty['cycles'] = ($this->cyclesQty['cycles'] ?? 0) + 1;

        return $this;
    }

    public function addSkipCycle(): self
    {
        $this->cyclesQty['skip-cycle'] = ($this->cyclesQty['skip-cycle'] ?? 0) + 1;

        return $this;
    }

    public function addEmptyCycle(): self
    {
        $this->cyclesQty['empty'] = ($this->cyclesQty['empty'] ?? 0) + 1;

        return $this;
    }

    public function addNoSlotCycle(): self
    {
        $this->cyclesQty['no_slot'] = ($this->cyclesQty['no_slot'] ?? 0) + 1;

        return $this;
    }

    public function addNoMemCycle(): self
    {
        $this->cyclesQty['no_mem'] = ($this->cyclesQty['no_mem'] ?? 0) + 1;

        return $this;
    }

    public function addCooldownCycle(): self
    {
        $this->cyclesQty['cooldown'] = ($this->cyclesQty['cooldown'] ?? 0) + 1;

        return $this;
    }

    public function addStoreQty(string $storeCode): self
    {
        $this->storeTasksQty[$storeCode] = ($this->storeTasksQty[$storeCode] ?? 0) + 1;

        return $this;
    }

    public function addQueueQty(string $queueQty): self
    {
        $this->queueTasksQty[$queueQty] = ($this->queueTasksQty[$queueQty] ?? 0) + 1;

        return $this;
    }

    public function addErrorQty(string $errorName): self
    {
        $this->errorsQty[$errorName] = ($this->errorsQty[$errorName] ?? 0) + 1;

        return $this;
    }

    public function addProcQty(string $name, ?int $newValue = null): self
    {
        $this->procQty[$name] = $newValue ?? ($this->procQty[$name] ?? 0) + 1;

        return $this;
    }

    public function getSuccessRate(): int
    {
        $failed = $this->procQty['failed'] ?? 0;
        $new = $this->procQty['new'] ?? 1;

        return (int)floor((($new - $failed) / $new) * 100);
    }

    public function getCycleEfficiency(): array
    {
        $cycles = $this->cyclesQty['cycles'] ?? 1;
        $skipped = $this->cyclesQty['skip-cycle'] ?? 0;

        $data = [
            'efficiency' => sprintf('%.2f%%', 100 * ($cycles - $skipped) / $cycles),
        ];

        foreach ($this->cyclesQty as $key => $value) {
            if ($key === 'cycles') {
                continue;
            }

            $data[$key] = sprintf('%.2f%%', 100 * $value / $cycles);
        }

        return $data;
    }

    public function getStats(): array
    {
        return [
            'queues' => $this->queueTasksQty,
            'stores' => $this->storeTasksQty,
            'errors' => $this->errorsQty,
            'cycles' => $this->cyclesQty,
            'proc' => $this->procQty,
        ];
    }
}
