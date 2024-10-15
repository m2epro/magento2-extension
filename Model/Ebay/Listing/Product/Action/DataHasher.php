<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

class DataHasher
{
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(\Ess\M2ePro\Helper\Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    public function hashProductIdentifiers(
        string $upc = null,
        string $ean = null,
        string $isbn = null,
        string $epid = null,
        string $brand = null,
        string $mpn = null
    ): string {
        $productIdentifiers = [
            'upc' => $upc,
            'ean' => $ean,
            'isbn' => $isbn,
            'epid' => $epid,
            'brand' => $brand,
            'mpn' => $mpn,
        ];

        return $this->dataHelper->md5String(
            \Ess\M2ePro\Helper\Json::encode($productIdentifiers)
        );
    }
}
