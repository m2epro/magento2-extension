<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;

class Installation extends AbstractInspection
{
    private $cacheConfig;

    public $lastVersion;
    public $installationVersionHistory = array();

    //########################################

    public function __construct(
        Template $context,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        array $data = []
    ) {
        $this->cacheConfig = $cacheConfig;

        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInspectionInstallation');
        // ---------------------------------------

        $this->setTemplate('control_panel/inspection/installation.phtml');

        $this->prepareInfo();
    }

    //########################################

    protected function prepareInfo()
    {
        $this->publicLatestVersion = $this->cacheConfig->getGroupValue('/installation/', 'public_last_version');
        $this->buildLatestVersion  = $this->cacheConfig->getGroupValue('/installation/', 'build_last_version');

        $setupCollection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $setupCollection->addFieldToFilter('version_from', array('notnull' => true));
        $setupCollection->addFieldToFilter('version_to', array('notnull' => true));

        $this->lastUpgradeDate = $setupCollection->getLastItem()->getData('create_date');
    }

    //########################################
}