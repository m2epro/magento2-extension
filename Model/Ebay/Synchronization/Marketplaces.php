<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization;

class Marketplaces extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES;
    }

    /**
     * @return null
     */
    protected function getNick()
    {
        return NULL;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    /**
     * @return bool
     */
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
        $marketplace = $this->ebayFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        if (!$marketplace->isComponentModeEbay() || !$marketplace->isStatusEnabled()) {
            return false;
        }

        return true;
    }

    protected function configureLockItemBeforeStart()
    {
        parent::configureLockItemBeforeStart();

        $componentName = '';
        if (count($this->getHelper('Component')->getEnabledComponents()) > 1) {
            $componentName = $this->getHelper('Component\Ebay')->getTitle() . ' ';
        }

        $params = $this->getParams();

        /** @var $marketplace \Ess\M2ePro\Model\Marketplace **/
        $marketplace = $this->ebayFactory->getObjectLoaded('Marketplace', (int)$params['marketplace_id']);

        $marketplace->getNativeId() == 100 && $componentName = '';
        $this->getActualLockItem()->setTitle(
            $componentName.$this->getHelper('Module\Translation')->__($marketplace->getTitle())
        );
    }

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('Marketplaces\Details') ? false : $result;
        $result = !$this->processTask('Marketplaces\Categories') ? false : $result;
        $result = !$this->processTask('Marketplaces\MotorsEpids') ? false : $result;
        $result = !$this->processTask('Marketplaces\MotorsKtypes') ? false : $result;

        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');

        return $result;
    }

    //########################################
}