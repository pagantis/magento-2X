<?php

namespace Test\Basic;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Magento2Test;

/**
 * Class BasicTest
 * @package Test\Basic
 *
 * @group magento-basic
 */
class BasicTest extends Magento2Test
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
    public function testMagentoOpen()
    {
        $this->webDriver->get(self::MAGENTO_URL);
        $this->webDriver->wait(5, 500)->until(
            WebDriverExpectedCondition::titleContains(
                self::TITLE
            )
        );
        $this->assertContains(self::TITLE, $this->webDriver->getTitle());
        $this->quit();
    }

    /**
     * testBackofficeOpen
     */
    public function testBackofficeOpen()
    {
        $this->webDriver->get(self::MAGENTO_URL.self::BACKOFFICE_FOLDER);
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::titleContains(
                self::BACKOFFICE_TITLE
            )
        );
        $this->assertContains(self::BACKOFFICE_TITLE, $this->webDriver->getTitle());
        $this->quit();
    }
}
