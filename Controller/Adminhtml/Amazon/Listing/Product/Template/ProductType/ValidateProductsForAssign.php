<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType;

class ValidateProductsForAssign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductType
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Variation */
    private $variationHelper;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory $diffFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory $changeProcessorFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeSettingsFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory $snapshotBuilderFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
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
        $this->variationHelper = $variationHelper;
    }

    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $messages = [];

        $productsIdsLocked = $this->filterLockedProducts($productsIds);

        if (count($productsIds) !== count($productsIdsLocked)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__(
                    'Product Type cannot be assigned because the Products are in Action.'
                ),
            ];
        }

        $filteredProductsIdsByType = $this->variationHelper->filterProductsByMagentoProductType($productsIdsLocked);

        if (count($productsIdsLocked) !== count($filteredProductsIdsByType)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__(
                    'Selected action was not completed for one or more Items. Product Type cannot be assigned
                    to Simple with Custom Options, Bundle and Downloadable with Separated Links Magento Products.'
                ),
            ];
        }

        if (empty($filteredProductsIdsByType)) {
            $this->setJsonContent([
                'messages' => $messages,
            ]);

            return $this->getResult();
        }

        $block = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductType::class);

        if (!empty($messages)) {
            $block->setMessages($messages);
        }

        $this->setJsonContent([
            'html' => $block->toHtml(),
            'messages' => $messages,
            'products_ids' => implode(',', $filteredProductsIdsByType),
        ]);

        return $this->getResult();
    }
}
