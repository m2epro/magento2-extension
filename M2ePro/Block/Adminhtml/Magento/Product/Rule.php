<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Product;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule
 */
class Rule extends AbstractForm
{
    protected $conditions;
    protected $rendererFieldset;

    //########################################

    public function __construct(
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->conditions = $conditions;
        $this->rendererFieldset = $rendererFieldset;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Magento\Product\Rule $model */
        $model = $this->getData('rule_model');
        $storeId = $model->getStoreId();
        $prefix = $model->getPrefix();

        $form = $this->_formFactory->create();
        $form->setHtmlId($prefix);

        $renderer = $this->rendererFieldset
            ->setTemplate('Ess_M2ePro::magento/product/rule.phtml')
            ->setNewChildUrl(
                $this->getUrl(
                    '*/general/magentoRuleGetNewConditionHtml',
                    [
                        'prefix' => $prefix,
                        'store' => $storeId,
                    ]
                )
            );

        $fieldset = $form->addFieldset($prefix, [])->setRenderer($renderer);

        $fieldset->addField($prefix . '_field', 'text', [
            'name' => 'conditions' . $prefix,
            'label' => $this->__('Conditions'),
            'title' => $this->__('Conditions'),
            'required' => true,
        ])->setRule($model)->setRenderer($this->conditions);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
