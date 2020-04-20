<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\Base;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\IsMarketplaceEnabled
 */
class IsMarketplaceEnabled extends Base
{
    //########################################

    public function execute()
    {
        $component = $this->getRequest()->getParam('component');
        if ($component === null) {
            $this->setAjaxContent('Component is not specified.', false);
            return $this->getResult();
        }

        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        if ($marketplaceId === null) {
            $this->setAjaxContent('Marketplace ID is not specified.', false);
            return $this->getResult();
        }

        $marketplaceObj = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace',
            $marketplaceId
        );

        $connection = $this->resourceConnection->getConnection();
        $tableName = null;

        if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $tableName = 'm2epro_ebay_dictionary_marketplace';
        } elseif ($component == \Ess\M2ePro\Helper\Component\Amazon::NICK) {
            $tableName = 'm2epro_amazon_dictionary_marketplace';
        } elseif ($component == \Ess\M2ePro\Helper\Component\Walmart::NICK) {
            $tableName = 'm2epro_walmart_dictionary_marketplace';
        }

        $select = $connection
            ->select()
            ->from($this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName), 'id')
            ->where('marketplace_id = ?', $marketplaceId);

        $result = $connection->fetchOne($select);

        $this->setJsonContent([
            'status' => $result !== false && $marketplaceObj->isStatusEnabled()
        ]);
        return $this->getResult();
    }

    //########################################
}
