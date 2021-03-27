<?php
/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */
namespace Globeweb\Globepay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Globeweb\Globepay\Model\Globepay;

class GlobepayConfigProvider implements ConfigProviderInterface
{
    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var \Globeweb\Globepay\Model\Globepay
     */
    protected $_globepayDirect;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        PaymentHelper $paymentHelper
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_globepayDirect = $this->_paymentHelper->getMethodInstance(Globepay::ALIPAY_DIRECT_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'globepaydirect' => [
                    'redirectUrl' => $this->getRedirectUrl(),
                    'globepayLogoUrl' => $this->getGlobepayLogoUrl()
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
        return $this->_globepayDirect->getRedirectUrl();
    }

    protected function getGlobepayLogoUrl()
    {
        return $this->_globepayDirect->getGlobepayLogoUrl();
    }
}
