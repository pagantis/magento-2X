<?php

namespace DigitalOrigin\Pmt\Test\Buy;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;
use Httpful\Request;
use PagaMasTarde\ModuleUtils\Exception\AlreadyProcessedException;
use PagaMasTarde\ModuleUtils\Exception\MerchantOrderNotFoundException;
use PagaMasTarde\ModuleUtils\Exception\QuoteNotFoundException;

/**
 * @requires magento-install
 * @requires magento-register
 *
 * @group magento-buy-registered
 */
class PaylaterMgBuyRegisteredTest extends AbstractMg21Selenium
{
    /**
     * @var string
     */
    protected $checkoutPrice;

    /**
     * @var string
     */
    protected $confirmationPrice;

    /**
     * @var string
     */
    protected $notifyUrl;

    /**
     * @var array $configs
     */
    protected $configs = array(
        "PMT_TITLE",
        "PMT_SIMULATOR_DISPLAY_TYPE",
        "PMT_SIMULATOR_DISPLAY_SKIN",
        "PMT_SIMULATOR_DISPLAY_POSITION",
        "PMT_SIMULATOR_START_INSTALLMENTS",
        "PMT_SIMULATOR_CSS_POSITION_SELECTOR",
        "PMT_SIMULATOR_DISPLAY_CSS_POSITION",
        "PMT_SIMULATOR_CSS_PRICE_SELECTOR",
        "PMT_SIMULATOR_CSS_QUANTITY_SELECTOR",
        "PMT_FORM_DISPLAY_TYPE",
        "PMT_DISPLAY_MIN_AMOUNT",
        "PMT_URL_OK",
        "PMT_URL_KO",
    );

    /**
     * @throws  \Exception
     */
    public function testBuy()
    {
        $this->createAccount();
        $this->goToProduct();
        $this->configureProduct();
        $this->checkProductPage();
        $this->goToCheckout();
        $this->goToPayment();
        $this->setCheckoutPrice($this->preparePaymentMethod());
        $this->verifyPaylater();
        $this->verifyOrder();
        $this->setConfirmationPrice($this->verifyOrderInformation());
        $this->comparePrices();
        $this->checkProcessed();
        $this->quit();
    }

    private function comparePrices()
    {
        $this->assertContains($this->getCheckoutPrice(), $this->getConfirmationPrice(), "PR46");
    }

    private function checkProcessed()
    {
        $orderUrl = $this->webDriver->getCurrentURL();
        $this->assertNotEmpty($orderUrl);

        $orderArray = explode('/', $orderUrl);
        $magentoOrderId = (int)$orderArray['8'];
        $this->assertNotEmpty($magentoOrderId);
        $notifyFile = 'index/';
        $quoteId=($magentoOrderId)-1;
        $version = '';

        if (version_compare($this->version, '23') >= 0) {
            $notifyFile = 'indexV2/';
            $quoteId = $magentoOrderId;
            $version = "V2";
        }

        $notifyUrl = sprintf(
            "%s%s%s%s%s%s",
            $this->configuration['magentoUrl'],
            self::NOTIFICATION_FOLDER,
            $notifyFile,
            '?',
            self::NOTIFICATION_PARAMETER,
            '='
        );

        $response = Request::post($notifyUrl.$quoteId)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, print_r($response, true));
        $this->assertContains(
            AlreadyProcessedException::ERROR_MESSAGE,
            $response->body->result,
            "PR51=>".$notifyUrl.$quoteId." = ".$response->body->result
        );

        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, print_r($response, true));
        $this->assertContains(
            QuoteNotFoundException::ERROR_MESSAGE,
            $response->body->result,
            "PR58=>".$notifyUrl.$quoteId." = ".$response->body->result
        );

        $quoteId=0;
        $response = Request::post($notifyUrl.$quoteId)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, print_r($response, true));
        $this->assertContains(
            MerchantOrderNotFoundException::ERROR_MESSAGE,
            $response->body->result,
            "PR51=>".$notifyUrl.$quoteId." = ".$response->body->result
        );

        $logUrl = sprintf(
            "%s%s%s%s%s",
            $this->configuration['magentoUrl'],
            self::LOG_FOLDER,
            $version,
            '?secret=',
            $this->configuration['secretKey']
        );
        $response = Request::get($logUrl)->expects('json')->send();
        $this->assertEquals(3, "PR57=>".$logUrl." = ".count($response->body));

        $notifyUrl = self::MAGENTO_URL.self::CONFIG_FOLDER.'post?secret='.$this->configuration['secretKey'];

        $response = Request::get($notifyUrl)->expects('json')->send();
        foreach ($this->configs as $config) {
            $this->assertArrayHasKey($config, "PR61=>".$response->body);
        }

        $body = array('PMT_TITLE' => 'changed');
        $response = Request::post($notifyUrl)
                           ->body($body, Mime::FORM)
                           ->expectsJSON()
                           ->send();
        $this->assertEquals('changed', "PR62=>".$notifyUrl." = ".$response->body->PMT_TITLE);
    }

    /**
     * @return string
     */
    public function getCheckoutPrice()
    {
        return $this->checkoutPrice;
    }

    /**
     * @param string $checkoutPrice
     */
    public function setCheckoutPrice($checkoutPrice)
    {
        $this->checkoutPrice = $checkoutPrice;
    }

    /**
     * @return string
     */
    public function getConfirmationPrice()
    {
        return $this->confirmationPrice;
    }

    /**
     * @param string $confirmationPrice
     */
    public function setConfirmationPrice($confirmationPrice)
    {
        $this->confirmationPrice = $confirmationPrice;
    }

    /**
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    /**
     * @param string $notifyUrl
     */
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }
}
