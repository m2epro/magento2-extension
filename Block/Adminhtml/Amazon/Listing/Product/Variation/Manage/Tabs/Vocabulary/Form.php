<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Vocabulary;

use Ess\M2ePro\Helper\Component\Amazon\Vocabulary;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->vocabularyHelper = $vocabularyHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'variation_Vocabulary_form',
                    'method' => 'post',
                    'action' => 'javascript:void(0)',
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'legend' => $this->__('Saving of manual Variational Attributes/Options matches'),
                'collapsable' => true
            ]
        );

        $fieldset->addField(
            'attribute_auto_action',
            'select',
            [
                'name' => 'attribute_auto_action',
                'label' => $this->__('Save selected matching of Attributes?'),
                'values' => [
                    Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET => $this->__('Ask Me'),
                    Vocabulary::VOCABULARY_AUTO_ACTION_NO => $this->__('Don\'t save'),
                    Vocabulary::VOCABULARY_AUTO_ACTION_YES => $this->__('Save')
                ],
                'value' => $this->vocabularyHelper->isAttributeAutoActionNotSet() ?
                    Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET :
                    ($this->vocabularyHelper->isAttributeAutoActionEnabled() ?
                        Vocabulary::VOCABULARY_AUTO_ACTION_YES : Vocabulary::VOCABULARY_AUTO_ACTION_NO)
            ]
        );

        $fieldset->addField(
            'option_auto_action',
            'select',
            [
                'name' => 'option_auto_action',
                'label' => $this->__('Save selected matching of Options?'),
                'values' => [
                    Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET => $this->__('Ask Me'),
                    Vocabulary::VOCABULARY_AUTO_ACTION_NO => $this->__('Don\'t save'),
                    Vocabulary::VOCABULARY_AUTO_ACTION_YES => $this->__('Save')
                ],
                'value' => $this->vocabularyHelper->isOptionAutoActionNotSet() ?
                    Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET :
                    ($this->vocabularyHelper->isOptionAutoActionEnabled() ?
                        Vocabulary::VOCABULARY_AUTO_ACTION_YES : Vocabulary::VOCABULARY_AUTO_ACTION_NO)
            ]
        );

        $fieldset->addField(
            'save_button',
            'button',
            [
                'label' => '',
                'value' => $this->__('Save'),
                'onclick' => 'ListingGridObj.variationProductManageHandler.saveAutoActionSettings()',
                'class' => 'action-primary'
            ]
        );

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
