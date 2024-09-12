<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public function _construct(): void
    {
        parent::_construct();
        $this->setId('amazonTemplateProductTypeEditForm');
    }

    protected function _prepareForm(): Form
    {
        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $productType */
        $productType = $this->getData('data_object');

        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'method' => 'post',
                'action' => $this->getUrl('*/*/save'),
                'enctype' => 'multipart/form-data',
            ],
        ]);

        $form->addField(
            'view_mode',
            'hidden',
            [
                //'value' => $productType->getViewMode(),
                'value' => 0,
                'name' => 'general[view_mode]'
            ]
        );

        $form->addField(
            'is_new_product_type',
            'hidden',
            [
                'value' => !$productType->getId() ? '1' : '0',
                'name' => 'is_new_product_type'
            ]
        );

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Data $dataBlock */
        $dataBlock = $this->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Data::class,
                '',
                ['productType' => $productType]
            );

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\FieldTemplates $fieldTemplatesBlock */
        $fieldTemplatesBlock = $this->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\FieldTemplates::class);

        $form->addField(
            'content_html',
            self::CUSTOM_CONTAINER,
            [
                'text' => $dataBlock->toHtml() . $fieldTemplatesBlock->toHtml()
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
