<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\Ebay\TagFactory as EbayTagFactory;
use Ess\M2ePro\Model\TagFactory as BaseTagFactory;
use Ess\M2ePro\Model\Tag\ListingProduct\Buffer;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Product as WizardProduct;
use Ess\M2ePro\Model\Listing\Product as ListingProduct;
use Ess\M2ePro\Helper\Date as DateHelper;

class ValidationErrorsProcessor
{
    private EbayTagFactory $ebayTagFactory;

    private BaseTagFactory $baseTagFactory;

    private Buffer $tagBuffer;

    public function __construct(
        EbayTagFactory $ebayTagFactory,
        BaseTagFactory $baseTagFactory,
        Buffer $tagBuffer
    ) {
        $this->ebayTagFactory = $ebayTagFactory;
        $this->baseTagFactory = $baseTagFactory;
        $this->tagBuffer = $tagBuffer;
    }

    public function process(ListingProduct $listingProduct, WizardProduct $wizardProduct): void
    {
        $this->clearErrorTags($listingProduct);

        $validationErrors = $wizardProduct->getValidationErrors();

        foreach ($validationErrors as $error) {
            $this->addErrorTags($listingProduct, $error);
        }

        $this->flushErrorTags();
    }

    private function addErrorTags(ListingProduct $listingProduct, array $error): void
    {
        if ($listingProduct->getStatus() !== ListingProduct::STATUS_NOT_LISTED) {
            return;
        }

        $tags[] = $this->baseTagFactory->createWithHasErrorCode();
        $tags[] = $this->ebayTagFactory->createByErrorCode(
            key($error),
            array_shift($error)
        );

        $this->tagBuffer->addTags($listingProduct, $tags);
    }

    private function clearErrorTags(ListingProduct $listingProduct): void
    {
        $this->tagBuffer->removeAllTags($listingProduct);
    }

    private function flushErrorTags(): void
    {
        $this->tagBuffer->flush();
    }
}
