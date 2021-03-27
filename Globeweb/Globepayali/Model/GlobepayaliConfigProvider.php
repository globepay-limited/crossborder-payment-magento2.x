<?php
/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */
namespace Globeweb\Globepayali\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Globeweb\Globepayali\Model\Globepayali;

class GlobepayaliConfigProvider implements ConfigProviderInterface
{
    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var \Globeweb\Globepayali\Model\Globepayali
     */
    protected $_globepayaliDirect;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        PaymentHelper $paymentHelper
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_globepayaliDirect = $this->_paymentHelper->getMethodInstance(Globepayali::ALIPAY_DIRECT_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'globepayalidirect' => [
                    'redirectUrl' => $this->getRedirectUrl(),
                    'globepayaliLogoUrl' => $this->getGlobepayaliLogoUrl()
                ]
            ]
        ];

        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @return string url
     */
    protected function getRedirectUrl()
    {
        return $this->_globepayaliDirect->getRedirectUrl();
    }

    protected function getGlobepayaliLogoUrl()
    {
        return $this->_globepayaliDirect->getGlobepayaliLogoUrl();
    }
}
