<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel;

class StopQueue extends ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        $this->_init(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_STOP_QUEUE, 'id');
    }
}
