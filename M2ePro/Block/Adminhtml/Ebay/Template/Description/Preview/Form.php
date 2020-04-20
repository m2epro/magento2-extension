<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Preview;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Magento\Framework\Message\MessageInterface;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Preview\Form
 */
class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'show',
            'hidden',
            [
                'name' => 'show',
                'value' => 1
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_description_form',
            [
                'legend' => $this->__('Select Product'),
            ]
        );

        if ($errorMessage = $this->getData('error_message')) {
            $fieldset->addField(
                'messages',
                self::MESSAGES,
                [
                    'messages' => [
                        [
                            'type' => MessageInterface::TYPE_ERROR,
                            'content' => $errorMessage
                        ],
                    ]
                ]
            );
        }

        $viewButton = $this->createBlock('Magento\Button')->addData([
            'label' => $this->__('View'),
            'type' => 'submit'
        ]);

        $randomButton = $this->createBlock('Magento\Button')->addData([
            'label' => $this->__('View Random Product'),
            'type' => 'submit',
            'onclick' => '$(\'product_id\').value = \'\'; return true;'
        ]);

        $fieldset->addField(
            'product_id',
            'text',
            [
                'name' => 'id',
                'value' => $this->getData('product_id'),
                'label' => $this->__('Enter Product Id'),
                'after_element_html' => $viewButton->toHtml() . $this->__('or') . $randomButton->toHtml()
            ]
        );

        $fieldset->addField(
            'store_id',
            self::STORE_SWITCHER,
            [
                'value' => $this->getData('store_id'),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
