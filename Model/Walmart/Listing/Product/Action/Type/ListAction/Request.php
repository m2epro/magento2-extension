<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\Request
 */
class Request extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request
{
    const LIST_TYPE_EXIST = 'exist';
    const LIST_TYPE_NEW = 'new';

    const PARENTAGE_PARENT = 'parent';
    const PARENTAGE_CHILD = 'child';

    //########################################

    protected function getActionData()
    {
        $params = $this->getParams();

        $data = [
            'sku' => $params['sku'],
        ];

        $data = array_merge(
            $data,
            $this->getPriceData(),
            $this->getDetailsData()
        );

        return $data;
    }

    //########################################
}
