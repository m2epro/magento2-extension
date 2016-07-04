<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Processing;

class Action extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    // ########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_processing_action', 'id');
    }

    // ########################################

    public function getIdsWithFullyCompletedItems()
    {
        $mapaiTable = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')
            ->getResource()->getMainTable();

        $select = $this->getConnection()->select()
            ->from(array('mapa' => $this->getMainTable()), 'id')
            ->joinLeft(
                array('mapai' => $mapaiTable),
                'mapa.id = mapai.action_id AND mapai.is_completed = 0',
                array()
            )
            ->group('mapa.id')
            ->having(new \Zend_Db_Expr('count(mapai.id) = 0'));

        return $this->getConnection()->fetchCol($select);
    }

    // ########################################
}