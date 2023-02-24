<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Url
{
    public const BACK_URL_PARAM_KEY = 'back';

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;
    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param string $backIdOrRoute
     * @param array $backParams
     *
     * @return string
     */
    public function makeBackUrlParam(string $backIdOrRoute, array $backParams = []): string
    {
        $paramsString = !empty($backParams) ? '|' . http_build_query($backParams, '', '&') : '';

        return base64_encode($backIdOrRoute . $paramsString);
    }

    /**
     * @param string $defaultBackIdOrRoute
     * @param array $defaultBackParams
     *
     * @return string
     */
    public function getBackUrlParam(
        string $defaultBackIdOrRoute = 'index',
        array $defaultBackParams = []
    ): string {
        return $this->request->getParams()[self::BACK_URL_PARAM_KEY] ?? $this->makeBackUrlParam(
            $defaultBackIdOrRoute,
            $defaultBackParams
        );
    }

    /**
     * @param string $defaultBackIdOrRoute
     * @param array $defaultBackParams
     * @param array $extendedRoutersParams
     *
     * @return string
     */
    public function getBackUrl(
        string $defaultBackIdOrRoute = 'index',
        array $defaultBackParams = [],
        array $extendedRoutersParams = []
    ): string {
        $back = $this->getBackUrlParam($defaultBackIdOrRoute, $defaultBackParams);
        $back = base64_decode($back);

        $params = [];

        if (strpos($back, '|') !== false) {
            $route = substr($back, 0, strpos($back, '|'));
            parse_str(substr($back, strpos($back, '|') + 1), $params);
        } else {
            $route = $back;
        }

        $extendedRoutersParamsTemp = [];
        foreach ($extendedRoutersParams as $extRouteName => $extParams) {
            if ($route == $extRouteName) {
                $params = array_merge($params, $extParams);
            } else {
                $extendedRoutersParamsTemp[$route] = $params;
            }
        }
        $extendedRoutersParams = $extendedRoutersParamsTemp;

        $route = $this->replaceRoute($route);
        foreach ($extendedRoutersParams as $extRouteName => $extParams) {
            if ($route == $extRouteName) {
                $params = array_merge($params, $extParams);
            }
        }

        $params['_escape_params'] = false;

        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * @param string $route
     *
     * @return string
     */
    private function replaceRoute(string $route): string
    {
        $map = [
            'index' => '*/*/index',
            'list' => '*/*/index',
            'edit' => '*/*/edit',
            'view' => '*/*/view',
        ];

        return $map[$route] ?? $route;
    }
}
