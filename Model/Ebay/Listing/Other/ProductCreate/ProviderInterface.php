<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

interface ProviderInterface
{
    public function getItem(\Ess\M2ePro\Model\Listing\Other $unmanagedProduct): ChannelItem;
}
