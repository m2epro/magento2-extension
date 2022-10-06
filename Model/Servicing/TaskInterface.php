<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing;

interface TaskInterface
{
    /** @return string */
    public function getServerTaskName(): string;

    /** @return bool */
    public function isAllowed(): bool;

    /** @return array */
    public function getRequestData(): array;

    /**
     * @param array $data
     *
     * @return void
     */
    public function processResponseData(array $data): void;
}
