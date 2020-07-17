<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Synchronization\AffectedListingsProducts
 */
class AffectedListingsProducts
    extends \Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts\AffectedListingsProductsAbstract
{
    //########################################

    public function getTemplateNick()
    {
        return \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION;
    }

    //########################################
}
