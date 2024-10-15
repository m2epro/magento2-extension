<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

class DescriptionHasher
{
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(\Ess\M2ePro\Helper\Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    public function hashProductDescriptionFields(
        string $description = null,
        string $includeEbayDetails = null,
        string $includeImage = null
    ): string {
        $productDescriptionFields = [
            'description' => $description,
            'include_ebay_details' => $includeEbayDetails,
            'include_image' => $includeImage,
        ];

        return $this->dataHelper->md5String(
            \Ess\M2ePro\Helper\Json::encode($productDescriptionFields)
        );
    }
}
