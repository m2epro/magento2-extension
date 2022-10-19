<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue;

interface LocatorInterface
{
    /**
     * @return DataObject[]
     */
    public function getIssues(): array;
}
