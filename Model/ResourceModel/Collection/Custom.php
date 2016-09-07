<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Collection;

class Custom extends \Magento\Framework\Data\Collection\AbstractDb
{
    //########################################

    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->getSelect()) {
            return parent::load($printQuery, $logQuery);
        }

        return $this;
    }

    public function getResource()
    {
        return NULL;
    }

    public function setCustomSize($size)
    {
        $this->_totalRecords = $size;
    }

    public function setCustomIsLoaded($flag)
    {
        $this->_isCollectionLoaded = $flag;
    }

    //########################################
}