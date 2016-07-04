<?php
/**
 * Created by PhpStorm.
 * User: HardRock
 * Date: 14.03.2016
 * Time: 17:23
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Listing;

class ClearLog extends Listing
{
    protected $activeRecordFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        Context $context
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->getMessageManager()->addError($this->__('Please select Item(s) to clear.'));
            $this->_redirect('*/*/index');
            return;
        }

        foreach ($ids as $id) {
            $this->activeRecordFactory->getObject('Listing\Log')->clearMessages($id);
        }

        $this->getMessageManager()->addSuccess($this->__('The Listing(s) Log was successfully cleared.'));
        $this->_redirect($this->getHelper('Data')->getBackUrl('list'));
    }
}