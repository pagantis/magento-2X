<?php

namespace DigitalOrigin\Test;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
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
    const MAGENTO_URL = 'http://magento2.docker:8086/index.php';

    /**
     * Magento Backoffice URL
     */
    const BACKOFFICE_FOLDER = '/admin';

    /**
     * Magento Logout URL
     */
    const LOGOUT_FOLDER = '/customer/account/logout/';

    /**
     * @var array
     */
    protected $configuration = array(
        'backofficeUsername' => 'admin',
        'backofficePassword' => 'password123',
        'publicKey'          => 'tk_fd53cd467ba49022e4f8215e',
        'secretKey'          => '21e57baa97459f6a',
        'methodName'         => 'Financiación instantánea',
        'defaultSimulatorOpt' => 6,
        'defaultMinIns' => 3,
        'defaultMaxIns' => 12,
        'username'           => 'demo@prestashop.com',
        'password'           => 'Prestashop_demo',
        'firstname'          => 'John',
        'lastname'           => 'Doe Martinez',
        'email'              => 'john.doe4@digitalorigin.com',
        //'company'            => 'Digital Origin SL',
        'zip'                => '08023',
        'city'               => 'Barcelona',
        'street'             => 'Av Diagonal 585, planta 7',
        'phone'              => '600123123',
        //'dni'                => '09422447Z',
    );

    /**
     * @var RemoteWebDriver
     */
    protected $webDriver;

    /**
     * Configure selenium
     */
    protected function setUp()
    {
        $capabilities    = array(WebDriverCapabilityType::BROWSER_NAME => 'chrome');
        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
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
