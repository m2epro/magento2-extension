<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

use Ess\M2ePro\Controller\Adminhtml\Context;

abstract class ProductTaxCode extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
{
    protected $transactionFactory;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    )
    {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($amazonFactory, $context);
    }

    //########################################

    protected function filterLockedProducts($productsIdsParam)
    {
        $table = $this->resourceConnection->getTableName('m2epro_processing_lock');

        $productsIds = array();
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {

            $select = $this->resourceConnection->getConnection()->select();
            $select->from(array('lo' => $table), array('object_id'))
                ->where('model_name = "M2ePro/Listing_Product"')
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
        $collection->addFieldToFilter('id', array('in' => $productsIds));
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            return;
        }

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $oldSnapshots = array();

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */

                $oldSnapshots[$listingProduct->getId()] = array_merge(
                    $listingProduct->getDataSnapshot(), $listingProduct->getChildObject()->getDataSnapshot()
                );

                $listingProduct->getChildObject()->setData('template_product_tax_code_id', $templateId);
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldSnapshots = false;
        }

        if (!$oldSnapshots) {
            return;
        }

        foreach ($collection->getItems() as $listingProduct) {
            /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $listingProduct->getChildObject()->setSynchStatusNeed(
                array_merge(
                    $listingProduct->getDataSnapshot(), $listingProduct->getChildObject()->getDataSnapshot()
                ),
                $oldSnapshots[$listingProduct->getId()]
            );
        }
    }

    protected function runProcessorForParents($productsIds)
    {
        $tableAmazonListingProduct = $this->resourceConnection->getTableName('m2epro_amazon_listing_product');

        $select = $this->resourceConnection->getConnection()->select();
        $select->from(array('alp' => $tableAmazonListingProduct), array('listing_product_id'))
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