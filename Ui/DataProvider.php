<?php

declare(strict_types=1);

namespace Ess\M2ePro\Ui;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected function prepareUpdateUrl(): void
    {
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }

        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' === $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }

            if ($paramValue !== null) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s/',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
                $this->addFilter(
                    $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('eq')->create()
                );
            }
        }
    }
}
