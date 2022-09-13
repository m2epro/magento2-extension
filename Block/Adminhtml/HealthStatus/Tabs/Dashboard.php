<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs;

class Dashboard extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var string */
    private $currentVersion;
    /** @var ?string */
    private $latestPublicVersion;
    /** @var bool */
    private $cronIsNotWorking = false;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $moduleSupportHelper;
    /** @var \Ess\M2ePro\Helper\Module\Cron */
    private $cronHelper;
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Set */
    private $resultSet;

    /**
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Module\Support $moduleSupportHelper
     * @param \Ess\M2ePro\Helper\Module\Cron $cronHelper
     * @param \Ess\M2ePro\Model\HealthStatus\Task\Result\Set $resultSet
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Support $moduleSupportHelper,
        \Ess\M2ePro\Helper\Module\Cron $cronHelper,
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Set $resultSet,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->resultSet = $resultSet;
        $this->moduleHelper = $moduleHelper;
        $this->moduleSupportHelper = $moduleSupportHelper;
        $this->cronHelper = $cronHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $this->prepareInfo();
        $form = $this->_formFactory->create();

        // -- Dynamic FieldSets for Info
        // ---------------------------------------
        $createdFieldSets = [];
        foreach ($this->resultSet->getByKeys() as $resultItem) {
            if (in_array($resultItem->getFieldSetName(), $createdFieldSets)) {
                continue;
            }

            $fieldSet = $form->addFieldset(
                'fieldset_' . strtolower($resultItem->getFieldSetName()),
                [
                    'legend'      => $this->__($resultItem->getFieldSetName()),
                    'collapsable' => false
                ]
            );

            foreach ($this->resultSet->getByFieldSet($this->resultSet->getFieldSetKey($resultItem)) as $byFieldSet) {
                $fieldSet->addField(
                    strtolower($byFieldSet->getTaskHash()),
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

        $fieldSet = $form->addFieldset(
            'version_info',
            [
                'legend'      => $this->__('Version Info'),
                'collapsable' => true
            ]
        );

        $fieldSet->addField(
            'current_version',
            'note',
            [
                'label' => $this->__('Current Version'),
                'text'  => $this->currentVersion
            ]
        );

        if ($this->latestPublicVersion) {
            $documentationArticleUrl = $this->moduleSupportHelper->getDocumentationArticleUrl(
                'x/BwAMB'
            );
            $fieldSet->addField(
                'latest_public_version',
                'note',
                [
                    'label' => $this->__('Latest Public Version'),
                    'text'  => <<<HTML
{$this->latestPublicVersion}
<a href="$documentationArticleUrl" target="_blank">{$this->__('[release notes]')}</a>
HTML
                ]
            );
        }

        // ---------------------------------------

        $fieldSet = $form->addFieldset(
            'cron_info',
            [
                'legend'      => $this->__('Cron Info'),
                'collapsable' => true
            ]
        );

        $fieldSet->addField(
            'current_status_type',
            'note',
            [
                'label' => $this->__('Type'),
                'text'  => ucwords(str_replace('_', ' ', $this->cronHelper->getRunner()))
            ]
        );

        $cronLastRunTime = $this->cronHelper->getLastRun();
        if ($cronLastRunTime !== null) {
            $this->cronIsNotWorking = $this->cronHelper->isLastRunMoreThan(12, true);
        } else {
            $cronLastRunTime = 'N/A';
        }

        $fieldSet->addField(
            'current_status_last_run',
            'note',
            [
                'label' => $this->__('Last Run'),
                'text' => "<span>{$cronLastRunTime}</span>" .
                    ($this->cronIsNotWorking ? ' (' . $this->__('not working') . ')' : ''),
                'style' => $this->cronIsNotWorking ? 'color: red' : ''
            ]
        );

        // ---------------------------------------

        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function prepareInfo()
    {
        $this->currentVersion = $this->moduleHelper->getPublicVersion();
        $this->latestPublicVersion = $this->moduleHelper->getRegistry()->getValue(
            '/installation/public_last_version/'
        );
    }

    protected function _toHtml()
    {
        $requirements = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Requirements::class);

        return $requirements->toHtml() . parent::_toHtml();
    }
}
