<?php

/*
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

        $saveButtons = [
            'id' => 'save_and_continue',
            'label' => $this->__('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'data_attribute' => [
                'mage-init' => [
                    'button' => [
                        'event' => 'save',
                        'target' => '#edit_form',
                        'eventData' => ['action' => ['args' => [
                            'back' => 'edit',
                        ]]]
                    ]
                ],
            ],
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
            'options' => [
                'save' => [
                    'label' => $this->__('Save And Back'),
                    'data_attribute' => [
                        'mage-init' => ['button' => [
                            'event' => 'save',
                            'target' => '#edit_form'
                        ]],
                    ],
                ]
            ],
        ];

        $this->addButton('save_buttons', $saveButtons);

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Template_Category',
            $this->getRequest()->getParam('template_id')
        );

        $isExists = true;
        $template->isCategoryModeEbay() && $isExists = $this->componentEbayCategoryEbay->exists(
            $template->getCategoryId(),
            $template->getMarketplaceId()
        );

        if (!$isExists) {
            $this->removeButton('reset');
            $this->removeButton('save_and_continue');
        }
    }

    //########################################
}
