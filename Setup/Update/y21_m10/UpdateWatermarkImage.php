<?php

namespace Ess\M2ePro\Setup\Update\y21_m10;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\Module\Setup;

class UpdateWatermarkImage extends AbstractFeature
{
    /**
     * @var \Ess\M2ePro\Helper\Data\Cache\Permanent
     */
    protected $permanentCache;

    //########################################

    public function __construct
    (
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        Setup $installer,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache
    )
    {
        parent::__construct($helperFactory, $modelFactory, $installer);

        $this->permanentCache = $permanentCache;
    }

    //########################################

    public function execute()
    {
        $ebayTemplateDescription =  $this->getFullTableName('ebay_template_description');

        $query = $this->getConnection()
            ->select()
            ->from($ebayTemplateDescription)
            ->query();

        while ($row = $query->fetch()) {
            if ($row['watermark_image'] !== null) {
                $newWatermarkImage = base64_encode($row['watermark_image']);

                $this->installer->getConnection()->update(
                    $ebayTemplateDescription,
                    ['watermark_image' => $newWatermarkImage],
                    ['template_description_id = ?' => $row['template_description_id']]
                );
            }
        }

        $this->permanentCache->removeTagValues('ebay_template_description');
    }

    //########################################
}
