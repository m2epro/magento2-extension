<?php
/**
 * Created by PhpStorm.
 * User: HardRock
 * Date: 14.03.2016
 * Time: 16:40
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\Base;

class IsMarketplaceEnabled extends Base
{
    //########################################

    public function execute()
    {
        $component = $this->getRequest()->getParam('component');
        if (is_null($component)) {
            $this->setAjaxContent('Component is not specified.', false);
            return $this->getResult();
        }

        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        if (is_null($marketplaceId)) {
            $this->setAjaxContent('Marketplace ID is not specified.', false);
            return $this->getResult();
        }

        $marketplaceObj = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace', $marketplaceId
        );

        $connection = $this->resourceConnection->getConnection();
        $tableName = null;

        if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $tableName = 'm2epro_ebay_dictionary_marketplace';
        } elseif ($component == \Ess\M2ePro\Helper\Component\Amazon::NICK) {
            $tableName = 'm2epro_amazon_dictionary_marketplace';
        }

        $select = $connection
            ->select()
            ->from($this->resourceConnection->getTableName($tableName), 'id')
            ->where('marketplace_id = ?', $marketplaceId);

        $result = $connection->fetchOne($select);

        $this->setJsonContent([
            'status' => $result !== false && $marketplaceObj->isStatusEnabled()
        ]);
        return $this->getResult();
    }

    //########################################
} 