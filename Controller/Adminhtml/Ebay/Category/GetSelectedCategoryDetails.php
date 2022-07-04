<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class GetSelectedCategoryDetails extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Store */
    private $componentEbayCategoryStore;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    private $magentoAttributeHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->componentEbayCategoryStore = $componentEbayCategoryStore;
        $this->componentEbayCategory      = $componentEbayCategory;
        $this->componentEbayCategoryEbay  = $componentEbayCategoryEbay;
        $this->magentoAttributeHelper     = $magentoAttributeHelper;
    }

    public function execute()
    {
        $details = [
            'path'               => '',
            'interface_path'     => '',
            'template_id'        => null,
            'is_custom_template' => null
        ];

        $categoryHelper = $this->componentEbayCategory;

        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId     = $this->getRequest()->getParam('account_id');
        $value         = $this->getRequest()->getParam('value');
        $mode          = $this->getRequest()->getParam('mode');
        $categoryType  = $this->getRequest()->getParam('category_type');

        switch ($mode) {
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                $details['path'] = $categoryHelper->isEbayCategoryType($categoryType)
                    ? $this->componentEbayCategoryEbay->getPath($value, $marketplaceId)
                    : $this->componentEbayCategoryStore->getPath($value, $accountId);

                $details['interface_path'] = $details['path'] . ' (' . $value . ')';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                $details['path'] = $this->__('Magento Attribute') .' > '.
                    $this->magentoAttributeHelper->getAttributeLabel($value);

                $details['interface_path'] = $details['path'];

                break;
        }

        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
            $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            $template->loadByCategoryValue($value, $mode, $marketplaceId, 0);

            $details['is_custom_template'] = $template->getIsCustomTemplate();
            $details['template_id']        = $template->getId();
        }

        $this->setJsonContent($details);

        return $this->getResult();
    }

    //########################################
}
