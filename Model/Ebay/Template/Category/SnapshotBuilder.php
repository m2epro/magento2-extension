<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category\SnapshotBuilder
 * @method \Ess\M2ePro\Model\Ebay\Template\Category getModel()
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->getModel()->getData();
        if (empty($data)) {
            return [];
        }

        $data['specifics'] = $this->getModel()->getSpecifics();

        foreach ($data['specifics'] as &$specificData) {
            unset($specificData['id'], $specificData['template_category_id']);
            foreach ($specificData as &$value) {
                $value !== null && !is_array($value) && $value = (string)$value;
            }
        }

        return $data;
    }

    //########################################
}
