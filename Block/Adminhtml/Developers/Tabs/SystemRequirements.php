<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class SystemRequirements extends AbstractBlock
{
    /** @var \Magento\Framework\Data\FormFactory */
    protected $formFactory;

    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->formFactory = $formFactory;
        $this->clientHelper = $clientHelper;
        $this->dataHelper = $dataHelper;
        $this->magentoHelper = $magentoHelper;
    }

    public function toHtml()
    {
        $requirements = $this->getLayout()
                             ->createBlock(\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Requirements::class);
        return $requirements->toHtml() .
            $this->getAdditionalForm()->toHtml();
    }

    /**
     * @return \Magento\Framework\Data\Form
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getAdditionalForm()
    {
        $form = $this->formFactory->create();

        $fieldSet = $form->addFieldset(
            'field_system',
            [
                'legend'      => $this->__('System'),
                'collapsable' => false,
            ]
        );

        $fieldSet->addField(
            'system_name',
            'note',
            [
                'label' => $this->__('Name'),
                'text'  => $this->clientHelper->getSystem(),
            ]
        );

        $fieldSet->addField(
            'system_current_date',
            'note',
            [
                'label' => $this->__('Current Date'),
                'text'  => $this->dataHelper->getCurrentGmtDate(),
            ]
        );

        $fieldSet = $form->addFieldset(
            'field_php',
            [
                'legend'      => $this->__('PHP'),
                'collapsable' => false,
            ]
        );

        $fieldSet->addField(
            'php_version',
            'note',
            [
                'label' => $this->__('Version'),
                'text'  => $this->clientHelper->getPhpVersion(),
            ]
        );

        $fieldSet->addField(
            'php_server_api',
            'note',
            [
                'label' => $this->__('Server API'),
                'text'  => $this->clientHelper->getPhpApiName(),
            ]
        );

        $phpSettings = $this->clientHelper->getPhpSettings();

        $fieldSet->addField(
            'php_memory_limit',
            'note',
            [
                'label' => $this->__('Memory Limit'),
                'text'  => $phpSettings['memory_limit'] == -1
                    ? $this->__('Unlimited')
                    : $phpSettings['memory_limit'] . ' Mb',
            ]
        );

        $fieldSet->addField(
            'php_max_execution_time',
            'note',
            [
                'label' => $this->__('Max Execution Time'),
                'text'  => $phpSettings['max_execution_time'] == 0
                    ? $this->__('Unlimited')
                    : $phpSettings['max_execution_time'] . ' sec',
            ]
        );

        $fieldSet = $form->addFieldset(
            'field_mysql',
            [
                'legend'      => $this->__('MySQL'),
                'collapsable' => false,
            ]
        );

        $fieldSet->addField(
            'mysql_version',
            'note',
            [
                'label' => $this->__('Version'),
                'text'  => $this->clientHelper->getMysqlVersion(),
            ]
        );

        $fieldSet->addField(
            'mysql_database_name',
            'note',
            [
                'label' => $this->__('Database Name'),
                'text'  => $this->magentoHelper->getDatabaseName(),
            ]
        );

        $tablesPrefix = $this->magentoHelper->getDatabaseTablesPrefix();
        $fieldSet->addField(
            'mysql_tables_prefix',
            'note',
            [
                'label' => $this->__('Tables Prefix'),
                'text'  => !empty($tablesPrefix) ? '<span style="color: red;">' . $tablesPrefix . '</span>'
                    : $this->__('disabled'),
            ]
        );

        $mySqlSettings = $this->clientHelper->getMysqlSettings();

        $fieldSet->addField(
            'mysql_timeout',
            'note',
            [
                'label' => $this->__('Connection Timeout'),
                'text'  => $mySqlSettings['connect_timeout'] . $this->__('sec'),
            ]
        );

        $fieldSet->addField(
            'mysql_wait_timeout',
            'note',
            [
                'label' => $this->__('Wait Timeout'),
                'text'  => $mySqlSettings['wait_timeout'] . $this->__('sec'),
            ]
        );

        return $form;
    }
}
