<?php

namespace Ess\M2ePro\Block\Adminhtml;

class General extends Magento\AbstractBlock
{
    //########################################

    protected $_template = 'general.phtml';

    protected $cacheConfig;

    protected function _prepareLayout()
    {
        if ($this->getIsAjax()) {
            return parent::_prepareLayout();
        }

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('General'));

        $this->css->addFile('plugin/AreaWrapper.css');
        $this->css->addFile('plugin/ProgressBar.css');
        $this->css->addFile('help_block.css');
        $this->css->addFile('style.css');
        $this->css->addFile('grid.css');

        $currentView = $this->getHelper('View')->getCurrentView();

        if (!empty($currentView)) {
            $this->css->addFile($currentView.'/style.css');
        }

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        if ($this->getIsAjax()) {
            return parent::_beforeToHtml();
        }

        $this->jsUrl->addUrls([
            'm2epro_skin_url' => $this->getViewFileUrl('Ess_M2ePro'),
            'general/getCreateAttributeHtmlPopup' => $this->getUrl('*/general/getCreateAttributeHtmlPopup')
        ]);

        $this->block_notices_show = $this->getHelper('Module')
            ->getConfig()
            ->getGroupValue('/view/', 'show_block_notices');
        $this->block_notices_disable_collapse = $this->getHelper('Data')->jsonEncode(
            (bool)$this->getHelper('Module')->getConfig()->getGroupValue('/view/ebay/notice/', 'disable_collapse')
        );

        $this->jsTranslator->addTranslations([
            'Are you sure?' => $this->__('Are you sure?'),
            'Confirmation'  => $this->__('Confirmation'),
            'Help'          => $this->__('Help'),
            'Hide Block'    => $this->__('Hide Block'),
            'Show Tips'     => $this->__('Show Tips'),
            'Hide Tips'     => $this->__('Hide Tips'),
            'Back'          => $this->__('Back'),
            'Notice'        => $this->__('Notice'),
            'Warning'       => $this->__('Warning'),
            'Error'         => $this->__('Error'),
            'Close'         => $this->__('Close'),
            'Success'       => $this->__('Success'),
            'None'          => $this->__('None'),
            'Add'           => $this->__('Add'),
            'Save'          => $this->__('Save'),
            'Send'          => $this->__('Send'),
            'Cancel'        => $this->__('Cancel'),
            'Reset'         => $this->__('Reset'),
            'Confirm'       => $this->__('Confirm'),
            'In Progress'   => $this->__('In Progress'),
            'Product(s)'    => $this->__('Product(s)'),
            'Continue'      => $this->__('Continue'),
            'Complete'      => $this->__('Complete'),
            'Yes'           => $this->__('Yes'),
            'No'            => $this->__('No'),

            'Collapse' => $this->__('Collapse'),
            'Expand'   => $this->__('Expand'),

            'Reset Auto Rules' => $this->__('Reset Auto Rules'),

            'Please select the Products you want to perform the Action on.' => $this->__(
                'Please select the Products you want to perform the Action on.'
            ),
            'Please select Items.'  => $this->__('Please select Items.'),
            'Please select Action.' => $this->__('Please select Action.'),
            'View Full Product Log' => $this->__('View Full Product Log'),
            'This is a required field.' => $this->__('This is a required field.'),
            'Please enter valid UPC' => $this->__('Please enter valid UPC'),
            'Please enter valid EAN' => $this->__('Please enter valid EAN'),
            'Please enter valid ISBN' => $this->__('Please enter valid ISBN'),
            'Invalid input data. Decimal value required. Example 12.05' => $this->__(
                'Invalid input data. Decimal value required. Example 12.05'
            ),
            'Email is not valid.' => $this->__('Email is not valid.'),

            'You should select Attribute Set first.' => $this->__('You should select Attribute Set first.'),

            'Create a New One...' => $this->__('Create a New One...'),
            'Creation of New Magento Attribute' => $this->__('Creation of New Magento Attribute'),

            'You should select Store View' => $this->__('You should select Store View'),

            'Insert Magento Attribute in %s%' => $this->__('Insert Magento Attribute in %s%'),
            'Attribute' => $this->__('Attribute'),
            'Insert' => $this->__('Insert'),
        ]);

        return parent::_beforeToHtml();
    }

    //########################################
}