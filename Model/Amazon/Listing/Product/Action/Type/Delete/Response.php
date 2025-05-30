<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete;

use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product as AmazonListingProductResource;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete\Response
 */
class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @ingeritdoc
     */
    public function processSuccess(array $params = []): void
    {
        $data = [
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            'general_id' => null,
            'is_general_id_owner' => \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO,
            'template_product_type_id' => null,
            AmazonListingProductResource::COLUMN_ONLINE_QTY => 0,
            AmazonListingProductResource::COLUMN_ONLINE_QTY_LAST_UPDATE_DATE => null,
        ];

        $data = $this->appendStatusChangerValue($data);
        $this->getListingProduct()->addData($data);

        $this->getAmazonListingProduct()->setIsStoppedManually(false);

        $this->getListingProduct()->save();
    }

    //########################################
}
