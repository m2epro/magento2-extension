<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order;

/**
 * Class Ess\M2ePro\Model\Amazon\Order\Invoice
 */
class Invoice extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const DOCUMENT_TYPE_INVOICE = 'invoice';
    const DOCUMENT_TYPE_CREDIT_NOTE = 'credit_note';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Order\Invoice');
    }

    //########################################

    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    public function getDocumentType()
    {
        return $this->getData('document_type');
    }

    public function getDocumentNumber()
    {
        return $this->getData('document_number');
    }

    public function getDocumentData()
    {
        return $this->getSettings('document_data');
    }

    //########################################
}
