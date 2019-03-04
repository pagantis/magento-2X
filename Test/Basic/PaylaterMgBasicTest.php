<?php

namespace DigitalOrigin\Pmt\Test\Basic;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Class PaylaterMgBasicTest
 * @package DigitalOrigin\Test\Basic
 *
 * @group magento-basic
 */
class PaylaterMgBasicTest extends AbstractMg21Selenium
{
    /**
     * String
     */
    const TITLE = 'Home';

    /**
     * String
     */
    const BACKOFFICE_TITLE = 'Admin';

    /**
     * testMagentoOpen
    */
    public function testPaylaterMg21BasicTest()
    {
        $this->webDriver->get($this->configuration['magentoUrl']);
        $condition = WebDriverExpectedCondition::titleContains(self::TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, $this->configuration['magentoUrl']);
        $this->quit();
    }

    /**
     * testBackofficeOpen
     */
    public function testBackofficeOpen()
    {
        $this->webDriver->get($this->configuration['magentoUrl'].self::BACKOFFICE_FOLDER);
        $condition = WebDriverExpectedCondition::titleContains(self::BACKOFFICE_TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->quit();
    }
}
