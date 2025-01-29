<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class Condition extends AbstractModel
{
    public function getBuilderData(): array
    {
        return array_merge(
            $this->getConditionData(),
            $this->getConditionNoteData(),
        );
    }

    private function getConditionData(): array
    {
        $this->searchNotFoundAttributes();
        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getCondition();

        if (!$this->processNotFoundAttributes('Condition')) {
            return [];
        }

        return [
            'item_condition' => $data,
        ];
    }

    private function getConditionNoteData(): array
    {
        $this->searchNotFoundAttributes();
        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getConditionNote();
        $this->processNotFoundAttributes('Seller Notes');

        return [
            'item_condition_note' => $data,
        ];
    }
}
