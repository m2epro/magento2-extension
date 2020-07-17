<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\ChangeProcessor;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\AbstractModel
 */
abstract class ChangeProcessorAbstract extends \Ess\M2ePro\Model\Template\ChangeProcessorAbstract
{
    //########################################

    const INSTRUCTION_TYPE_QTY_DATA_CHANGED        = 'template_qty_data_changed';
    const INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED   = 'template_lag_time_data_changed';
    const INSTRUCTION_TYPE_PRICE_DATA_CHANGED      = 'template_price_data_changed';
    const INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED = 'template_promotions_data_changed';
    const INSTRUCTION_TYPE_DETAILS_DATA_CHANGED    = 'template_details_data_changed';

    //########################################
}
