<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs;

use Magento\Framework\Message\MessageInterface;

class Dashboard extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
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

        $toDevelopersArea = $this->getUrl('*/developers/index', [
            'referrer' => $this->getRequest()->getParam('referrer')
        ]);

        $form->addField(
            'health_status_dashboard_developers_notice',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => MessageInterface::TYPE_NOTICE,
                        'content' => $this->__(
<<<HTML
The additional information which might be helpful for Developers and Administrators is available in the
<a target="_blank" href="{$toDevelopersArea}">Developers Area</a>.
HTML
                        )
                    ],
                ]
            ]
        );

        // -- Dynamic FieldSets for Info
        // ---------------------------------------
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
                    'note',
                    [
                        'label' => $this->__($byFieldSet->getFieldName()),
                        'text'  => $byFieldSet->getTaskMessage()
                    ]
                );
            }

            $createdFieldSets[] = $resultItem->getFieldSetName();
        }
        // ---------------------------------------

        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################
}