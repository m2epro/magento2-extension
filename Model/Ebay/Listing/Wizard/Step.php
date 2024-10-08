<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\ActiveRecord\AbstractModel;
use Ess\M2ePro\Model\Ebay\Listing\Wizard as WizardModel;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Step as StepResource;

class Step extends AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(StepResource::class);
    }

    public function init(WizardModel $wizard, string $nick): self
    {
        $this->setData(StepResource::COLUMN_WIZARD_ID, $wizard->getId())
             ->setData(StepResource::COLUMN_NICK, $nick);

        return $this;
    }

    public function getWizardId(): int
    {
        return (int)$this->getData(StepResource::COLUMN_WIZARD_ID);
    }

    public function getNick(): string
    {
        return $this->getData(StepResource::COLUMN_NICK);
    }

    public function setResultData(array $data): self
    {
        $this->setData(StepResource::COLUMN_DATA, json_encode($data));

        return $this;
    }

    public function getResultData(): array
    {
        $value = $this->getData(StepResource::COLUMN_DATA);
        if (empty($value)) {
            return [];
        }

        return (array)json_decode($value, true);
    }

    public function isSkipped(): bool
    {
        return (bool)$this->getData(StepResource::COLUMN_IS_SKIPPED);
    }

    public function isCompleted(): bool
    {
        return (bool)$this->getData(StepResource::COLUMN_IS_COMPLETED);
    }

    public function complete(): self
    {
        $this->changeCompleteStatus(true, false);

        return $this;
    }

    public function skip(): self
    {
        $this->changeCompleteStatus(true, true);

        return $this;
    }

    public function notComplete(): self
    {
        $this->changeCompleteStatus(false, false)
             ->setResultData([]);

        return $this;
    }

    private function changeCompleteStatus(bool $isCompleted, bool $isSkipped): self
    {
        $this->setData(StepResource::COLUMN_IS_COMPLETED, (int)$isCompleted)
             ->setData(StepResource::COLUMN_IS_SKIPPED, (int)$isSkipped);

        return $this;
    }
}
