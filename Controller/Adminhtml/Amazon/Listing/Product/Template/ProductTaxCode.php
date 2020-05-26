<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode
 */
abstract class ProductTaxCode extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
{
    protected $transactionFactory;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    ) {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($amazonFactory, $context);
    }

    //########################################

    protected function filterLockedProducts($productsIdsParam)
    {
        $table = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_processing_lock');

        $productsIds = [];
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {
            $select = $this->resourceConnection->getConnection()->select();
            $select->from(['lo' => $table], ['object_id'])
                ->where('model_name = "Listing_Product"')
                ->where('object_id IN (?)', $productsIdsParamChunk)
                ->where('tag IS NOT NULL');

            $lockedProducts = $this->resourceConnection->getConnection()->fetchCol($select);

            foreach ($lockedProducts as $id) {
                $key = array_search($id, $productsIdsParamChunk);
                if ($key !== false) {
                    unset($productsIdsParamChunk[$key]);
                }
            }

            $productsIds = array_merge($productsIds, $productsIdsParamChunk);
        }

        return $productsIds;
    }

    protected function setProductTaxCodeTemplateForProducts($productsIds, $templateId)
    {
        if (empty($productsIds)) {
            return;
        }

        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $productsIds]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            return;
        }

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $oldTemplateIds = [];

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                $oldTemplateIds[$listingProduct->getId()] = $listingProduct->getData('template_product_tax_code_id');
                $listingProduct->getChildObject()->setData('template_product_tax_code_id', $templateId);
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldTemplateIds = false;
            $transaction->rollback();
        }

        if (!$oldTemplateIds) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $newTemplate */
        $newTemplate = $this->activeRecordFactory->getObjectLoaded(
            'Amazon_Template_ProductTaxCode',
            $templateId,
            null,
            false
        );

        if ($newTemplate !== null && $newTemplate->getId()) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_ProductTaxCode_SnapshotBuilder');
            $snapshotBuilder->setModel($newTemplate);
            $newSnapshot = $snapshotBuilder->getSnapshot();
        } else {
            $newSnapshot = [];
        }

        /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $oldTemplate */
            $oldTemplate = $this->activeRecordFactory->getObjectLoaded(
                'Amazon_Template_ProductTaxCode',
                $oldTemplateIds[$listingProduct->getId()],
                null,
                false
            );

            if ($oldTemplate !== null && $oldTemplate->getId()) {
                $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_ProductTaxCode_SnapshotBuilder');
                $snapshotBuilder->setModel($oldTemplate);
                $oldSnapshot = $snapshotBuilder->getSnapshot();
            } else {
                $oldSnapshot = [];
            }

            if (empty($newSnapshot) && empty($oldSnapshot)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\Diff $diff */
            $diff = $this->modelFactory->getObject('Amazon_Template_ProductTaxCode_Diff');
            $diff->setOldSnapshot($oldSnapshot);
            $diff->setNewSnapshot($newSnapshot);

            /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\ChangeProcessor $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject('Amazon_Template_ProductTaxCode_ChangeProcessor');
            $changeProcessor->process(
                $diff,
                [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
            );
        }
    }

    protected function runProcessorForParents($productsIds)
    {
        $tableAmazonListingProduct = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_listing_product');

        $select = $this->resourceConnection->getConnection()->select();
        $select->from(['alp' => $tableAmazonListingProduct], ['listing_product_id'])
            ->where('listing_product_id IN (?)', $productsIds)
            ->where('is_variation_parent = ?', 1);

        $productsIds = $this->resourceConnection->getConnection()->fetchCol($select);

        foreach ($productsIds as $productId) {
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);
            $listingProduct->getChildObject()->getVariationManager()->getTypeModel()->getProcessor()->process();
        }
    }

    //########################################
}
