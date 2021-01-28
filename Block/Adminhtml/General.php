<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\General
 */
class General extends Magento\AbstractBlock
{
    protected $_template = 'general.phtml';

    //########################################

    protected function _prepareLayout()
    {
        if ($this->getIsAjax()) {
            return parent::_prepareLayout();
        }

        $actions = $this->getHelper('Data')->getControllerActions(
            'General',
            [],
            !$this->helperFactory->getObject('Module')->areImportantTablesExist()
        );

        $this->jsUrl->addUrls($actions);

        $this->css->addFile('plugin/AreaWrapper.css');
        $this->css->addFile('plugin/ProgressBar.css');
        $this->css->addFile('help_block.css');
        $this->css->addFile('style.css');
        $this->css->addFile('grid.css');

        $currentView = $this->getHelper('View')->getCurrentView();
        if (!empty($currentView)) {
            $this->css->addFile($currentView . '/style.css');
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

        /**
         * m2epro_config table may be missing if migration is going on
         */
        $this->block_notices_show = $this->helperFactory->getObject('Module')->areImportantTablesExist()
            ? $this->getHelper('Module_Configuration')->getViewShowBlockNoticesMode()
            : 0;

        $synchWarningMessage = 'Marketplace synchronization was completed with warnings. '
            . '<a target="_blank" href="%url%">View Log</a> for the details.';
        $synchErrorMessage = 'Marketplace synchronization was completed with errors. '
            . '<a target="_blank" href="%url%">View Log</a> for the details.';

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
            'Submit'        => $this->__('Submit'),
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

            'Settings have been saved.' => $this->__('Settings have been saved.'),
            'You must select at least one Site you will work with.' =>
                $this->__('You must select at least one Site you will work with.'),

            'Preparing to start. Please wait ...' => $this->__('Preparing to start. Please wait ...'),

            'Marketplace synchronization was completed.' =>
                $this->__('Marketplace synchronization was completed.'),
            $synchWarningMessage => $this->__($synchWarningMessage),
            $synchErrorMessage => $this->__($synchErrorMessage),
            'Unauthorized! Please login again' => $this->__('Unauthorized! Please login again'),

            'Reset Unmanaged Listings' => $this->__('Reset Unmanaged Listings')
        ]);

        return parent::_beforeToHtml();
    }

    //########################################
}
