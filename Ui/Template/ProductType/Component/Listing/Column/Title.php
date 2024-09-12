<?php

declare(strict_types=1);

namespace Ess\M2ePro\Ui\Template\ProductType\Component\Listing\Column;

class Title extends \Magento\Ui\Component\Listing\Columns\Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$row) {
            $isInvalid = (bool)(int)$row['invalid'];
            $isOutOfDate = (bool)(int)$row['out_of_date'];
            if (
                !$isInvalid
                && !$isOutOfDate
            ) {
                continue;
            }

            $title = $row['template_title'];

            if ($isOutOfDate) {
                $title .= ' ' . <<<HTML
<span style="color: orange">(Out Of Date)</span>
HTML;
            }

            if ($isInvalid) {
                $message = (string)__(
                    'This Product Type is no longer supported by Amazon. '
                    . 'Please assign another Product Type to the products that use it.',
                );

                $title = <<<HTML
    $title
    <br>
<span style="color: red">
    $message
</span>
HTML;
            }

            $row['template_title'] = $title;
        }

        return $dataSource;
    }
}
