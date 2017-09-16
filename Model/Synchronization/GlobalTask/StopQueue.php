<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask;

class StopQueue extends AbstractModel
{
    private $itemsWereProcessed = false;

    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::STOP_QUEUE;
    }

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/stop_queue/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Stopping Products';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 90;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        foreach ($this->getHelper('Component')->getComponents() as $component) {
            $tempFlag = $this->sendComponentRequests($component);
            $tempFlag && $this->itemsWereProcessed = true;
        }
    }

    //########################################

    private function sendComponentRequests($component)
    {
        $items = $this->activeRecordFactory->getObject('StopQueue')->getCollection()
                    ->addFieldToFilter('is_processed',0)
                    ->addFieldToFilter('component_mode',$component)
                    ->getItems();

        $accountMarketplaceItems = array();

        foreach ($items as $item) {

            /** @var \Ess\M2ePro\Model\StopQueue $item */
            $tempKey = (string)$item->getMarketplaceId().'_'.$item->getAccountHash();

            if (!isset($accountMarketplaceItems[$tempKey])) {
                $accountMarketplaceItems[$tempKey] = array();
            }

            if (count($accountMarketplaceItems[$tempKey]) >= 100) {
                continue;
            }

            $accountMarketplaceItems[$tempKey][] = $item;
        }

        foreach ($accountMarketplaceItems as $items) {

            if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {

                $parts = array_chunk($items,10);

                foreach ($parts as $part) {
                    if (count($part) <= 0) {
                        continue;
                    }
                    $this->sendAccountMarketplaceRequests($component,$part);
                }

            } else {
                $this->sendAccountMarketplaceRequests($component,$items);
            }

            foreach ($items as $item) {
                /** @var \Ess\M2ePro\Model\StopQueue $item */
                $item->setData('is_processed',1)->save();
            }
        }

        return count($accountMarketplaceItems) > 0;
    }

    private function sendAccountMarketplaceRequests($component, $accountMarketplaceItems)
    {
        try {

            $requestData = array(
                'items' => array(),
            );

            /** @var \Ess\M2ePro\Model\StopQueue $tempItem */
            $tempItem = $accountMarketplaceItems[0];
            $requestData['account'] = $tempItem->getAccountHash();
            if (!is_null($tempItem->getMarketplaceId())) {
                $requestData['marketplace'] = $tempItem->getMarketplaceId();
            }

            foreach ($accountMarketplaceItems as $item) {
                /** @var \Ess\M2ePro\Model\StopQueue $item */
                $tempIndex = count($requestData['items']);
                $component == \Ess\M2ePro\Helper\Component\Ebay::NICK && $tempIndex+=100;
                $requestData['items'][$tempIndex] = $item->getDecodedItemData();
            }

            if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
                $entity = 'item';
                $type = 'update';
                $name = 'ends';
            } else {
                $entity = 'product';
                $type = 'update';
                $name = 'entities';
            }

            $dispatcher = $this->modelFactory->getObject(ucwords($component).'\Connector\Dispatcher');
            $connectorObj = $dispatcher->getVirtualConnector($entity, $type, $name, $requestData);
            $dispatcher->process($connectorObj);

        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception);
        }
    }

    //########################################

    protected function intervalSetLastTime($time)
    {
        if ($this->itemsWereProcessed) {
            return;
        }

        parent::intervalSetLastTime($time);
    }

    //########################################
}