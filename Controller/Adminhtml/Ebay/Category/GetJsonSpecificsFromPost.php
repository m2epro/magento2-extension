<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class GetJsonSpecificsFromPost
 * @package Ess\M2ePro\Controller\Adminhtml\Ebay\Category
 */
class GetJsonSpecificsFromPost extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $itemSpecifics = $this->getSpecificsFromPost($this->getRequest()->getPost());

        $this->setJsonContent($itemSpecifics);

        return $this->getResult();
    }

    //########################################
}
