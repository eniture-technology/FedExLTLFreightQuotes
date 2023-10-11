<?php

namespace Eniture\FedExLTLFreightQuotes\Cron;

use Eniture\FedExLTLFreightQuotes\Helper\EnConstants;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class PlanUpgrade
{
    /**
     * @var String URL
     */
    private $curlUrl = EnConstants::PLAN_URL;
    /**
     * @var Logger Object
     */
    protected $logger;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var ConfigInterface
     */
    private $resourceConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param ConfigInterface $resourceConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Curl $curl,
        ConfigInterface $resourceConfig,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->resourceConfig = $resourceConfig;
        $this->logger = $logger;
    }

    /**
     * upgrade plan information
     */
    public function execute()
    {
        $domain = $this->storeManager->getStore()->getUrl();
        $webHookUrl = $domain . 'fedexltlfreightquotes';
        $postData = http_build_query([
            'platform' => 'magento2',
            'carrier' => '70',
            'store_url' => $domain,
            'webhook_url' => $webHookUrl,
        ]);
        $this->curl->post($this->curlUrl, $postData);
        $output = $this->curl->getBody();
        
        if(!empty($output) && is_string($output)){
            $result = json_decode($output, true);
        }else{
            $result = [];
        }

        $plan = $result['pakg_group'] ?? '';
        $expireDay = $result['pakg_duration'] ?? '';
        $expiryDate = $result['expiry_date'] ?? '';
        $planType = $result['plan_type'] ?? '';
        $pkgPrice = $result['pakg_price'] ?? 0;
        if ($pkgPrice == 0) {
            $plan = 0;
        }

        $today = date('F d, Y');
        if (strtotime($today) > strtotime($expiryDate)) {
            $plan = '-1';
        }

        $this->saveConfigurations('eniture/ENFedExLTL/plan', "$plan");
        $this->saveConfigurations('eniture/ENFedExLTL/expireday', "$expireDay");
        $this->saveConfigurations('eniture/ENFedExLTL/expiredate', "$expiryDate");
        $this->saveConfigurations('eniture/ENFedExLTL/storetype', "$planType");
        $this->saveConfigurations('eniture/ENFedExLTL/pakgprice', "$pkgPrice");
        $this->saveConfigurations('eniture/ENFedExLTL/label', "ENITURE LTL FREIGHT QUOTES - FOR FEDEX");

        $this->logger->info($output);
    }

    /**
     * @param $path
     * @param $value
     */
    public function saveConfigurations($path, $value)
    {
        $this->resourceConfig->saveConfig(
            $path,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }
}
