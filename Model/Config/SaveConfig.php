<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Config;

use Closure;
use Magento\Config\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Request\Http;

/**
 * Class SaveConfig
 * @package Eniture\FedExLTLFreightQuotes\Model\Config
 */
class SaveConfig
{
    /**
     * @var Http
     */
    private $request;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;


    /**
     * SaveConfig constructor.
     * @param Http $request
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Http $request,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->request          = $request;
        $this->scopeConfig     = $scopeConfig;
        $this->configWriter    = $configWriter;
    }

    /**
     *
     * @param Config $subject
     * @param Closure $proceed
     * @return type
     */
    public function aroundSave(
        Config $subject,
        Closure $proceed
    ) {

        if (isset($postData['config_state']['fedexLtlQuoteSetting_third'])) {
            $isActive = (isset($postData['groups']['third']['fields']['RADforLiftgate']) && $postData['groups']['third']['fields']['RADforLiftgate'] == 'yes') ? 'yes' : 'no';
            $this->configWriter->save('fedexLtlQuoteSetting/third/RADforLiftgate', $isActive, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
        }

        return $proceed();
    }
}
