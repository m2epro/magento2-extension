<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

class DataHasher
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(\Ess\M2ePro\Helper\Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    public function hashProductIdentifiers(
        string $upc = null,
        string $ean = null,
        string $isbn = null,
        string $epid = null
    ): string {
        $productIdentifiers = [
            'upc' => $upc,
            'ean' => $ean,
            'isbn' => $isbn,
            'epid' => $epid,
        ];

        return $this->dataHelper->md5String(
            \Ess\M2ePro\Helper\Json::encode($productIdentifiers)
        );
    }
}
