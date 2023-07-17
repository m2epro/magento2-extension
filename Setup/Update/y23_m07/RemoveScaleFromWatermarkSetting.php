<?php

namespace Ess\M2ePro\Setup\Update\y23_m07;

class RemoveScaleFromWatermarkSetting extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $ebayDescriptionTable = $this->getFullTableName('ebay_template_description');
        $watermarkSettingsQuery =  $this->getConnection()
                                        ->select()
                                        ->from(
                                            $ebayDescriptionTable,
                                            ['template_description_id', 'watermark_settings']
                                        )
                                        ->where('watermark_settings LIKE ?', '%scale%')
                                        ->query();

        while ($row = $watermarkSettingsQuery->fetch()) {
            $watermarkSettings = json_decode($row['watermark_settings'], true);
            if ($watermarkSettings['scale'] === 1 || $watermarkSettings['scale'] === 2) {
                $watermarkSettings['position'] = 6;
            }
            unset($watermarkSettings['scale']);
            $updateWatermarkSettings = json_encode($watermarkSettings);

            $this->getConnection()->update(
                $ebayDescriptionTable,
                ['watermark_settings' => $updateWatermarkSettings],
                ['template_description_id = ?' => (int)$row['template_description_id']]
            );
        }
    }
}
