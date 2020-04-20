<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete\Response
 */
class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = [])
    {
        $data = [
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            'general_id' => null,
            'is_general_id_owner' => \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO,
            'template_description_id' => null,
            'online_qty' => 0,
        ];

        $data = $this->appendStatusChangerValue($data);

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->save();
    }

    //########################################
}
