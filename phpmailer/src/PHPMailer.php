<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer - PHP email creation and transport class.
 * PHP Version 5.5.
 *
 * @package PHPMailer
 * @link https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * PHPMailer - PHP email creation and transport class.
 * @package PHPMailer
 * @author Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 */
class PHPMailer
{
    /**
     * The PHPMailer Version number.
     * @var string
     */
    const VERSION = '6.8.1';

    /**
     * Language code for PHPMailer messages.
     * @var string
     */
    public $Language = 'en';

    /**
     * The character set of the message.
     * @var string
     */
    public $CharSet = 'iso-8859-1';

    /**
     * The MIME Content-type of the message.
     * @var string
     */
    public $ContentType = 'text/plain';

    /**
     * The message encoding.
     * @var string
     */
    public $Encoding = '8bit';

    /**
     * Holds the most recent mailer error message.
     * @var string
     */
    public $ErrorInfo = '';

    /**
     * The From email address for the message.
     * @var string
     */
    public $From = '';

    /**
     * The From name of the message.
     * @var string
     */
    public $FromName = '';

    /**
     * The Sender email (Return-Path) of the message.
     * @var string
     */
    public $Sender = '';

    /**
     * The Subject of the message.
     * @var string
     */
    public $Subject = '';

    /**
     * An HTML or plain text message body.
     * @var string
     */
    public $Body = '';

    /**
     * The plain-text message body.
     * @var string
     */
    public $AltBody = '';

    /**
     * The word-wrap width of the message body.
     * @var int
     */
    public $WordWrap = 0;

    /**
     * Email priority (1 = High, 3 = Normal, 5 = low).
     * @var int
     */
    public $Priority = 3;

    /**
     * The hostname to connect to.
     * @var string
     */
    public $Host = 'localhost';

    /**
     * The port to connect to.
     * @var int
     */
    public $Port = 25;

    /**
     * The SMTP HELO of the message.
     * @var string
     */
    public $Helo = '';

    /**
     * What kind of encryption to use on the SMTP connection.
     * @var string
     */
    public $SMTPSecure = '';

    /**
     * Whether to use SMTP authentication.
     * @var bool
     */
    public $SMTPAuth = false;

    /**
     * Username to use for SMTP authentication.
     * @var string
     */
    public $Username = '';

    /**
     * Password to use for SMTP authentication.
     * @var string
     */
    public $Password = '';

    /**
     * The authentication type we're using.
     * @var string
     */
    public $AuthType = '';

    /**
     * The SMTP timeout value.
     * @var int
     */
    public $Timeout = 300;

    /**
     * The SMTP keepalive value.
     * @var bool
     */
    public $SMTPKeepAlive = false;

    /**
     * The SMTP class to use.
     * @var SMTP
     */
    public $smtp;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->smtp = new SMTP($this);
    }

    /**
     * Send the email.
     * @return bool
     */
    public function send()
    {
        try {
            if (!$this->smtp->connect($this->Host, $this->Port, $this->Timeout)) {
                throw new Exception($this->smtp->getError()['error']);
            }

            if ($this->SMTPSecure == 'tls') {
                if (!$this->smtp->startTLS()) {
                    throw new Exception($this->smtp->getError()['error']);
                }
            }

            if ($this->SMTPAuth) {
                if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                    throw new Exception($this->smtp->getError()['error']);
                }
            }

            // Send the email
            if (!$this->smtp->sendCommand('MAIL FROM:<' . $this->From . '>', 'MAIL FROM', 250)) {
                throw new Exception($this->smtp->getError()['error']);
            }

            // Add recipients
            if (!$this->smtp->sendCommand('RCPT TO:<' . $this->to . '>', 'RCPT TO', 250)) {
                throw new Exception($this->smtp->getError()['error']);
            }

            // Send the message
            if (!$this->smtp->sendCommand('DATA', 'DATA', 354)) {
                throw new Exception($this->smtp->getError()['error']);
            }

            // Send the message body
            $message = $this->createHeader() . $this->Body;
            if (!$this->smtp->sendCommand($message . "\r\n.", 'DATA', 250)) {
                throw new Exception($this->smtp->getError()['error']);
            }

            $this->smtp->close();
            return true;
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * Create the email header.
     * @return string
     */
    protected function createHeader()
    {
        $header = '';
        $header .= 'From: ' . $this->FromName . ' <' . $this->From . ">\r\n";
        $header .= 'To: ' . $this->to . "\r\n";
        $header .= 'Subject: ' . $this->Subject . "\r\n";
        $header .= 'MIME-Version: 1.0' . "\r\n";
        $header .= 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet . "\r\n";
        $header .= 'Content-Transfer-Encoding: ' . $this->Encoding . "\r\n";
        return $header;
    }
} 