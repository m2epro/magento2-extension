<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingEdit');
        $this->_controller = 'adminhtml_amazon_listing';
        $this->_mode = 'edit';
        // ---------------------------------------

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('edit_listing');

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        if (!is_null($this->getRequest()->getParam('back'))) {
            // ---------------------------------------
            $url = $this->getHelper('Data')->getBackUrl(
                '*/amazon_listing/index'
            );
            $this->addButton('back', array(
                'label'     => $this->__('Back'),
                'onclick'   => 'AmazonListingSettingsObj.backClick(\''.$url.'\')',
                'class'     => 'back'
            ));
            // ---------------------------------------
        }

        // ---------------------------------------
        $this->addButton('auto_action', array(
            'label'     => $this->__('Auto Add/Remove Rules'),
            'onclick'   => 'ListingAutoActionObj.loadAutoActionHtml();',
            'class'     => 'action-primary'
        ));
        // ---------------------------------------

        $backUrl = $this->getHelper('Data')->getBackUrlParam('list');

        // ---------------------------------------
        $url = $this->getUrl(
            '*/amazon_listing/save',
            array(
                'id'    => $this->listing['id'],
                'back'  => $backUrl
            )
        );
        $saveButtonsProps = ['save' => [
            'label'     => $this->__('Save And Back'),
            'onclick'   => 'AmazonListingSettingsObj.saveClick(\'' . $url . '\')',
            'class'     => 'save primary'
        ]];
        // ---------------------------------------

        // ---------------------------------------
        $saveButtons = [
            'id' => 'save_and_continue',
            'label' => $this->__('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'onclick'   => 'AmazonListingSettingsObj.saveAndEditClick(\''.$url.'\', 1)',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
            'options' => $saveButtonsProps
        ];

        $this->addButton('save_buttons', $saveButtons);

        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');

        return parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $tabs = $this->createBlock('Amazon\Listing\Edit\Tabs');
        $this->setChild('tabs', $tabs);
        // ---------------------------------------

        return $this;
    }

    //########################################

    public function getFormHtml()
    {
        $viewHeaderBlock = $this->createBlock('Listing\View\Header','', [
            'data' => ['listing' => $this->listing]
        ]);

        $tabs = $this->getChildBlock('tabs');

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Amazon\Listing\AutoAction',
            ['id' => $this->getRequest()->getParam('id')]
        ));

        $path = 'amazon_listing_autoAction/getDescriptionTemplatesList';
        $this->jsUrl->add($this->getUrl('*/' . $path, [
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'is_new_asin_accepted' => 1
        ]), $path);

        $this->jsTranslator->addTranslations([
            'Remove Category' => $this->__('Remove Category'),
            'Add New Group' => $this->__('Add New Group'),
            'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
            'Auto Add/Remove Rules' => $this->__('Auto Add/Remove Rules'),
            'Based on Magento Categories' => $this->__('Based on Magento Categories'),
            'You must select at least 1 Category.' => $this->__('You must select at least 1 Category.'),
            'Rule with the same Title already exists.' => $this->__('Rule with the same Title already exists.')
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Listing')
        );

        $this->js->addOnReadyJs(
<<<JS
    require([
        'M2ePro/Amazon/Listing/AutoAction'
    ], function(){

        window.ListingAutoActionObj = new AmazonListingAutoAction();

    });
JS
    );

        return $viewHeaderBlock->toHtml() . $tabs->toHtml() . parent::getFormHtml();
    }

    //########################################
}