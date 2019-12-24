<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Settings\SkuPopup;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Settings\SkuPopup\Form
 */
class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'variation_manager_sku_form',
                'action' => 'javascript:void(0)',
                'method' => 'post'
            ]]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            []
        );

        $fieldset->addField(
            'sku',
            'text',
            [
                'name' => 'sku',
                'label' => $this->__('SKU'),
                'required' => true
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__('
            In order to prove that this is your Product, you need to provide SKU of the respective Product
            in your Amazon Inventory. Please follow the Rules below to avoid issues:
            <ul class="list">
                <li>The SKU has to be related to Parent Product in your Amazon Inventory;</li>
                <li>ASIN(s)/ISBN(s) in M2E Pro and in Amazon Inventory have to be the same;</li>
                <li>The Product in the Amazon Inventory has to be visible via Amazon API.</li>
            </ul>')
        ]);

        return '<div id="manage_variation_sku_popup">' .
            $helpBlock->toHtml() .
            parent::_toHtml() .
            '</div>';
    }
}
