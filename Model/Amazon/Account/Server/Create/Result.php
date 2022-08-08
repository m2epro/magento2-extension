<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account\Server\Create;

class Result
{
    /** @var string */
    private $hash;
    /** @var array */
    private $info;

    public function __construct(string $hash, array $info)
    {
        $this->hash = $hash;
        $this->info = $info;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }
}
