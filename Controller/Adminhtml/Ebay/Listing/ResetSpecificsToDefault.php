<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ResetSpecificsToDefault
 */
class ResetSpecificsToDefault extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Magento\Framework\DB\TransactionFactory  */
    protected $transactionFactory = null;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($ebayFactory, $context);
    }

    //########################################

    public function execute()
    {
        $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $this->getRequestIds('products_id')]);

        $transaction = $this->transactionFactory->create();
        try {
            foreach ($collection->getItems() as $listingProduct) {
                $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_SnapshotBuilder');
                $snapshotBuilder->setModel($listingProduct);

                $snapshots[$listingProduct->getId()] = $snapshotBuilder->getSnapshot();
                $listingProduct->getChildObject()->setData(
                    'template_category_id',
                    (int)$this->getRequest()->getParam('template_id')
                );
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $transaction->rollback();

            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $this->updateProcessChanges($collection->getItems(), $snapshots);
        $this->setAjaxContent('1', false);
        return $this->getResult();
    }

    //########################################

    protected function updateProcessChanges($listingProducts, $oldSnapshot)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts\Processor $changesProcessor */
        $changesProcessor = $this->modelFactory->getObject('Ebay_Template_AffectedListingsProducts_Processor');

        foreach ($listingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_SnapshotBuilder');
            $snapshotBuilder->setModel($listingProduct);

            $changesProcessor->setListingProduct($listingProduct);
            $changesProcessor->processChanges(
                $snapshotBuilder->getSnapshot(),
                $oldSnapshot[$listingProduct->getId()]
            );
        }
    }

    //########################################
}
