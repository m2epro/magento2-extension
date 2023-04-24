<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Shipping;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    public function isDifferent(): bool
    {
        return $this->isDetailsDifferent();
    }

    public function isDetailsDifferent(): bool
    {
        $keys = [
            'template_id'
        ];

        return $this->isSettingsDifferent($keys);
    }
}
