<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs;

class IssueGroup extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    const NOTE_ELEMENT = 'Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs\Element\Note';

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Set */
    private $resultSet;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Set $resultSet,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->resultSet = $resultSet;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $createdFieldSets = [];
        foreach ($this->resultSet->getByKeys() as $resultItem) {

            if (in_array($resultItem->getFieldSetName(), $createdFieldSets)) {
                continue;
            }

            $fieldSet = $form->addFieldset('fieldset_' . strtolower($resultItem->getFieldSetName()),
                [
                    'legend'      => $this->__($resultItem->getFieldSetName()),
                    'collapsable' => false
                ]
            );

            foreach ($this->resultSet->getByFieldSet($this->resultSet->getFieldSetKey($resultItem)) as $byFieldSet) {

                $fieldSet->addField(strtolower($byFieldSet->getTaskHash()),
                    self::NOTE_ELEMENT,
                    [
                        'label'       => $this->__($byFieldSet->getFieldName()),
                        'text'        => $byFieldSet->getTaskMessage(),
                        'task_result' => $byFieldSet,
                    ]
                );
            }

            $createdFieldSets[] = $resultItem->getFieldSetName();
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################
}