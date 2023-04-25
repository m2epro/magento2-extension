<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m04;

class RemoveUnavailableDataType extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $this->removeImagesFromScheduledActions();
        $this->removeImagesFromProcessings();
    }

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function removeImagesFromScheduledActions(): void
    {
        $scheduledActionTable = $this->getFullTableName('listing_product_scheduled_action');

        $stmt = $this->getConnection()->select()
            ->from(
                $scheduledActionTable,
                ['id', 'additional_data']
            )
            ->where('component = ?', 'amazon')
            ->where('additional_data LIKE ?', '%images%')
            ->query();

        while ($row = $stmt->fetch()) {
            $additionalData = json_decode($row['additional_data'], true);
            $isSaveRequired = false;

            if (!empty($additionalData['configurator']['allowed_data_types'])) {
                $key = array_search('images', $additionalData['configurator']['allowed_data_types']);
                if ($key) {
                    unset($additionalData['configurator']['allowed_data_types'][$key]);
                    $isSaveRequired = true;
                }
            }

            if ($isSaveRequired) {
                $value = json_encode($additionalData);
                $this->getConnection()->update(
                    $scheduledActionTable,
                    ['additional_data' => $value],
                    ['id = ?' => (int)$row['id']]
                );
            }
        }
    }

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function removeImagesFromProcessings(): void
    {
        $processingTable = $this->getFullTableName('processing');

        $stmt = $this->getConnection()->select()
            ->from(
                $processingTable,
                ['id', 'params']
            )
            ->where('params LIKE ?', '%images%')
            ->query();

        while ($row = $stmt->fetch()) {
            $params = json_decode($row['params'], true);
            $isSaveRequired = false;

            if (
                !empty($params['component'])
                && $params['component'] === 'Amazon'
                && !empty($params['configurator']['allowed_data_types'])
            ) {
                $key = array_search('images', $params['configurator']['allowed_data_types']);
                if ($key) {
                    unset($params['configurator']['allowed_data_types'][$key]);
                    $isSaveRequired = true;
                }
            }

            if ($isSaveRequired) {
                $value = json_encode($params);
                $this->getConnection()->update(
                    $processingTable,
                    ['params' => $value],
                    ['id = ?' => (int)$row['id']]
                );
            }
        }
    }
}
