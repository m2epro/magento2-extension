<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType
{
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Variation */
    private $variationHelper;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory $diffFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory $changeProcessorFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeSettingsFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct(
            $transactionFactory,
            $amazonFactory,
            $amazonListingProductResource,
            $diffFactory,
            $changeProcessorFactory,
            $listingProductCollectionFactory,
            $productTypeSettingsFactory,
            $productTypeResource,
            $snapshotBuilderFactory,
            $context
        );

        $this->productTypeFactory = $productTypeFactory;
        $this->variationHelper = $variationHelper;
    }

    /**
     * @inheridoc
     */
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $templateId = $this->getRequest()->getParam('product_type_id');
        $isGeneralIdOwnerWillBeSet = (bool)$this->getRequest()->getParam('is_general_id_owner_will_be_set', false);

        if (empty($productsIds) || empty($templateId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $productType = $this->productTypeFactory
            ->create()
            ->load((int)$templateId);
        if (!$productType->getId()) {
            $this->setAjaxContent('You should provide correct product_type_id.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $responseType = 'success';
        $messages = [];

        $filteredProductsIdsByType = $this->variationHelper->filterProductsByMagentoProductType(
            $productsIds,
            true
        );

        if (count($productsIds) !== count($filteredProductsIdsByType)) {
            $responseType = 'warning';
            $text = __(
                'Cannot assign Product Type because %count% items are Bundle Magento Products,'
                . ' this actions is not eligible for this type of Magento Products.',
                count($productsIds) - count($filteredProductsIdsByType)
            );

            $messages[] = [
                'type' => 'warning',
                'text' => $text,
            ];
        }

        if ($productType->getNick() !== \Ess\M2ePro\Model\Amazon\Template\ProductType::GENERAL_PRODUCT_TYPE_NICK) {
            $productIdsWithAvailableWorldwideIds =
                $this->variationHelper->filterProductsByAvailableWorldwideIdentifiers($filteredProductsIdsByType);

            if (count($filteredProductsIdsByType) !== count($productIdsWithAvailableWorldwideIds)) {
                $text = (string) __(
                    'UPC/EAN information could not be located for %count product(s).
                    Before proceeding with adding or updating your Amazon Items,
                    please ensure that either the Product Identifiers settings or Amazon GTIN exemption is configured.',
                    [
                        'count' => count($filteredProductsIdsByType) - count($productIdsWithAvailableWorldwideIds),
                    ]
                );

                $messages[] = [
                    'type' => 'warning',
                    'text' => $text,
                ];
            }
        }

        if (empty($filteredProductsIdsByType)) {
            $this->setJsonContent([
                'type' => $responseType,
                'messages' => $messages,
            ]);

            return $this->getResult();
        }

        $this->setProductTypeForProducts($filteredProductsIdsByType, $templateId, $isGeneralIdOwnerWillBeSet);
        $this->runProcessorForParents($filteredProductsIdsByType);

        $text = $this->__(
            'Product Type was assigned to %count% Products',
            count($filteredProductsIdsByType)
        );
        $messages[] = [
            'type' => 'success',
            'text' => $text,
        ];

        $this->setJsonContent([
            'type' => $responseType,
            'messages' => $messages,
            'products_ids' => implode(',', $filteredProductsIdsByType),
        ]);

        return $this->getResult();
    }
}
