<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

class StepDeclarationCollection
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclaration[] */
    private array $steps;

    private int $currentStenIndex;
    private int $lastStepIndex;

    public function __construct(array $steps)
    {
        $this->steps = $steps;
        if (empty($this->steps)) {
            throw new \LogicException('Steps not defined.');
        }

        $this->currentStenIndex = 0;
        $this->lastStepIndex = count($this->steps) - 1;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclaration[]
     */
    public function getAll(): array
    {
        return $this->steps;
    }

    public function setCurrent(string $nick): void
    {
        foreach ($this->steps as $index => $step) {
            if ($step->getNick() === $nick) {
                $this->currentStenIndex = $index;

                return;
            }
        }

        throw new \LogicException(sprintf('Step "%s" not found.', $nick));
    }

    public function getFirst(): StepDeclaration
    {
        return $this->steps[0];
    }

    public function findPreviousStep(): ?StepDeclaration
    {
        if ($this->isCurrentStepFirst()) {
            return null;
        }

        return $this->steps[$this->currentStenIndex - 1];
    }

    public function getCurrent(): StepDeclaration
    {
        return $this->steps[$this->currentStenIndex];
    }

    public function getByNick(string $nick): StepDeclaration
    {
        foreach ($this->steps as $step) {
            if ($step->getNick() === $nick) {
                return $step;
            }
        }

        throw new \LogicException(sprintf("Listing Wizard step '%s' not found", $nick));
    }

    public function findNextStep(): ?StepDeclaration
    {
        if ($this->isCurrentStepLast()) {
            return null;
        }

        return $this->steps[$this->currentStenIndex + 1];
    }

    // ----------------------------------------

    private function isCurrentStepFirst(): bool
    {
        return $this->currentStenIndex === 0;
    }

    private function isCurrentStepLast(): bool
    {
        return $this->currentStenIndex === $this->lastStepIndex;
    }
}
