<?php
namespace core;

class Mailer
{

    /**
     * @var \Swift_Mailer
     */
    private $mailer = null;
    /**
     * @var \Swift_Message
     */
    private $message = null;
    private $encoding = 'iso-2022-jp';

    function __construct($options = array())
    {
        $smtpOptions = Config::get('smtp');
        if (is_array($smtpOptions)) {
            foreach ($smtpOptions as $key => $val) {
                if (isset($options[$key]) == false) {
                    $options[$key] = $val;
                }
            }
        }

        if (isset($options['host']) == false) {
            $options['host'] = 'localhost';
        }

        if (isset($options['port']) == false) {
            $options['port'] = 25;
        }

        if (isset($options['encoding'])) {
            $this->encoding = strtolower($options['encoding']);
        }

        if ($this->encoding == 'iso-2022-jp') {
            \Swift::init(function()
            {
                \Swift_DependencyContainer::getInstance()
                    ->register('mime.qpheaderencoder')
                    ->asAliasOf('mime.base64headerencoder');
                \Swift_Preferences::getInstance()->setCharset('iso-2022-jp');
            });
        }

        $this->mailer = new \Swift_Mailer(
            new \Swift_SmtpTransport($options['host'], $options['port'])
        );

        $this->message = new \Swift_Message();
        $this->message->setCharset($this->encoding);
    }

    public static function build($options = array())
    {
        return new self($options);
    }

    public function from($from)
    {
        $this->message->setFrom($from);
        return $this;
    }

    public function bcc($bcc)
    {
        $this->message->setBcc($bcc);
        return $this;
    }

    public function subject($subject)
    {
        $this->message->setSubject($subject);
        return $this;
    }

    public function body($body)
    {
        $this->message->setBody($body);
        return $this;
    }

    public function templete($template, $params = null)
    {
        ob_start();
        include $template;
        $this->message->setBody(ob_get_contents());
        ob_end_clean();
        return $this;
    }

    public function attach($data, $name, $contentType = null)
    {
        $attach = new \Swift_Attachment();

        $attach->setBody($data);
        $attach->setFilename(mb_encode_mimeheader($name));
        if ($contentType) {
            $attach->setContentType($contentType);
        }
        $this->message->attach($attach);

        return $this;
    }

    public function send($emails)
    {
        if (!is_array($emails)) {
            $emails = array($emails);
        }

        foreach ($emails as $key => $val) {
            $matches = null;
            if (preg_match('/^(.+\.)(@.+)$/', $val, $matches)) {
                $emails[$key] = '"' . $matches[1] . '"' . $matches[2];
            } else {
                $emails[$key] = $val;
            }
        }

        $this->message->setTo($emails);

        $this->mailer->send($this->message);

        return $this;
    }

    public function disconnect()
    {
        $this->mailer->getTransport()->stop();
    }
}
