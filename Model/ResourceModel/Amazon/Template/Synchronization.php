<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template;

class Synchronization extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_REVISE_UPDATE_PRICE = 'revise_update_price';
    public const COLUMN_REVISE_UPDATE_MAIN_DETAILS = 'revise_update_main_details';
    public const COLUMN_REVISE_UPDATE_IMAGES = 'revise_update_images';

    /** @var bool */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_TEMPLATE_SYNCHRONIZATION,
            'template_synchronization_id'
        );
        $this->_isPkAutoIncrement = false;
    }
}
