<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use \Ess\M2ePro\Helper\Module\Cron as CronHelper;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Developers\Tabs\CronJobDetails
 */
class CronJobDetails extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $cronIsNotWorking = false;

    //########################################

    protected function _prepareLayout()
    {
        $form = $this->_formFactory->create();
        $config = $this->getHelper('Module')->getConfig();

        $form->addField(
            'cron_job_details',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__(
                    <<<HTML
On this page, you can review which Cron Job type 
(<a target="_blank" href="%url1%">M2E Pro Cron Service</a> or <a target="_blank" href="%url2%">Magento Cron</a>)
 is used in your system to run automatic synchronization and its last run date.
HTML
                    ,
                    $this->getHelper('Module_Support')->getKnowledgebaseArticleUrl('1499822'),
                    $this->getHelper('Module_Support')->getKnowledgebaseArticleUrl('1577329')
                )
            ]
        );

        $fieldSet = $form->addFieldset(
            'field_current_status',
            [
                'legend' => $this->__('Current Status'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField(
            'current_status_type',
            'note',
            [
                'label' => $this->__('Type'),
                'text' => ucwords(str_replace('_', ' ', $this->getHelper('Module\Cron')->getRunner()))
            ]
        );

        if ($this->getHelper('Module\Cron')->isRunnerService() && !$this->getData('is_support_mode')) {
            $fieldSet->addField(
                'current_status_service_auth_key',
                'note',
                [
                    'label' => $this->__('Service Auth Key'),
                    'text' => $config->getGroupValue('/cron/service/', 'auth_key')
                ]
            );
        }

        $cronLastRunTime = $this->getHelper('Module\Cron')->getLastRun();
        if ($cronLastRunTime !== null) {
            $this->cronIsNotWorking = $this->getHelper('Module\Cron')->isLastRunMoreThan(12, true);
        } else {
            $cronLastRunTime = 'N/A';
        }

        $fieldSet->addField(
            'current_status_last_run',
            'note',
            [
                'label' => $this->__('Last Run'),
                'text' => "<span>{$cronLastRunTime}</span>" .
                           $this->cronIsNotWorking  ?: ' (' .$this->__('not working'). ')',
                'style' => !$this->cronIsNotWorking ? '' : 'color: red'
            ]
        );

        $isDisabled = (bool)(int)$config->getGroupValue(
            '/cron/'.CronHelper::RUNNER_SERVICE_CONTROLLER.'/',
            'disabled'
        );
        if (!$this->getData('is_support_mode') && $isDisabled) {
            $fieldSet->addField(
                'current_status_frontend_controller_cron_state',
                'note',
                [
                    'label' => $this->__('Service Controller Cron State'),
                    'text' => $this->__('Disabled by Developer'),
                    'style' => 'color: red'
                ]
            );
        }

        $isDisabled = (bool)(int)$config->getGroupValue(
            '/cron/'.CronHelper::RUNNER_SERVICE_PUB.'/',
            'disabled'
        );
        if (!$this->getData('is_support_mode') && $isDisabled) {
            $fieldSet->addField(
                'current_status_external_controller_cron_state',
                'note',
                [
                    'label' => $this->__('Service Pub Cron State'),
                    'text' => $this->__('Disabled by Developer'),
                    'style' => 'color: red'
                ]
            );
        }

        $isDisabled = (bool)(int)$config->getGroupValue(
            '/cron/'.CronHelper::RUNNER_MAGENTO.'/',
            'disabled'
        );
        if (!$this->getData('is_support_mode') && $isDisabled) {
            $fieldSet->addField(
                'current_status_magento_cron_state',
                'note',
                [
                    'label' => $this->__('Magento Cron State'),
                    'text' => $this->__('Disabled by Developer'),
                    'style' => 'color: red'
                ]
            );
        }

        if ($this->isShownRecommendationsMessage()) {
            $fieldSet = $form->addFieldset(
                'field_setup_instruction',
                [
                    'legend' => $this->__('Additional'),
                    'collapsable' => false
                ]
            );

            $baseDir = $this->getHelper('Client')->getBaseDirectory();
            $fieldSet->addField(
                'setup_instruction_php',
                'note',
                [
                    'label' => $this->__('PHP Command'),
                    'text' => 'php -q '.$baseDir.'cron.php -mdefault 1'
                ]
            );

            $baseUrl = $this->getHelper('Magento')->getBaseUrl();
            $fieldSet->addField(
                'setup_instruction_get',
                'note',
                [
                    'label' => $this->__('GET Command'),
                    'text' => 'GET '.$baseUrl.'cron.php'
                ]
            );
        }

        $fieldSet = $form->addFieldset(
            'field_additional',
            [
                'legend' => $this->__('Additional'),
                'collapsable' => false
            ]
        );

        if ($this->isShownServiceDescriptionMessage()) {
            $fieldSet->addField(
                'setup_instruction_service',
                'note',
                [
                    'label' => $this->__('What is the Cron Type Service?'),
                    'text' => $this->__(
                        'It is M2E Pro Cron System where you were registered automatically during the
                        Extension Installation.
                        No additional Settings are required. Our Service does HTTP calls to your Magento from
                        IP address: <b>%server_ip%</b>.',
                        gethostbyname($config->getGroupValue('/cron/service/', 'hostname'))
                    )
                ]
            );
        }

        $fieldSet->addField(
            'recommendation_message',
            'note',
            [
                'text' => '<strong>'.$this->__(
                    'We recommend to set up your Magento Cron Job to be run every 1 minute (e.g. * * * * *).'
                ) . '</strong>',
                'style' => 'text-align: center;'
            ]
        );

        $this->setForm($form);
        return parent::_prepareLayout();
    }

    //########################################

    public function isShownRecommendationsMessage()
    {
        if (!$this->getData('is_support_mode')) {
            return false;
        }

        if ($this->getHelper('Module\Cron')->isRunnerMagento()) {
            return true;
        }

        if ($this->getHelper('Module\Cron')->isRunnerService() && $this->cronIsNotWorking) {
            return true;
        }

        return false;
    }

    public function isShownServiceDescriptionMessage()
    {
        if (!$this->getData('is_support_mode')) {
            return false;
        }

        if ($this->getHelper('Module\Cron')->isRunnerService() && !$this->cronIsNotWorking) {
            return true;
        }

        return false;
    }

    //########################################
}
