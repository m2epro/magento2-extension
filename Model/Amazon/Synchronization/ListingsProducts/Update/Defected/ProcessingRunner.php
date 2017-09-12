<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update\Defected;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Partial
{
    const LOCK_ITEM_PREFIX = 'synchronization_amazon_listings_products_update_defected';

    // ##################################

    public function getResponserParams()
    {
        $responserParams = parent::getResponserParams();
        if (is_null($this->getProcessingObject())) {
            return $responserParams;
        }

        $resultData = $this->getProcessingObject()->getResultData();

        if (empty($resultData['next_data_part_number'])) {
            return array_merge($responserParams, array('is_first_part' => true));
        }

        $partNumber = (int)$resultData['next_data_part_number'];
        $isFirstPart = (--$partNumber == 1);

        return array_merge($responserParams, array('is_first_part' => $isFirstPart));
    }

    // ##################################

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(self::LOCK_ITEM_PREFIX.'_'.$params['account_id']);
        $lockItem->setMaxInactiveTime(self::MAX_LIFETIME);
        $lockItem->create();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account', $params['account_id']
        );

        $account->addProcessingLock(NULL, $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization', $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->addProcessingLock(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(self::LOCK_ITEM_PREFIX.'_'.$params['account_id']);
        $lockItem->remove();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account', $params['account_id']
        );

        $account->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    // ##################################
}