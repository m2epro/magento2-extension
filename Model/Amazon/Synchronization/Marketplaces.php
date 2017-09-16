<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization;

class Marketplaces extends AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES;
    }

    protected function getNick()
    {
        return NULL;
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function isPossibleToRun()
    {
        if (!parent::isPossibleToRun()) {
            return false;
        }

        $params = $this->getParams();

        if (empty($params['marketplace_id'])) {
            return false;
        }

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace **/
        $marketplace = $this->amazonFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        if (!$marketplace->isComponentModeAmazon() || !$marketplace->isStatusEnabled()) {
            return false;
        }

        return true;
    }

    protected function configureLockItemBeforeStart()
    {
        parent::configureLockItemBeforeStart();

        $componentName = '';
        if (count($this->getHelper('Component')->getEnabledComponents()) > 1) {
            $componentName = $this->getHelper('Component\Amazon')->getTitle() . ' ';
        }

        $params = $this->getParams();

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace **/
        $marketplace = $this->amazonFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);
        $this->getActualLockItem()->setTitle(
            $componentName.$this->getHelper('Module\Translation')->__($marketplace->getTitle())
        );
    }

    /**
     * @return bool
     */
    public function performActions()
    {
        $result = true;

        $result = !$this->processTask('Marketplaces\Details') ? false : $result;
        $result = !$this->processTask('Marketplaces\Categories') ? false : $result;
        $result = !$this->processTask('Marketplaces\Specifics') ? false : $result;

        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');

        return $result;
    }

    //########################################
}