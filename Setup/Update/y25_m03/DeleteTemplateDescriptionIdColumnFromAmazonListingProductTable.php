<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m03;

use Ess\M2ePro\Helper\Module\Database\Tables;

class DeleteTemplateDescriptionIdColumnFromAmazonListingProductTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_LISTING_PRODUCT);
        $modifier->dropColumn('template_description_id', true, false);
        $modifier->commit();
    }
}
