<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Variation\Product\Manage;

class SaveOptionsMap extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $listingProductRepository;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\SaveOptionMap $saveOptionMap;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $listingProductRepository,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\SaveOptionMap $saveOptionMap,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->listingProductRepository = $listingProductRepository;
        $this->saveOptionMap = $saveOptionMap;
    }

    public function execute()
    {
        $this->saveOptionMap
            ->execute($this->getListingProducts(), $this->getReplacedOptions());

        $this->setJsonContent([
            'success' => true,
        ]);

        return $this->getResult();
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     * @throws \Ess\M2ePro\Model\Exception\EntityNotFound
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getListingProducts(): array
    {
        $currentListingProduct = $this->listingProductRepository
            ->get((int)$this->getRequest()->getParam('listing_product_id'));

        if (!$this->getRequest()->getParam('save_for_all_products', false)) {
            return [$currentListingProduct];
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $currentEbayListingProduct */
        $currentEbayListingProduct = $currentListingProduct->getChildObject();

        $products =  $this->listingProductRepository->getForUpdateReplacedVariationOptions(
            (int)$currentEbayListingProduct->getTemplateCategoryId(),
            $currentListingProduct->getListingId()
        );

        $products[] = $currentListingProduct;

        return $products;
    }

    private function getReplacedOptions(): array
    {
        $replacedOptions = $this->getRequest()->getParam('options');
        if (!is_array($replacedOptions)) {
            return [];
        }

        $result = [];
        foreach ($replacedOptions as $attribute => $options) {
            foreach ($options as $option => $channelOption) {
                if (empty($channelOption)) {
                    continue;
                }

                if ((string)$option === (string)$channelOption) {
                    continue;
                }

                $result[] = new \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\ReplacedOptionDto(
                    (string)$attribute,
                    (string)$option,
                    (string)$channelOption
                );
            }
        }

        return $result;
    }
}
