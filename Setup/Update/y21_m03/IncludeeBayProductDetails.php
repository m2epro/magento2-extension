<?php

namespace Ess\M2ePro\Setup\Update\y21_m03;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m02\AmazonPL
 */
class IncludeeBayProductDetails extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $ebayTemplateDescriptionTable = $this->getFullTableName('ebay_template_description');
        $query = $this->installer->getConnection()
            ->select()
            ->from($ebayTemplateDescriptionTable, ['template_description_id', 'product_details'])
            ->query();

        while ($row = $query->fetch()) {
            $productDetails = (array)json_decode($row['product_details'], true);
            if (isset($productDetails['include_description'])) {
                $productDetails['include_ebay_details'] = $productDetails['include_description'];
                unset($productDetails['include_description']);

                $this->installer->getConnection()->update(
                    $ebayTemplateDescriptionTable,
                    [
                        'product_details' => json_encode($productDetails)
                    ],
                    [
                        'template_description_id = ?' => (int)$row['template_description_id']
                    ]
                );
            }
        }
    }

    //########################################
}
