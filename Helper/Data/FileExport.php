<?php

namespace Ess\M2ePro\Helper\Data;

class FileExport
{
    public const ALL_ITEMS_GRID = 'All';
    public const UNMANAGED_GRID = 'Unmanaged';

    /**
     * @param string $gridName
     *
     * @return string
     * @throws \Exception
     */
    public function generateExportFileName(string $gridName): string
    {
        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $dateString = $date->format('Ymd_His');

        return $gridName . '_' . $dateString . '.csv';
    }
}
