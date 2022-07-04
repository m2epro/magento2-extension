<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Actual
 */
class Actual extends AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $maintenanceHelper;

    /** @var string */
    public $systemName;
    /** @var int|string */
    public $systemTime;
    /** @var string */
    public $magentoInfo;
    /** @var string */
    public $publicVersion;
    /** @var mixed */
    public $setupVersion;
    /** @var mixed|null */
    public $moduleEnvironment;
    /** @var bool */
    public $maintenanceMode;
    /** @var false|mixed|string */
    public $coreResourceVersion;
    /** @var false|mixed|string */
    public $coreResourceDataVersion;
    /** @var array|string */
    public $phpVersion;
    /** @var string */
    public $phpApi;
    /** @var float|int */
    public $memoryLimit;
    /** @var false|string */
    public $maxExecutionTime;
    /** @var string|null */
    public $mySqlVersion;
    /** @var string */
    public $mySqlDatabaseName;
    /** @var string */
    public $mySqlPrefix;

    /**
     * @param \Ess\M2ePro\Helper\Client $clientHelper
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Module\Maintenance $maintenanceHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Maintenance $maintenanceHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->clientHelper = $clientHelper;
        $this->magentoHelper = $magentoHelper;
        $this->moduleHelper = $moduleHelper;
        $this->dataHelper = $dataHelper;
        $this->maintenanceHelper = $maintenanceHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelSummaryInfo');
        $this->setTemplate('control_panel/info/actual.phtml');
    }

    // ----------------------------------------

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $this->systemName = $this->clientHelper->getSystem();
        $this->systemTime = $this->dataHelper->getCurrentGmtDate();
        // ---------------------------------------

        $this->magentoInfo = $this->__(ucwords($this->magentoHelper->getEditionName())) .
            ' (' . $this->magentoHelper->getVersion() . ')';

        // ---------------------------------------
        $this->publicVersion = $this->moduleHelper->getPublicVersion();
        $this->setupVersion = $this->moduleHelper->getSetupVersion();
        $this->moduleEnvironment = $this->moduleHelper->getEnvironment();
        // ---------------------------------------

        // ---------------------------------------
        $this->maintenanceMode = $this->maintenanceHelper->isEnabled();
        $this->coreResourceVersion = $this->moduleHelper->getSchemaVersion();
        $this->coreResourceDataVersion = $this->moduleHelper->getDataVersion();
        // ---------------------------------------

        // ---------------------------------------
        $this->phpVersion = $this->clientHelper->getPhpVersion();
        $this->phpApi = $this->clientHelper->getPhpApiName();
        // ---------------------------------------

        // ---------------------------------------
        $this->memoryLimit = $this->clientHelper->getMemoryLimit(true);
        $this->maxExecutionTime = ini_get('max_execution_time');
        // ---------------------------------------

        // ---------------------------------------
        $this->mySqlVersion = $this->clientHelper->getMysqlVersion();
        $this->mySqlDatabaseName = $this->magentoHelper->getDatabaseName();
        $this->mySqlPrefix = $this->magentoHelper->getDatabaseTablesPrefix();
        if (empty($this->mySqlPrefix)) {
            $this->mySqlPrefix = $this->__('disabled');
        }
        // ---------------------------------------

        return parent::_beforeToHtml();
    }
}
