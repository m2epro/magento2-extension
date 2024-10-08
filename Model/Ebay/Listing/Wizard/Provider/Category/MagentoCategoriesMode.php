<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category;

use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\Listing;

class MagentoCategoriesMode
{
    private ActiveRecordFactory $activeRecordFactory;
    public function __construct(ActiveRecordFactory $activeRecordFactory)
    {
        $this->activeRecordFactory = $activeRecordFactory;
    }

    public function getTemplatesDataPerMagentoCategory(Listing $listing, array $customTemplates): array
    {
        $ebayListing = $listing->getChildObject();

        $categoriesData = [];

        $tempData = $ebayListing->getLastPrimaryCategory(['ebay_primary_category', 'mode_category']);

        foreach ($tempData as $categoryId => $data) {
            if (!empty($data['mode']) && !empty($data['value']) && !empty($data['path'])) {
                $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
                $template->loadByCategoryValue(
                    $data['value'],
                    $data['mode'],
                    $listing->getMarketplaceId(),
                    0
                );

                if ($template->getId()) {
                    $isCustomTemplate = isset($customTemplates[$categoryId]) ? 1 : 0;
                    $categoriesData[$categoryId][eBayCategory::TYPE_EBAY_MAIN] = [
                        'mode' => $data['mode'],
                        'value' => $data['value'],
                        'path' => $data['path'],
                        'template_id' => $template->getId(),
                        'is_custom_template' => $isCustomTemplate,
                    ];
                }
            }
        }

        $tempData = $ebayListing->getLastPrimaryCategory(['ebay_store_primary_category', 'mode_category']);

        foreach ($tempData as $categoryId => $data) {
            if (!empty($data['mode']) && !empty($data['value']) && !empty($data['path'])) {
                $template = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
                $template->loadByCategoryValue(
                    $data['value'],
                    $data['mode'],
                    $listing->getAccountId()
                );

                if ($template->getId()) {
                    $categoriesData[$categoryId][eBayCategory::TYPE_STORE_MAIN] = [
                        'mode' => $data['mode'],
                        'value' => $data['value'],
                        'path' => $data['path'],
                        'template_id' => $template->getId(),
                        'is_custom_template' => $data['is_custom_template'] ?? 0,
                    ];
                }
            }
        }

        return $categoriesData;
    }
}
