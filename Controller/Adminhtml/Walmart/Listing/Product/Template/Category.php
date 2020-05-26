<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Category
 */
abstract class Category extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template
{
    protected $transactionFactory;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        Context $context
    ) {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($walmartFactory, $context);
    }

    //########################################

    protected function setCategoryTemplateForProducts($productsIds, $templateId)
    {
        if (empty($productsIds)) {
            return;
        }

        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
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
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

                $oldTemplateIds[$listingProduct->getId()] = $listingProduct->getChildObject()
                    ->getData('template_category_id');

                $listingProduct->getChildObject()->setData('template_category_id', $templateId);
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

        $newTemplate = $this->activeRecordFactory->getObjectLoaded(
            'Walmart_Template_Category',
            $templateId,
            null,
            false
        );

        if ($newTemplate !== null && $newTemplate->getId()) {
            $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Category_SnapshotBuilder');
            $snapshotBuilder->setModel($newTemplate);
            $newSnapshot = $snapshotBuilder->getSnapshot();
        } else {
            $newSnapshot = [];
        }

        foreach ($collection->getItems() as $listingProduct) {
            /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $oldTemplate = $this->activeRecordFactory->getObjectLoaded(
                'Walmart_Template_Category',
                $oldTemplateIds[$listingProduct->getId()],
                null,
                false
            );

            if ($oldTemplate !== null && $oldTemplate->getId()) {
                $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Category_SnapshotBuilder');
                $snapshotBuilder->setModel($oldTemplate);
                $oldSnapshot = $snapshotBuilder->getSnapshot();
            } else {
                $oldSnapshot = [];
            }

            if (empty($newSnapshot) && empty($oldSnapshot)) {
                continue;
            }

            $diff = $this->modelFactory->getObject('Walmart_Template_Category_Diff');
            $diff->setOldSnapshot($oldSnapshot);
            $diff->setNewSnapshot($newSnapshot);

            $changeProcessor = $this->modelFactory->getObject('Walmart_Template_Category_ChangeProcessor');
            $changeProcessor->process(
                $diff,
                [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
            );
        }
    }

    //########################################
}
