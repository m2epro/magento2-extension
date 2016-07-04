<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

use Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid;

class Template extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplate');
        $this->_controller = 'adminhtml_amazon_template';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
        // ---------------------------------------

        // ---------------------------------------
        $this->buttonList->update('add', 'label', $this->__('Add Policy'));
        $this->buttonList->update('add', 'onclick', '');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $content = $this->__('
            In this Section you can Create, Edit and Delete Selling Format, Shipping Override, Description and
            Synchronization Policies.<br/><br/>
            <strong>Selling Format Policies</strong> are used to work with values related
            to the offer part of the Listings, such as
            Price, Quantity and similar parameters.<br/><br/>
            <strong>Shipping Override Policies</strong> are used to specify Settings for Shipping Services,
            Locale and Shipping Cost.<br/><br/>
            <strong>Description Policies</strong> are used to provide necessary Settings
            for Creating new ASIN/ISBN in Amazon Catalog
            or Update the Product Information of the existing Amazon Item.<br/><br/>
            In the <strong>Synchronization Policy</strong> you can set the Rules
            under which the dynamic data Synchronization between
            Channel and Magento will be performed.
        ');

        $this->appendHelpBlock([
            'content' => $content
        ]);

        $addButtonProps = [
            'id' => 'add_new_product',
            'label' => __('Add Policy'),
            'class' => 'add',
            'button_class' => '',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options' => $this->_getAddTemplateButtonOptions(),
        ];
        $this->addButton('add', $addButtonProps);

        return parent::_prepareLayout();
    }

    //########################################

    protected function _getAddTemplateButtonOptions()
    {
        $data = [
            Grid::TEMPLATE_SELLING_FORMAT => [
                'label' => $this->__('Selling Format'),
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_SELLING_FORMAT) . "')",
            ],
            Grid::TEMPLATE_DESCRIPTION => [
                'label' => $this->__('Description'),
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_DESCRIPTION) . "')",
            ],
            Grid::TEMPLATE_SYNCHRONIZATION => [
                'label' => $this->__('Synchronization'),
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_SYNCHRONIZATION) . "')",
            ],
        ];

        return $data;
    }

    protected function getTemplateUrl($type)
    {
        return $this->getUrl('*/amazon_template/new', ['type' => $type]);
    }

    //########################################
}