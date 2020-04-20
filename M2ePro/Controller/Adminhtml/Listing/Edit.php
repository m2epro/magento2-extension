<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Edit
 */
class Edit extends Listing
{
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params['id'])) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        $listing = $this->activeRecordFactory->getObjectLoaded('Listing', $params['id']);

        if ($this->getRequest()->isPost()) {
            $listing->addData($params)->save();

            return $this->getResult();
        }

        $global = $this->getHelper('Data\GlobalData');

        $global->setValue('edit_listing', $listing);

        $this->setAjaxContent(
            $this->createBlock('Listing\Edit')
        );
        return $this->getResult();
    }
}
