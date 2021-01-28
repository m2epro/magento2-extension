<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

use Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template
 */
class Template extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplate');
        $this->_controller = 'adminhtml_amazon_template';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $this->buttonList->update('add', 'label', $this->__('Add Policy'));
        $this->buttonList->update('add', 'onclick', '');
    }

    //########################################

    protected function _prepareLayout()
    {
        $content = $this->__(
            '
            In this Section you can Create, Edit and Delete the Selling Policy,
            Synchronization Policy, Description Policy, Shipping Policy,
            Product Tax Code Policy.<br/><br/>

            <strong>Selling Policy</strong> is used to work with values related
            to the formation of your Channel Offers such as Price, Quantity, etc.<br/><br/>

            In the <strong>Synchronization Policy</strong>, you can set the Rules under which the dynamic data
            exchange between Channel and Magento will be performed.<br/><br/>

            <strong>Description Policy</strong> is used to provide necessary settings for Creating new ASIN/ISBN in
            Amazon Catalog or Update the Product Information of the existing Amazon Item.<br/><br/>

            <strong>Shipping Policy</strong> is used to apply the Amazon Shipping to your
            Products within M2E Pro Listings.<br/><br/>

            <strong>Product Tax Code Policy</strong> allows applying the Amazon Tax Codes to your
            Products within M2E Pro Listings.<br/><br/>

            More detailed information about Policy configuration can be found
            <a href="%url%" target="_blank" class="external-link">here</a>.',
            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/8gEtAQ')
        );

        $this->appendHelpBlock(
            [
                'content' => $content
            ]
        );

        $addButtonProps = [
            'id'           => 'add_policy',
            'label'        => __('Add Policy'),
            'class'        => 'add',
            'button_class' => '',
            'class_name'   => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options'      => $this->_getAddTemplateButtonOptions(),
        ];
        $this->addButton('add', $addButtonProps);

        return parent::_prepareLayout();
    }

    //########################################

    protected function _getAddTemplateButtonOptions()
    {
        $data = [
            Grid::TEMPLATE_SELLING_FORMAT   => [
                'label'   => $this->__('Selling'),
                'id'      => 'selling',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_SELLING_FORMAT) . "')",
            ],
            Grid::TEMPLATE_DESCRIPTION      => [
                'label'   => $this->__('Description'),
                'id'      => 'description',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_DESCRIPTION) . "')",
            ],
            Grid::TEMPLATE_SYNCHRONIZATION  => [
                'label'   => $this->__('Synchronization'),
                'id'      => 'synchronization',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_SYNCHRONIZATION) . "')",
            ],
            Grid::TEMPLATE_SHIPPING         => [
                'label'   => $this->__('Shipping'),
                'id'      => 'shipping',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_SHIPPING) . "')",
            ],
            Grid::TEMPLATE_PRODUCT_TAX_CODE => [
                'label'   => $this->__('Product Tax Code'),
                'id'      => 'product_tax_code',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_PRODUCT_TAX_CODE) . "')",
            ]
        ];

        return $data;
    }

    protected function getTemplateUrl($type)
    {
        return $this->getUrl('*/amazon_template/new', ['type' => $type]);
    }

    //########################################
}
