<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\SaveCategoryTemplate
 */
class SaveCategoryTemplate extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
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
        if (!$post = $this->getRequest()->getPostValue()) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        if (!isset($post['template_category_data'])) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $categoryTemplatesData = $post['template_category_data'];
        $categoryTemplatesData = $this->getHelper('Data')->jsonDecode($categoryTemplatesData);

        $accountId = $this->getRequest()->getParam('account_id');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $accountId && $converter->setAccountId($accountId);
        $marketplaceId && $converter->setMarketplaceId($marketplaceId);
        foreach ($categoryTemplatesData as $type => $templateData) {
            $converter->setCategoryDataFromChooser($templateData, $type);
        }

        $categoryTmpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_Category'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_MAIN)
        );
        $categorySecondaryTmpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_Category'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_SECONDARY)
        );
        $storeCategoryTmpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_MAIN)
        );
        $storeCategorySecondaryTmpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_SECONDARY)
        );

        $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $this->getRequestIds('products_id')]);

        $snapshots = [];
        $transaction = $this->transactionFactory->create();

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilder $snapshotBuilder */
                $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_SnapshotBuilder');
                $snapshotBuilder->setModel($listingProduct);

                $snapshots[$listingProduct->getId()] = $snapshotBuilder->getSnapshot();

                $listingProduct->getChildObject()->setData(
                    'template_category_id',
                    $categoryTmpl->getId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_category_secondary_id',
                    $categorySecondaryTmpl->getId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_store_category_id',
                    $storeCategoryTmpl->getId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_store_category_secondary_id',
                    $storeCategorySecondaryTmpl->getId()
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
