<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ChangeProcessor;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\AbstractModel
 */
abstract class ChangeProcessorAbstract extends \Ess\M2ePro\Model\Template\ChangeProcessorAbstract
{
    //########################################

    const INSTRUCTION_TYPE_QTY_DATA_CHANGED     = 'template_qty_data_changed';
    const INSTRUCTION_TYPE_PRICE_DATA_CHANGED   = 'template_price_data_changed';
    const INSTRUCTION_TYPE_DETAILS_DATA_CHANGED = 'template_details_data_changed';
    const INSTRUCTION_TYPE_IMAGES_DATA_CHANGED  = 'template_images_data_changed';

    //########################################
}
