<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Import;

/**
 * Class \Ess\M2ePro\Observer\Import\Bunch
 */
class Bunch extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange  */
    private $publicService;
    /** @var \Magento\Catalog\Model\Product  */
    private $magentoProduct;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
        \Magento\Catalog\Model\Product $magentoProduct
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->publicService  = $publicService;
        $this->magentoProduct = $magentoProduct;
    }

    public function process()
    {
        $rowData = $this->getEvent()->getBunch();

        $productIds = [];

        foreach ($rowData as $item) {
            if (!isset($item['sku'])) {
                continue;
            }

            $id = $this->magentoProduct->getIdBySku($item['sku']);
            if (intval($id) > 0) {
                $productIds[] = $id;
            }
        }

        foreach ($productIds as $id) {
            $this->publicService->markProductChanged($id);
        }

        $this->publicService->applyChanges();
    }

    //########################################
}
