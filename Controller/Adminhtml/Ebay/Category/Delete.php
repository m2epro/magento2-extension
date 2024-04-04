<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class Delete extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $ruleViewStateManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory $ebayProductRuleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct(
            $ruleViewStateManager,
            $viewStateFactory,
            $ebayProductRuleFactory,
            $globalDataHelper,
            $sessionHelper,
            $ebayFactory,
            $context
        );

        $this->componentEbayCategory = $componentEbayCategory;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->getMessageManager()->addError($this->__('Please select Item(s) to remove.'));
            $this->_redirect('*/*/index');

            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        $locked = 0;
        $deleted = 0;
        $deletedTemplate = [];

        foreach ($collection->getItems() as $template) {
            if ($template->isLocked()) {
                $locked++;
                continue;
            }

            $template->delete();

            $deletedTemplate[] = $template->getId();

            $deleted++;
        }

        if ($deletedTemplate) {
            $this->unsetCategoryData($deletedTemplate);
        }

        $tempString = $this->__('%s% record(s) were deleted.', $deleted);
        $deleted && $this->getMessageManager()->addSuccess($tempString);

        $tempString = $this->__(
            '[%count%] Category cannot be removed until it’s unassigned from the existing products.
            Read the <a href="%url%" target="_blank">article</a> for more information.',
            $locked,
            $this->getHelper('Module\Support')->getDocumentationArticleUrl(
                'display/eBayMagentoV6X/Set+eBay+Categories'
            )
        );
        $locked && $this->getMessageManager()->addError($tempString);

        $this->_redirect('*/*/index');
    }

    /**
     * @param array $ids
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function unsetCategoryData(array $ids): void
    {
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection
            ->addFieldToSelect(['id', 'additional_data'])
            ->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->addFieldToFilter('additional_data', ['like' => '%mode_same_category_data%']);

        foreach ($collection as $listing) {
            /** @var \Ess\M2ePro\Model\Listing $listing */

            $additionalData = $listing->getSettings('additional_data');

            if (empty($additionalData['mode_same_category_data'])) {
                continue;
            }

            $save = false;

            foreach ($additionalData['mode_same_category_data'] as $key => $templateData) {
                if (
                    in_array($templateData['template_id'], $ids, true)
                    && in_array($key, $this->componentEbayCategory->getEbayCategoryTypes(), true)
                ) {
                    unset($additionalData['mode_same_category_data'][$key]);

                    if (empty($additionalData['mode_same_category_data'])) {
                        unset($additionalData['mode_same_category_data']);
                    }

                    $save = true;
                }
            }

            if ($save) {
                $listing->setSettings('additional_data', $additionalData);
                $listing->save();
            }
        }
    }
}
