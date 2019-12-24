<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Log;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Log\ErrorsSummary
 */
class ErrorsSummary extends AbstractBlock
{
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingLogErrorsSummary');
        // ---------------------------------------

        $this->setTemplate('listing/log/errors_summary.phtml');
    }

    protected function _beforeToHtml()
    {
        $tableName = $this->getData('table_name');
        $actionIdsString = $this->getData('action_ids');

        $countField = 'product_id';

        if ($this->getData('type_log') == 'listing') {
            $countField = 'product_id';
        } elseif ($this->getData('type_log') == 'listing_other') {
            $countField = 'listing_other_id';
        }

        $connection = $this->resourceConnection->getConnection();
        $fields = new \Zend_Db_Expr('COUNT(`'.$countField.'`) as `count_products`, `description`');
        $dbSelect = $connection->select()
                             ->from($tableName, $fields)
                             ->where('`action_id` IN ('.$actionIdsString.')')
                             ->where('`type` = ?', \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR)
                             ->group('description')
                             ->order(['count_products DESC'])
                             ->limit(100);

        $newErrors = [];
        $tempErrors = $connection->fetchAll($dbSelect);

        foreach ($tempErrors as $row) {
            $row['description'] = $this->getHelper('View')->getModifiedLogMessage($row['description']);
            $newErrors[] = $row;
        }

        $this->errors = $newErrors;

        return parent::_beforeToHtml();
    }

    //########################################
}
