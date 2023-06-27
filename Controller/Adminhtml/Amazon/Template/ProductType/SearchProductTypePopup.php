<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class SearchProductTypePopup extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->productTypeHelper = $productTypeHelper;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        if (!$marketplaceId) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct marketplace_id.',
            ]);

            return $this->getResult();
        }

        $productTypes = $this->getAvailableProductTypes((int)$marketplaceId);

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\General\SearchPopup $block */
        $block = $this->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\General\SearchPopup::class
            );
        $block->setProductTypes($productTypes);

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    /**
     * @param int $marketplaceId
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAvailableProductTypes(int $marketplaceId): array
    {
        $marketplaceDictionaryItem = $this->productTypeHelper->getMarketplaceDictionary($marketplaceId);
        if (!$marketplaceDictionaryItem->getId()) {
            return [];
        }

        $productTypes = $marketplaceDictionaryItem->getProductTypes();
        if (empty($productTypes)) {
            return [];
        }

        $result = [];
        $alreadyUsedProductTypes = $this->productTypeHelper->getConfiguredProductTypesList($marketplaceId);

        foreach ($productTypes as $productType) {
            $productTypeData = [
                'nick' => $productType['nick'],
                'title' => $productType['title'],
            ];

            if (!empty($alreadyUsedProductTypes[$productType['nick']])) {
                $productTypeData['exist_product_type_id'] = $alreadyUsedProductTypes[$productType['nick']];
            }
            $result[] = $productTypeData;
        }

        return $result;
    }
}
