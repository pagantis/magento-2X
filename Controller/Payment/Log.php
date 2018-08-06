<?php
namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;

class Log extends Action
{
    /**
     * @var mixed
     */
    protected $config;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \DigitalOrigin\Pmt\Helper\Config $pmtConfig
    ) {
        $this->config = $pmtConfig->getConfig();
        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $secretKey = $this->getRequest()->getParam('secret');
            $privateKey = $this->config['secret_key'];
            $file = 'var/log/pmt.log';
            if (file_exists($file) && $privateKey == $secretKey) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($file).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
            } else {
                die('ERROR');
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
