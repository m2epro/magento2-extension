<?php

namespace Ess\M2ePro\Setup\Update\y22_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ClearPartListingAdditionalData extends AbstractFeature
{
    private const TYPE_EBAY_MAIN = 0;
    private const TYPE_EBAY_SECONDARY = 1;

    /** @var array */
    private $ebayTemplateCategoryIds = [];

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $ebayTemplateCategory = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_template_category'), 'id')
            ->query();

        while ($row = $ebayTemplateCategory->fetch()) {
            $this->ebayTemplateCategoryIds[] = $row['id'];
        }

        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('listing'), ['id', 'additional_data'])
            ->where('component_mode = ?', 'ebay')
            ->where('additional_data LIKE ?', '%"mode_same_category_data":%')
            ->query();

        while ($row = $query->fetch()) {
            $additionalData = (array)json_decode($row['additional_data'], true);

            if (empty($additionalData['mode_same_category_data'])) {
                continue;
            }

            $save = false;

            foreach ($additionalData['mode_same_category_data'] as $key => $templateData) {
                if (
                     !in_array($templateData['template_id'], $this->ebayTemplateCategoryIds, true)
                     && in_array($key, $this->getEbayCategoryTypes(), true)
                ) {
                    unset($additionalData['mode_same_category_data'][$key]);

                    if (empty($additionalData['mode_same_category_data'])) {
                        unset($additionalData['mode_same_category_data']);
                    }

                    $save = true;
                }
            }

            if ($save) {
                $this->getConnection()->update(
                    $this->getFullTableName('listing'),
                    ['additional_data' => json_encode($additionalData)],
                    ['id = ?' => $row['id']]
                );
            }
        }
    }

    /**
     * @return int[]
     */
    private function getEbayCategoryTypes(): array
    {
        return [
            self::TYPE_EBAY_MAIN,
            self::TYPE_EBAY_SECONDARY,
        ];
    }
}
