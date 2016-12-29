<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class SystemRequirements extends AbstractBlock
{
    protected $formFactory;

    protected $_template = 'developers/tabs/system_requirements.phtml';

    public $requirements = [];
    public $additionalInfo = NULL;

    //########################################

    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    )
    {
        $this->formFactory = $formFactory;
        parent::__construct($context, $data);
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->requirements = $this->getHelper('Module')->getRequirementsInfo();

        $form = $this->formFactory->create();

        $fieldSet = $form->addFieldset('field_system',
            [
                'legend' => $this->__('System'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField('system_name',
            'note',
            [
                'label' => $this->__('Name'),
                'text' => $this->getHelper('Client')->getSystem()
            ]
        );

        $fieldSet->addField('system_current_date',
            'note',
            [
                'label' => $this->__('Current Date'),
                'text' => $this->getHelper('Data')->getCurrentGmtDate()
            ]
        );

        $fieldSet = $form->addFieldset('field_php',
            [
                'legend' => $this->__('PHP'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField('php_version',
            'note',
            [
                'label' => $this->__('Version'),
                'text' => $this->getHelper('Client')->getPhpVersion()
            ]
        );

        $fieldSet->addField('php_server_api',
            'note',
            [
                'label' => $this->__('Server API'),
                'text' => $this->getHelper('Client')->getPhpApiName()
            ]
        );

        $phpSettings = $this->getHelper('Client')->getPhpSettings();

        $fieldSet->addField('php_memory_limit',
            'note',
            [
                'label' => $this->__('Memory Limit'),
                'text' => $phpSettings['memory_limit'] == -1
                            ? $this->__('Unlimited')
                            : $phpSettings['memory_limit'] . ' Mb'
            ]
        );

        $fieldSet->addField('php_max_execution_time',
            'note',
            [
                'label' => $this->__('Max Execution Time'),
                'text' => $phpSettings['max_execution_time'] == 0
                            ? $this->__('Unlimited')
                            : $phpSettings['max_execution_time'] . ' sec'
            ]
        );

        $fieldSet = $form->addFieldset('field_mysql',
            [
                'legend' => $this->__('MySQL'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField('mysql_version',
            'note',
            [
                'label' => $this->__('Version'),
                'text' => $this->getHelper('Client')->getMysqlVersion()
            ]
        );

        $fieldSet->addField('mysql_database_name',
            'note',
            [
                'label' => $this->__('Database Name'),
                'text' => $this->getHelper('Magento')->getDatabaseName()
            ]
        );

        $tablesPrefix = $this->getHelper('Magento')->getDatabaseTablesPrefix();
        $fieldSet->addField('mysql_tables_prefix',
            'note',
            [
                'label' => $this->__('Tables Prefix'),
                'text' => !empty($tablesPrefix) ? '<span style="color: red;">'.$tablesPrefix.'</span>'
                                                : $this->__('disabled')
            ]
        );

        $mySqlSettings = $this->getHelper('Client')->getMysqlSettings();

        $fieldSet->addField('mysql_timeout',
            'note',
            [
                'label' => $this->__('Connection Timeout'),
                'text' => $mySqlSettings['connect_timeout'] . $this->__('sec')
            ]
        );

        $fieldSet->addField('mysql_wait_timeout',
            'note',
            [
                'label' => $this->__('Wait Timeout'),
                'text' => $mySqlSettings['wait_timeout'] . $this->__('sec')
            ]
        );

        $this->additionalInfo = $form;
        return parent::_beforeToHtml();
    }

    //########################################
}