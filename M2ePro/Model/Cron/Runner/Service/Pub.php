<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner\Service;

/**
 * Class \Ess\M2ePro\Model\Cron\Runner\Service\Pub
 */
class Pub extends AbstractModel
{
    //########################################

    public function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::RUNNER_SERVICE_PUB;
    }

    //########################################
}
