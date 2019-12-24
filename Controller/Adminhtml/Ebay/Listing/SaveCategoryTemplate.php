<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

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

        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listingProductIds = $this->getRequestIds();
        $categoryTemplateData = $post['template_category_data'];
        $categoryTemplateData = $this->getHelper('Data')->jsonDecode($categoryTemplateData);
        // ---------------------------------------

        // ---------------------------------------
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        // ---------------------------------------
        $this->getHelper('Component_Ebay_Category')->fillCategoriesPaths($categoryTemplateData, $listing);

        $builderData = $categoryTemplateData;
        $builderData['account_id'] = $listing->getAccountId();
        $builderData['marketplace_id'] = $listing->getMarketplaceId();

        // ---------------------------------------
        $builder = $this->modelFactory->getObject('Ebay_Template_Category_Builder');
        $categoryTemplate = $builder->build($builderData);
        // ---------------------------------------
        $builder = $this->modelFactory->getObject('Ebay_Template_OtherCategory_Builder');
        $otherCategoryTemplate = $builder->build($builderData);
        // ---------------------------------------

        $this->assignTemplatesToProducts(
            $categoryTemplate->getId(),
            $otherCategoryTemplate->getId(),
            $listingProductIds
        );

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }

    //########################################

    private function assignTemplatesToProducts($categoryTemplateId, $otherCategoryTemplateId, $productsIds)
    {
        if (empty($productsIds)) {
            return;
        }

        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $productsIds]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            return;
        }

        $snapshots   = [];
        $transaction = $this->transactionFactory->create();

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
                $dataSnapshot = array_merge(
                    $listingProduct->getDataSnapshot(),
                    $listingProduct->getChildObject()->getDataSnapshot()
                );
                $snapshots[$listingProduct->getId()] = $dataSnapshot;

                $listingProduct->setData('template_category_id', $categoryTemplateId);
                $listingProduct->setData('template_other_category_id', $otherCategoryTemplateId);
                $listingProduct->getChildObject()->setData('template_category_id', $categoryTemplateId);
                $listingProduct->getChildObject()->setData('template_other_category_id', $otherCategoryTemplateId);

                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $snapshots = false;
        }

        if (!$snapshots) {
            return;
        }

        foreach ($collection->getItems() as $listingProduct) {
            $dataSnapshot = array_merge(
                $listingProduct->getDataSnapshot(),
                $listingProduct->getChildObject()->getDataSnapshot()
            );

            $listingProduct->getChildObject()->setSynchStatusNeed(
                $dataSnapshot,
                $snapshots[$listingProduct->getId()]
            );
        }
    }

    //########################################
}
