<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class Description extends AbstractModel
{
    public function getBuilderData(): array
    {
        $this->searchNotFoundAttributes();

        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getDescription();
        $data = $this->getEbayListingProduct()->getDescriptionRenderer()->parseTemplate($data);

        $this->processNotFoundAttributes('Description');

        $descriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();

        return [
            'description' => $data,
            'product_details' => [
                'include_ebay_details' => $descriptionTemplate->isProductDetailsIncludeEbayDetails(),
                'include_image' => $descriptionTemplate->isProductDetailsIncludeImage(),
            ],
        ];
    }
}
