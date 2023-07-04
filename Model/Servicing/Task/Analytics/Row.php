<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

class Row
{
    /** @var int */
    public $id;
    /** @var array */
    public $data;

    public function __construct(
        int $id,
        array $data
    ) {
        $this->id = $id;
        $this->data = $data;
    }
}
