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
    /** @var bool */
    public $cronIsNotWorking = false;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Module\Cron */
    private $cronHelper;
    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Module\Cron $cronHelper
     * @param \Ess\M2ePro\Helper\Client $clientHelper
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Module\Cron $cronHelper,
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->config = $config;
        $this->supportHelper = $supportHelper;
        $this->cronHelper = $cronHelper;
        $this->clientHelper = $clientHelper;
        $this->magentoHelper = $magentoHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'cron_job_details',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide'     => true,
                'content'     => $this->__(
                    <<<HTML
On this page, you can review which Cron Job type
(<a target="_blank" href="%url1%">M2E Pro Cron Service</a> or <a target="_blank" href="%url2%">Magento Cron</a>)
 is used in your system to run automatic synchronization and its last run date.
HTML
                    ,
                    $this->supportHelper->getKnowledgebaseArticleUrl('1499822'),
                    $this->supportHelper->getKnowledgebaseArticleUrl('1577329')
                ),
            ]
        );

        $fieldSet = $form->addFieldset(
            'field_current_status',
            [
                'legend'      => $this->__('Current Status'),
                'collapsable' => false,
            ]
        );

        $fieldSet->addField(
            'current_status_type',
            'note',
            [
                'label' => $this->__('Type'),
                'text'  => ucwords(str_replace('_', ' ', $this->cronHelper->getRunner())),
            ]
        );

        if ($this->cronHelper->isRunnerService() && !$this->getData('is_support_mode')) {
            $fieldSet->addField(
                'current_status_service_auth_key',
                'note',
                [
                    'label' => $this->__('Service Auth Key'),
                    'text'  => $this->config->getGroupValue('/cron/service/', 'auth_key'),
                ]
            );
        }

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
                'text'  => "<span>{$cronLastRunTime}</span>" .
                    ($this->cronIsNotWorking ? ' (' . $this->__('not working') . ')' : ''),
                'style' => $this->cronIsNotWorking ? 'color: red' : '',
            ]
        );

        $isDisabled = (bool)(int)$this->config->getGroupValue(
            '/cron/' . CronHelper::RUNNER_SERVICE_CONTROLLER . '/',
            'disabled'
        );
        if (!$this->getData('is_support_mode') && $isDisabled) {
            $fieldSet->addField(
                'current_status_frontend_controller_cron_state',
                'note',
                [
                    'label' => $this->__('Service Controller Cron State'),
                    'text'  => $this->__('Disabled by Developer'),
                    'style' => 'color: red',
                ]
            );
        }

        $isDisabled = (bool)(int)$this->config->getGroupValue(
            '/cron/' . CronHelper::RUNNER_SERVICE_PUB . '/',
            'disabled'
        );
        if (!$this->getData('is_support_mode') && $isDisabled) {
            $fieldSet->addField(
                'current_status_external_controller_cron_state',
                'note',
                [
                    'label' => $this->__('Service Pub Cron State'),
                    'text'  => $this->__('Disabled by Developer'),
                    'style' => 'color: red',
                ]
            );
        }

        $isDisabled = (bool)(int)$this->config->getGroupValue(
            '/cron/' . CronHelper::RUNNER_MAGENTO . '/',
            'disabled'
        );
        if (!$this->getData('is_support_mode') && $isDisabled) {
            $fieldSet->addField(
                'current_status_magento_cron_state',
                'note',
                [
                    'label' => $this->__('Magento Cron State'),
                    'text'  => $this->__('Disabled by Developer'),
                    'style' => 'color: red',
                ]
            );
        }

        if ($this->isShownRecommendationsMessage()) {
            $fieldSet = $form->addFieldset(
                'field_setup_instruction',
                [
                    'legend'      => $this->__('Additional'),
                    'collapsable' => false,
                ]
            );

            $baseDir = $this->clientHelper->getBaseDirectory();
            $fieldSet->addField(
                'setup_instruction_php',
                'note',
                [
                    'label' => $this->__('PHP Command'),
                    'text'  => 'php -q ' . $baseDir . 'cron.php -mdefault 1',
                ]
            );

            $baseUrl = $this->magentoHelper->getBaseUrl();
            $fieldSet->addField(
                'setup_instruction_get',
                'note',
                [
                    'label' => $this->__('GET Command'),
                    'text'  => 'GET ' . $baseUrl . 'cron.php',
                ]
            );
        }

        $fieldSet = $form->addFieldset(
            'field_additional',
            [
                'legend'      => $this->__('Additional'),
                'collapsable' => false,
            ]
        );

        if ($this->isShownServiceDescriptionMessage()) {
            $fieldSet->addField(
                'setup_instruction_service',
                'note',
                [
                    'label' => $this->__('What is the Cron Type Service?'),
                    'text'  => $this->__(
                        'It is M2E Pro Cron System where you were registered automatically during the
                        Extension Installation.
                        No additional Settings are required. Our Service does HTTP calls to your Magento from
                        IP address: <b>%server_ip%</b>.',
                        gethostbyname((string)$this->config->getGroupValue('/cron/service/', 'hostname'))
                    ),
                ]
            );
        }

        $fieldSet->addField(
            'recommendation_message',
            'note',
            [
                'text'  => '<strong>' . $this->__(
                        'We recommend to set up your Magento Cron Job to be run every 1 minute (e.g. * * * * *).'
                    ) . '</strong>',
                'style' => 'text-align: center;',
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

        if ($this->cronHelper->isRunnerMagento()) {
            return true;
        }

        if ($this->cronHelper->isRunnerService() && $this->cronIsNotWorking) {
            return true;
        }

        return false;
    }

    public function isShownServiceDescriptionMessage()
    {
        if (!$this->getData('is_support_mode')) {
            return false;
        }

        if ($this->cronHelper->isRunnerService() && !$this->cronIsNotWorking) {
            return true;
        }

        return false;
    }
}
