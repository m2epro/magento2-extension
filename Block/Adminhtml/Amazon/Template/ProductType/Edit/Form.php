<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonTemplateProductTypeEditForm');
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Form
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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
                'value' => $productType->getViewMode(),
                'name' => 'general[view_mode]'
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
