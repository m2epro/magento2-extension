<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\ItemSpecific;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->removeButton('save');

        $this->setId('ebayConfigurationCategoryViewTabsItemSpecificsEdit');
        $this->_controller = 'adminhtml_ebay_category_view_tabs_itemSpecific';

        $this->_headerText = '';

        $this->updateButton(
            'reset',
            'onclick',
            'EbayTemplateCategorySpecificsObj.resetSpecifics()'
        );

        $editUrl = $this->_urlBuilder->getUrl(
            '*/ebay_category/saveTemplateCategorySpecifics',
            ['back' => 'edit']
        );

        $closeUrl = $this->_urlBuilder->getUrl(
            '*/ebay_category/saveTemplateCategorySpecifics'
        );

        $saveButtons = [
            'id' => 'save_and_continue',
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
            'label' => __('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'onclick' => "EbayTemplateCategorySpecificsObj.saveAndEditClick('$editUrl')",
            'options' => [
                'save' => [
                    'label' => __('Save And Back'),
                    'onclick' => "EbayTemplateCategorySpecificsObj.saveAndCloseClick('$closeUrl')",
                ],
            ],
        ];

        $this->addButton('save_buttons', $saveButtons);

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Template_Category',
            $this->getRequest()->getParam('template_id')
        );

        $isExists = true;
        if ($template->isCategoryModeEbay()) {
            $isExists = $this->componentEbayCategoryEbay->exists(
                $template->getCategoryId(),
                $template->getMarketplaceId()
            );
        }

        if (!$isExists) {
            $this->removeButton('reset');
            $this->removeButton('save_and_continue');
        }
    }

    //########################################
}
