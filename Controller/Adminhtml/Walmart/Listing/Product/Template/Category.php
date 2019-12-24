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
        $oldSnapshots = [];

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

                $oldSnapshots[$listingProduct->getId()] = array_merge(
                    $listingProduct->getDataSnapshot(),
                    $listingProduct->getChildObject()->getDataSnapshot()
                );

                $listingProduct->getChildObject()->setData('template_category_id', $templateId);
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldSnapshots = false;
            $transaction->rollback();
        }

        if (!$oldSnapshots) {
            return;
        }

        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $listingProduct->getChildObject()->setSynchStatusNeed(
                array_merge(
                    $listingProduct->getDataSnapshot(),
                    $listingProduct->getChildObject()->getDataSnapshot()
                ),
                $oldSnapshots[$listingProduct->getId()]
            );
        }
    }

    //########################################
}
