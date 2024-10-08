<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public function _construct()
    {
        parent::_construct();
        $this->setId('walmartProductTypeEditForm');
    }

    protected function _prepareForm(): Form
    {
        /** @var \Ess\M2ePro\Model\Walmart\ProductType $productType */
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
            'is_new_product_type',
            'hidden',
            [
                'value' => $productType->isObjectNew() ? '1' : '0',
                'name' => 'is_new_product_type'
            ]
        );

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Data $dataBlock */
        $dataBlock = $this->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Data::class,
                '',
                ['productType' => $productType]
            );

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\FieldTemplates $fieldTemplatesBlock */
        $fieldTemplatesBlock = $this->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\FieldTemplates::class);

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
