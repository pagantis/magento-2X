<?php

namespace DigitalOrigin\Pmt\Test;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\TestCase;

/**
 * Class PaylaterMagentoTest
 * @package DigitalOrigin\Test
 */
abstract class PaylaterMagentoTest extends TestCase
{
    /**
     * Magento URL
     */
    const MAGENTO_URL = 'http://magento2-test.docker:8085/index.php';

    /**
     * Magento Backoffice URL
     */
    const BACKOFFICE_FOLDER = '/admin';

    /**
     * Magento Logout URL
     */
    const LOGOUT_FOLDER = '/customer/account/logout/';

    /**
     * Magento Checkout URL
     */
    const CHECKOUT_FOLDER = '/checkout/';

    /**
     * Product name
     */
    const PRODUCT_NAME = 'Fusion Backpack';

    /**
     * Product quantity after
     */
    const PRODUCT_QTY_AFTER = 3;

    /**
     * Magento checkout Title
     */
    const CHECKOUT_TITLE = 'Checkout';

    /**
     * Magento cart Title
     */
    const CART_TITLE = 'Cart';

    /**
     * Magento success title
     */
    const SUCCESS_TITLE = 'Success Page';

    /**
     * Magento order confirmation title
     */
    const ORDER_TITLE = 'Order #';

    /**
     * Pmt Order Title
     */
    const PMT_TITLE = 'Paga+Tarde';

    /**
     * Already processed
     */
    const ALREADY_TITLE = 'already processed';

    /**
     * Wrong order
     */
    const NOORDER_TITLE = 'Merchant Order Not Found';

    /**
     * Magento Logout URL
     */
    const NOTIFICATION_FOLDER = '/paylater/notify/';

    /**
     * Magento Logout URL
     */
    const NOTIFICATION_PARAMETER = 'quoteId';

    /**
     * @var array
     */
    protected $configuration = array(
        'backofficeUsername' => 'admin',
        'backofficePassword' => 'password123',
        'publicKey'          => 'tk_fd53cd467ba49022e4f8215e',
        'secretKey'          => '21e57baa97459f6a',
        'methodName'         => 'Financiación instantánea',
        'defaultSimulatorOpt'=> 6,
        'defaultMinIns'      => 3,
        'defaultMaxIns'      => 12,
        'minAmount'          => 70,
        'username'           => 'demo@prestashop.com',
        'password'           => 'Prestashop_demo',
        'firstname'          => 'John',
        'lastname'           => 'Döe Martinez',
        'email'              => null,
        'zip'                => '08023',
        'city'               => 'Barcelona',
        'street'             => 'Av Diagonal 585, planta 7',
        'phone'              => '600123123',
        'checkoutDescription'=> 'Paga hasta en 12 cómodas cuotas con Paga+Tarde'
        //'dni'                => '09422447Z',
        //'company'            => 'Digital Origin SL',
    );

    /**
     * @var RemoteWebDriver
     */
    protected $webDriver;

    /**
     * PaylaterMagentoTest constructor.
     */
    public function __construct()
    {
        $this->configuration['email'] = "john.doe+".microtime(true)."@digitalorigin.com";

        return parent::__construct();
    }

    /**
     * Configure selenium
     */
    protected function setUp()
    {
        $this->webDriver = RemoteWebDriver::create(
            'http://localhost:4444/wd/hub',
            DesiredCapabilities::chrome(),
            240000,
            240000
        );
    }

    /**
     * @param $name
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function findByName($name)
    {
        return $this->webDriver->findElement(WebDriverBy::name($name));
    }

    /**
     * @param $id
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function findById($id)
    {
        return $this->webDriver->findElement(WebDriverBy::id($id));
    }

    /**
     * @param $className
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function findByClass($className)
    {
        return $this->webDriver->findElement(WebDriverBy::className($className));
    }

    /**
     * @param $css
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function findByCss($css)
    {
        return $this->webDriver->findElement(WebDriverBy::cssSelector($css));
    }

    /**
     * @param $link
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function findByLinkText($link)
    {
        return $this->webDriver->findElement(WebDriverBy::linkText($link));
    }

    /**
     * @param $link
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function findByPartialLinkText($link)
    {
        return $this->webDriver->findElement(WebDriverBy::partialLinkText($link));
    }

    /**
     * Quit browser
     */
    protected function quit()
    {
        $this->webDriver->quit();
    }
}
