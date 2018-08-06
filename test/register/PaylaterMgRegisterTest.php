<?php

namespace DigitalOrigin\Test\Register;

use DigitalOrigin\Test\Common\AbstractMg21Selenium;

/**
 * Class PaylaterMgInstallTest
 * @package DigitalOrigin\Test\Register
 *
 * @group magento-register
 */
class PaylaterMgRegisterTest extends AbstractMg21Selenium
{
    /**
     * @require configurePaylater
     *
     * @throws \Exception
     */
    public function testRegisterAndLogin()
    {
        $this->createAccount();
        $this->logoutFromFrontend();
        $this->loginToFrontend();
        $this->logoutFromFrontend();
        $this->quit();
    }
}
