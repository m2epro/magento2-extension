<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class SaveCategoryTemplate extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected $transaction;

    //########################################

    public function __construct(
        \Magento\Framework\DB\Transaction $transaction,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        $this->transaction = $transaction;
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
        $this->getHelper('Component\Ebay\Category')->fillCategoriesPaths($categoryTemplateData, $listing);

        $builderData = $categoryTemplateData;
        $builderData['account_id'] = $listing->getAccountId();
        $builderData['marketplace_id'] = $listing->getMarketplaceId();

        // ---------------------------------------
        $builder = $this->modelFactory->getObject('Ebay\Template\Category\Builder');
        $categoryTemplate = $builder->build($builderData);
        // ---------------------------------------
        $builder = $this->modelFactory->getObject('Ebay\Template\OtherCategory\Builder');
        $otherCategoryTemplate = $builder->build($builderData);
        // ---------------------------------------

        $this->assignTemplatesToProducts($categoryTemplate->getId(),$otherCategoryTemplate->getId(),$listingProductIds);

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
        $collection->addFieldToFilter('id', array('in' => $productsIds));
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            return;
        }

        $snapshots = array();

        try {
            foreach ($collection->getItems() as $listingProduct) {
                $dataSnapshot = array_merge(
                    $listingProduct->getDataSnapshot(),
                    $listingProduct->getChildObject()->getDataSnapshot()
                );
                $snapshots[$listingProduct->getId()] = $dataSnapshot;

                $listingProduct->setData('template_category_id', $categoryTemplateId);
                $listingProduct->setData('template_other_category_id', $otherCategoryTemplateId);
                $listingProduct->getChildObject()->setData('template_category_id', $categoryTemplateId);
                $listingProduct->getChildObject()->setData('template_other_category_id', $otherCategoryTemplateId);

                $this->transaction->addObject($listingProduct);
            }

            $this->transaction->save();
        } catch (\Exception $e) {
            $snapshots = false;
            $this->transaction->rollback();
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