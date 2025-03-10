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
        $source = $this->getEbayListingProduct()
                       ->getDescriptionTemplateSource();

        $condition = $source->getCondition();

        if (!$this->processNotFoundAttributes('Condition')) {
            return [];
        }

        $result = [
            'item_condition' => $condition,
        ];

        $descriptors = $this->getConditionDescriptors($source);
        if (!empty($descriptors)) {
            $result['item_condition_descriptors'] = $descriptors;
        }

        return $result;
    }

    private function getConditionDescriptors(\Ess\M2ePro\Model\Ebay\Template\Description\Source $source): array
    {
        $descriptors = [];
        $conditionDescriptors = $source->getConditionDescriptors();

        foreach ($conditionDescriptors['required_descriptors'] as $descriptorId => $gradeId) {
            $descriptors[] = [
                'name' => (string)$descriptorId,
                'value' => (string)$gradeId,
            ];
        }

        foreach ($conditionDescriptors['optional_descriptors'] as $descriptorId => $gradeVal) {
            $descriptors[] = [
                'name' => (string)$descriptorId,
                'value' => null,
                'additional_info' => $gradeVal,
            ];
        }

        return $descriptors;
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
