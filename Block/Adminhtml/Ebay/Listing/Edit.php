<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    private $isEdit = false;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateEdit');
        $this->_controller = 'adminhtml_ebay_listing';
        $this->_mode = 'edit';
        // ---------------------------------------

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('ebay_listing');
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        if ($listing) {
            // ---------------------------------------
            $url = $this->getUrl('*/ebay_listing/view', ['id' => $listing->getId()]);

            if ($this->getRequest()->getParam('back')) {
                $url = $this->getHelper('Data')->getBackUrl();
            }

            $this->addButton('back', [
                'label'     => $this->__('Back'),
                'onclick'   => 'CommonObj.backClick(\'' . $url . '\')',
                'class'     => 'back'
            ]);
            // ---------------------------------------

            // ---------------------------------------
            $backUrl = $this->getHelper('Data')->makeBackUrlParam(
                '*/ebay_listing/view',
                ['id' => $listing->getId()]
            );
            $url = $this->getUrl(
                '*/ebay_template/saveListing',
                [
                    'id' => $listing->getId(),
                    'back' => $backUrl
                ]
            );
            $callback = 'function(params) { CommonObj.postForm(\''.$url.'\', params); }';
            $saveButtonsProps = ['save' => [
                'label'     => $this->__('Save And Back'),
                'onclick'   => 'EbayListingTemplateSwitcherObj.saveSwitchers(' . $callback . ')',
                'class'     => 'save primary'
            ]];
            // ---------------------------------------

            // ---------------------------------------
            $backUrl = $this->getHelper('Data')->makeBackUrlParam('*/ebay_template/editListing');
            $url = $this->getUrl(
                '*/ebay_template/saveListing',
                [
                    'id' => $listing->getId(),
                    'back' => $backUrl
                ]
            );

            $callback = 'function(params) { CommonObj.postForm(\''.$url.'\', params); }';
            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'button_class' => '',
                'onclick'   => 'EbayListingTemplateSwitcherObj.saveSwitchers(' . $callback . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => $saveButtonsProps
            ];

            $this->addButton('save_buttons', $saveButtons);

            // ---------------------------------------
        }

        if (!$listing) {
            // ---------------------------------------
            $currentStep = (int)$this->getRequest()->getParam('step', 2);
            $prevStep = $currentStep - 1;
            // ---------------------------------------

            if ($prevStep >= 1 && $prevStep <= 4) {
                // ---------------------------------------
                $url = $this->getUrl(
                    '*/ebay_listing_create/index',
                    ['_current' => true, 'step' => $prevStep]
                );
                $this->addButton('back', [
                    'label'     => $this->__('Previous Step'),
                    'onclick'   => 'CommonObj.backClick(\'' . $url . '\')',
                    'class'     => 'back primary'
                ]);
                // ---------------------------------------
            }

            $nextStepBtnText = 'Next Step';

            $sessionKey = 'ebay_listing_create';
            $sessionData = $this->getHelper('Data\Session')->getValue($sessionKey);
            if ($currentStep == 4 && isset($sessionData['creation_mode']) && $sessionData['creation_mode'] ===
                \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY) {
                $nextStepBtnText = 'Complete';
            }
            // ---------------------------------------
            $url = $this->getUrl(
                '*/ebay_listing_create/index',
                ['_current' => true, 'step' => $currentStep]
            );
            $callback = 'function(params) { CommonObj.postForm(\''.$url.'\', params); }';
            $this->addButton('save', [
                'label'     => $this->__($nextStepBtnText),
                'onclick'   => 'EbayListingTemplateSwitcherObj.saveSwitchers(' . $callback . ')',
                'class'     => 'action-primary forward'
            ]);
            // ---------------------------------------
        }

        $this->css->addFile('ebay/template.css');
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $data = [
            'allowed_tabs' => $this->getAllowedTabs()
        ];
        $tabs = $this->createBlock('Ebay_Listing_Edit_Tabs');
        $tabs->addData($data);
        $this->setChild('tabs', $tabs);
        // ---------------------------------------

        return $this;
    }

    //########################################

    public function getAllowedTabs()
    {
        if (!isset($this->_data['allowed_tabs']) || !is_array($this->_data['allowed_tabs'])) {
            return [];
        }

        return $this->_data['allowed_tabs'];
    }

    //########################################

    public function getFormHtml()
    {
        $html = '';
        $tabs = $this->getChildBlock('tabs');

        // ---------------------------------------
        $html .= $this->createBlock('Ebay_Listing_Template_Switcher_Initialization')->toHtml();
        // ---------------------------------------

        // ---------------------------------------
        $listing = $this->getHelper('Data\GlobalData')->getValue('ebay_listing');
        $headerHtml = '';
        if ($listing) {
            $headerBlock =  $this->createBlock('Listing_View_Header', '', [
                'data' => ['listing' => $listing]
            ]);
            $headerBlock->setListingViewMode(true);
            $headerHtml = $headerBlock->toHtml();
        }
        // ---------------------------------------

        // hide tabs selector if only one tab is allowed for displaying
        // ---------------------------------------
        if (count($this->getAllowedTabs()) == 1) {
            $this->js->add(<<<JS
    require([], function(){
        $('{$tabs->getId()}').hide();
    });
JS
            );
        }
        // ---------------------------------------

        return $html . $headerHtml . $tabs->toHtml() . parent::getFormHtml();
    }

    //########################################
}
