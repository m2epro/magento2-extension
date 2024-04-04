<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    protected $listingProduct = null;

    /**
     * @var array
     */
    protected $cachedData = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $metaData = [];

    /**
     * @var array
     */
    private $warningMessages = [];

    protected $isVariationItem = false;

    //---------------------------------------

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct): self
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    //---------------------------------------

    public function setCachedData(array $data): self
    {
        $this->cachedData = $data;

        return $this;
    }

    //---------------------------------------

    public function setParams(array $params = []): self
    {
        $this->params = $params;

        return $this;
    }

    //---------------------------------------

    public function setIsVariationItem($isVariationItem): self
    {
        $this->isVariationItem = $isVariationItem;

        return $this;
    }

    //---------------------------------------

    protected function getMarketplace(): \Ess\M2ePro\Model\Marketplace
    {
        return $this->getListing()->getMarketplace();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getEbayMarketplace(): \Ess\M2ePro\Model\Ebay\Marketplace
    {
        return $this->getMarketplace()->getChildObject();
    }

    //---------------------------------------

    protected function getAccount(): \Ess\M2ePro\Model\Account
    {
        return $this->getListing()->getAccount();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getEbayAccount(): \Ess\M2ePro\Model\Ebay\Account
    {
        return $this->getAccount()->getChildObject();
    }

    //---------------------------------------

    protected function getListing(): \Ess\M2ePro\Model\Listing
    {
        return $this->getListingProduct()->getListing();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getEbayListing(): \Ess\M2ePro\Model\Ebay\Listing
    {
        return $this->getListing()->getChildObject();
    }

    //---------------------------------------

    protected function getListingProduct(): \Ess\M2ePro\Model\Listing\Product
    {
        return $this->listingProduct;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getEbayListingProduct(): \Ess\M2ePro\Model\Ebay\Listing\Product
    {
        return $this->getListingProduct()->getChildObject();
    }

    protected function getMagentoProduct(): \Ess\M2ePro\Model\Magento\Product
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    //---------------------------------------

    protected function searchNotFoundAttributes(): void
    {
        $this->getMagentoProduct()->clearNotFoundAttributes();
    }

    protected function processNotFoundAttributes(string $title): bool
    {
        $attributes = $this->getMagentoProduct()->getNotFoundAttributes();

        if (empty($attributes)) {
            return true;
        }

        $this->addNotFoundAttributesMessages($title, $attributes);

        return false;
    }

    //---------------------------------------

    protected function addNotFoundAttributesMessages(string $title, array $attributes): void
    {
        $attributesTitles = $this->getAttributeTitles($attributes);

        $this->addWarningMessage(
            __(
                '%1: Attribute(s) %2 were not found' .
                ' in this Product and its value was not sent.',
                $this->getHelper('Module\Translation')->__($title),
                implode(',', $attributesTitles)
            )
        );
    }

    protected function addFoundAttributesInChildrenMessages(array $attributes): void
    {
        $attributesTitles = $this->getAttributeTitles($attributes);

        $this->addWarningMessage(
            __(
                'The %1: Attribute(s) could not be located in the Parent Product.
                Therefore, %1 values available in the Child Products were synchronized instead.',
                implode(',', $attributesTitles)
            )
        );
    }

    private function getAttributeTitles($attributes): array
    {
        $attributesTitles = [];
        foreach ($attributes as $attribute) {
            $attributesTitles[] = $this->getHelper('Magento\Attribute')
                                       ->getAttributeLabel(
                                           $attribute,
                                           $this->getListing()->getStoreId()
                                       );
        }

        return $attributesTitles;
    }

    //---------------------------------------

    protected function addWarningMessage(string $message): self
    {
        $this->warningMessages[sha1($message)] = $message;

        return $this;
    }

    public function getWarningMessages(): array
    {
        return $this->warningMessages;
    }

    //---------------------------------------

    protected function addMetaData($key, $value): void
    {
        $this->metaData[$key] = $value;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function setMetaData($value): self
    {
        $this->metaData = $value;

        return $this;
    }

    //---------------------------------------

    /**
     * @return array
     */
    abstract public function getBuilderData();

    //---------------------------------------
}
