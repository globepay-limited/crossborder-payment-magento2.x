<?php
/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */
namespace Globeweb\Globepayali\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->curlFactory = $curlFactory;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
//         $requestArr = [
//             'key' => 'bdbf281c595d3f496e05970f0d19cb9b',
//             'version' => '2.0.1',
//             'name' => 'Globepayali',
//             'domain' => urlencode($this->urlBuilder->getBaseUrl()),
//         ];
//         $bizString = $this->toUrlParams($requestArr);
//         $url = 'http://sta.hellomagento2.com/sta.php?' . $bizString;
//         $http = $this->curlFactory->create();
//         $config = ['timeout' => 60, 'verifypeer' => false, 'header' => false];
//         $http->setConfig($config);
//         $http->write(
//             \Zend_Http_Client::GET,
//             $url,
//             '1.1',
//             []
//         );
//         $response = $http->read();
//         $this->logger->info($response);
//         $http->close();
    }

    protected function toUrlParams($para)
    {
        $buff = "";
        foreach ($para as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }

        $buff = trim($buff, "&");
        return $buff;
    }

}
