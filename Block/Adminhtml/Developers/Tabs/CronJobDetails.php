<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

class CronJobDetails extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $cronIsNotWorking = false;

    //########################################

    protected function _prepareLayout()
    {
        $form = $this->_formFactory->create();
        $moduleConfig = $this->getHelper('Module')->getConfig();

        $form->addField('cron_job_details',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__(
                    <<<HTML
                     <p>M2E Pro is an automatic tool for inventory data management on eBay, Amazon, etc.
                     The concept of the Module implies initial configurations which then become the basis 
                     of an automatic operation. Continuous data synchronization is required to ensure the automatic
                     execution of tasks. Frequency of data synchronization is determined by the Cron Job.
                     M2E Pro Synchronization can be run using 2 types of Cron Jobs:</p>
                     <ul>
                     <li><p><strong>M2E Pro Cron Service</strong></p>
                        <p>It is the Cron Job which is used in M2E Pro by default. 
                        This Service was created by M2E Pro Developers specially for Module Clients. 
                        It provides an automatic initialization of M2E Pro Extension via HTTP GET request.</p>
                        <p>This solution has a lot of advantages: users do not need to configure
                        the Magento Cron Job, M2E Pro Synchronization is performed in the same environment 
                        where the Magento admin panel or front-end are working, it prevents the risk of the
                        double synchronization problem (which could be a real issue in the earlier Magento versions)
                        along with other major advantages. Which explains why M2E Pro Cron Service is of a higher 
                        priority as compared to other available options.</p>
                         
                        <p><strong>Considering the fact that M2E Pro Cron Service works over HTTP,
                        it is necessary to ensure that the IP of M2E Pro Cron Service is not blocked
                        by the Firewall or whitelisted on your server. </strong></p>
                        <p>The list of M2E Pro Cron Service IPs is 198.27.83.180, 94.23.53.45</p>
                     </li>
                     <li><p><strong>Magento Cron</strong></p>
                     <p>Magento Cron can be automatically enabled only in case when automatic synchronization
                     cannot be executed via M2E Pro Cron Service (e.g. if M2E Pro Cron Service IP is 
                     blocked by the Firewall of the web-server).</p>
                     <p>This type of Cron Jobs is based on the Magento Cron which can cause various problems,
                     e.g. double synchronization (which is inherent into the earlier Magento versions).</p>
                     <p><strong>To provide proper work of Magento Cron Job,
                     primary correct settings are required.</strong></p>
                     </li>
                     <p>In the list below you can find which Cron Job type is used in your system, 
                     the date of the last synchronization run and notices about the Magento Cron settings 
                     which might be helpful if you need to configure Magento Cron.</p>
                     </ul>
HTML
                )
            ]
        );

        $fieldSet = $form->addFieldset('field_current_status',
            [
                'legend' => $this->__('Current Status'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField('current_status_type',
            'note',
            [
                'label' => $this->__('Type'),
                'text' => ucfirst($this->getHelper('Module\Cron')->getRunner())
            ]
        );

        if ($this->getHelper('Module\Cron')->isRunnerService() && !$this->getData('is_support_mode')) {
            $fieldSet->addField('current_status_service_auth_key',
                'note',
                [
                    'label' => $this->__('Service Auth Key'),
                    'text' => $moduleConfig->getGroupValue('/cron/service/', 'auth_key')
                ]
            );
        }

        $cronLastRunTime = $this->getHelper('Module\Cron')->getLastRun();
        if (!is_null($cronLastRunTime)) {
            $this->cronIsNotWorking = $this->getHelper('Module\Cron')->isLastRunMoreThan(12,true);
        } else {
            $cronLastRunTime = 'N/A';
        }

        $fieldSet->addField('current_status_last_run',
            'note',
            [
                'label' => $this->__('Last Run'),
                'text' => "<span>{$cronLastRunTime}</span>"
                          . ($this->cronIsNotWorking ? '' : ' (' . $this->__('not working') . ')'),
                'style' => !$this->cronIsNotWorking ?: 'color: red'
            ]
        );

        if (!$this->getData('is_support_mode')
            && (bool)(int)$moduleConfig->getGroupValue('/cron/service/','disabled')) {

            $fieldSet->addField('current_status_service_cron_state',
                'note',
                [
                    'label' => $this->__('Service Cron State'),
                    'text' => $this->__('Disabled by Developer'),
                    'style' => 'color: red'
                ]
            );
        }

        if (!$this->getData('is_support_mode') &&
            (bool)(int)$moduleConfig->getGroupValue('/cron/magento/','disabled')) {

            $fieldSet->addField('current_status_magento_cron_state',
                'note',
                [
                    'label' => $this->__('Magento Cron State'),
                    'text' => $this->__('Disabled by Developer'),
                    'style' => 'color: red'
                ]
            );
        }

        if ($this->isShownRecommendationsMessage()) {

            $fieldSet = $form->addFieldset('field_setup_instruction',
                [
                    'legend' => $this->__('Additional'),
                    'collapsable' => false
                ]
            );

            $baseDir = $this->getHelper('Client')->getBaseDirectory();

            $fieldSet->addField('setup_instruction_php',
                'note',
                [
                    'label' => $this->__('PHP Command'),
                    'text' => 'php -q '.$baseDir.'cron.php -mdefault 1'
                ]
            );

            $baseUrl = $this->getHelper('Magento')->getBaseUrl();
            $fieldSet->addField('setup_instruction_get',
                'note',
                [
                    'label' => $this->__('GET Command'),
                    'text' => 'GET '.$baseUrl.'cron.php'
                ]
            );
        }

        $fieldSet = $form->addFieldset('field_additional',
            [
                'legend' => $this->__('Additional'),
                'collapsable' => false
            ]
        );

        if ($this->isShownServiceDescriptionMessage()) {
            $fieldSet->addField('setup_instruction_service',
                'note',
                [
                    'label' => $this->__('What is the Cron Type Service?'),
                    'text' => $this->__(
                        'It is M2E Pro Cron System where you were registered automatically during the
                        Extension Installation.
                        No additional Settings are required. Our Service does HTTP calls to your Magento from
                        IP address: <b>%server_ip%</b>.',
                        gethostbyname($moduleConfig->getGroupValue('/cron/service/', 'hostname'))
                    )
                ]
            );
        }

        if (!$this->getData('is_support_mode')) {
            $fieldSet->addField('setup_instruction_schedule',
                'note',
                [
                    'label' => $this->__('Cron Schedule Table'),
                    'text' => "<a href=\"
                                {$this->getUrl('*/adminhtml_development_inspection/cronScheduleTable')}
                                \" target=\"_blank\">
                                {$this->__('Show')}
                                </a>"
                ]
            );
        }

        $fieldSet->addField('recommendation_message',
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