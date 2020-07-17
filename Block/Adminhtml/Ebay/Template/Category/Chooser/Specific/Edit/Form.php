<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Edit;

use \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer\Dictionary as RendererDictionary;
use \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Element\Dictionary as ElementDictionary;
use \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Element\Custom as ElementCustom;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Edit\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayTemplateCategoryChooserSpecificEditForm');
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id'      => 'edit_specifics_form',
                'action'  => '',
                'method'  => 'post',
                'enctype' => 'multipart/form-data'
            ]
        ]);

        $formData = $this->getData('form_data');

        if (!empty($formData['dictionary_specifics'])) {
            $fieldset = $form->addFieldset(
                'dictionary',
                [
                    'legend'      => $this->__('eBay Specifics'),
                    'collapsable' => false
                ]
            );

            /** @var RendererDictionary $renderer */
            $renderer = $this->createBlock('Ebay_Template_Category_Chooser_Specific_Form_Renderer_Dictionary');
            $fieldset->addField(
                'dictionary_specifics',
                ElementDictionary::class,
                [
                    'specifics' => $formData['dictionary_specifics'],
                ]
            )->setRenderer($renderer);
        }

        $fieldset = $form->addFieldset(
            'custom',
            [
                'legend'      => $this->__('Additional Specifics'),
                'collapsable' => false
            ]
        );

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer\Custom $renderer */
        $renderer = $this->createBlock('Ebay_Template_Category_Chooser_Specific_Form_Renderer_Custom');
        $fieldset->addField(
            'custom_specifics',
            ElementCustom::class,
            [
                'specifics' => $formData['template_custom_specifics'],
            ]
        )->setRenderer($renderer);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
