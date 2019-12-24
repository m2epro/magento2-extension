<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\OtherIssues
 */
class OtherIssues extends AbstractInspection
{
    private $resourceConnection;

    //########################################

    public function __construct(
        Template $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInspectionOtherIssues');
        // ---------------------------------------

        $this->setTemplate('control_panel/inspection/otherIssues.phtml');
    }

    //########################################

    public function isShown()
    {
        return $this->isMagicQuotesEnabled() ||
               $this->isGdLibraryUnAvailable() ||
               $this->isZendOpcacheAvailable();
    }

    //########################################

    public function isMagicQuotesEnabled()
    {
        return (bool)ini_get('magic_quotes_gpc');
    }

    public function isGdLibraryUnAvailable()
    {
        return !extension_loaded('gd') || !function_exists('gd_info');
    }

    public function isZendOpcacheAvailable()
    {
        return $this->getHelper('Client\Cache')->isZendOpcacheAvailable();
    }

    public function isSystemLogNotEmpty()
    {
        $table = $this->activeRecordFactory->getObject('Log\System')->getResource()->getMainTable();

        if (!$this->getHelper('Module_Database_Structure')->isTableExists($table)) {
            return false;
        }

        $totalCount = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['log'   => $table],
                ['count' => new \Zend_Db_Expr('COUNT(*)')]
            )
            ->query()->fetchColumn();

        return (bool)(int)$totalCount;
    }

    //########################################
}
