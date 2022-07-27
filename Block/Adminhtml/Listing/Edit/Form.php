<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Form extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $listing = $this->globalDataHelper->getValue('edit_listing');

        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => 'javascript:void(0)',
                'method' => 'post'
            ]]
        );

        $form->addField(
            'id',
            'hidden',
            [
                'name' => 'id'
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_listing_fieldset',
            []
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'class' => 'validate-no-empty M2ePro-listing-title',
                'label' => $this->__('Title'),
                'field_extra_attributes' => 'style="margin-bottom: 0;"'
            ]
        );

        if ($listing) {
            $form->addValues($listing->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
