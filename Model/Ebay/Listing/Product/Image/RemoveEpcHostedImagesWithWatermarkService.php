<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Image;

class RemoveEpcHostedImagesWithWatermarkService
{
    private \Magento\Framework\Filesystem\DriverInterface $fsDriver;

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool
    ) {
        $this->fsDriver = $driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct
    ): void {
        if (!$this->canRemoveImages($ebayListingProduct)) {
            return;
        }

        $images = $ebayListingProduct->getDescriptionTemplateSource()
                                     ->getGalleryImages();
        foreach ($images as $image) {
            if ($image->hasWatermark()) {
                try {
                    $this->fsDriver->deleteFile($image->getPath());
                } catch (\Throwable $e) {
                }
            }
        }
    }

    private function canRemoveImages(
        \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct
    ): bool {
        return $ebayListingProduct->isEpcEbayImagesMode()
            && $ebayListingProduct->getEbayDescriptionTemplate()
                                  ->isWatermarkEnabled();
    }
}
