<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask;

class MagentoProducts extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS;
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
        return 60;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 90;
    }

    //########################################

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('MagentoProducts\DeletedProducts') ? false : $result;
        $result = !$this->processTask('MagentoProducts\AddedProducts') ? false : $result;
        $result = !$this->processTask('MagentoProducts\Inspector') ? false : $result;

        return $result;
    }

    //########################################
}