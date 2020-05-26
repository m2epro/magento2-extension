<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue\Locator;

/**
 * Class \Ess\M2ePro\Model\Issue\Locator\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    /**
     * @return \Ess\M2ePro\Model\Issue\DataObject[]
     */
    abstract public function getIssues();

    //########################################
}
