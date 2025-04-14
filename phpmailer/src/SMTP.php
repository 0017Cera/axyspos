<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer RFC821 SMTP email transport class.
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
 * PHPMailer RFC821 SMTP email transport class.
 * Implements RFC 821 SMTP commands and provides some utility methods for sending mail to an SMTP server.
 *
 * @package PHPMailer
 * @author Chris Ryan
 * @author Marcus Bointon <phpmailer@synchromedia.co.uk>
 */
class SMTP
{
    /**
     * The PHPMailer SMTP Version number.
     *
     * @var string
     */
    const VERSION = '6.8.1';

    /**
     * SMTP line break constant.
     *
     * @var string
     */
    const LE = "\r\n";

    /**
     * The SMTP port to use if one is not specified.
     *
     * @var int
     */
    const DEFAULT_PORT = 25;

    /**
     * The maximum line length allowed by RFC 2822 section 2.1.1.
     *
     * @var int
     */
    const MAX_LINE_LENGTH = 998;

    /**
     * The maximum line length allowed by RFC 821 section 4.5.2.
     *
     * @var int
     */
    const MAX_REPLY_LENGTH = 512;

    /**
     * Debug level for no output.
     *
     * @var int
     */
    const DEBUG_OFF = 0;

    /**
     * Debug level to show client -> server messages.
     *
     * @var int
     */
    const DEBUG_CLIENT = 1;

    /**
     * Debug level to show client -> server and server -> client messages.
     *
     * @var int
     */
    const DEBUG_SERVER = 2;

    /**
     * Debug level to show connection status, client -> server and server -> client messages.
     *
     * @var int
     */
    const DEBUG_CONNECTION = 3;

    /**
     * Debug level to show all messages.
     *
     * @var int
     */
    const DEBUG_LOWLEVEL = 4;

    /**
     * The PHPMailer instance.
     *
     * @var PHPMailer
     */
    protected $mailer;

    /**
     * The hostname to connect to.
     *
     * @var string
     */
    protected $host;

    /**
     * The port to connect to.
     *
     * @var int
     */
    protected $port;

    /**
     * The timeout value for connection, in seconds.
     *
     * @var int
     */
    protected $timeout;

    /**
     * The socket for the server connection.
     *
     * @var resource
     */
    protected $smtp_conn;

    /**
     * The error information, if any.
     *
     * @var array
     */
    protected $error = [
        'error' => '',
        'detail' => '',
        'smtp_code' => '',
        'smtp_code_ex' => '',
    ];

    /**
     * The debug output mode.
     *
     * @var int
     */
    protected $do_debug = self::DEBUG_OFF;

    /**
     * Constructor.
     *
     * @param PHPMailer $mailer The PHPMailer instance
     */
    public function __construct(PHPMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Connect to an SMTP server.
     *
     * @param string $host    The hostname to connect to
     * @param int    $port    The port to connect to
     * @param int    $timeout The timeout value for connection, in seconds
     *
     * @return bool
     */
    public function connect($host, $port = null, $timeout = 30)
    {
        $this->error = [
            'error' => '',
            'detail' => '',
            'smtp_code' => '',
            'smtp_code_ex' => '',
        ];

        // Make sure we are __not__ connected
        if ($this->connected()) {
            // Already connected, generate an error
            $this->error = ['error' => 'Already connected to a server'];
            return false;
        }

        if (empty($port)) {
            $port = self::DEFAULT_PORT;
        }

        // Connect to the SMTP server
        $errno = 0;
        $errstr = '';
        $socket_context = stream_context_create();
        $this->smtp_conn = @stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $socket_context
        );

        // Verify we connected properly
        if (empty($this->smtp_conn)) {
            $this->error = [
                'error' => 'Failed to connect to server',
                'detail' => $errstr,
                'smtp_code' => $errno,
                'smtp_code_ex' => '',
            ];
            return false;
        }

        if (substr(PHP_OS, 0, 3) != 'WIN') {
            $max = ini_get('max_execution_time');
            if (0 != $max and $timeout > $max) {
                @set_time_limit($timeout);
            }
            stream_set_timeout($this->smtp_conn, $timeout, 0);
        }

        // Get any announcement
        $announce = $this->get_lines();

        if ($this->do_debug >= self::DEBUG_SERVER) {
            $this->edebug('SERVER -> CLIENT: ' . $announce);
        }

        return true;
    }

    /**
     * Initiate a TLS (encrypted) session.
     *
     * @return bool
     */
    public function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }

        // Begin encrypted connection
        if (!stream_socket_enable_crypto(
            $this->smtp_conn,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        )) {
            $this->error = ['error' => 'StartTLS failed'];
            return false;
        }

        return true;
    }

    /**
     * Perform SMTP authentication.
     *
     * @param string $username The username to authenticate with
     * @param string $password The password to authenticate with
     *
     * @return bool
     */
    public function authenticate($username, $password)
    {
        if (!$this->sendCommand('AUTH LOGIN', 'AUTH LOGIN', 334)) {
            return false;
        }

        if (!$this->sendCommand(base64_encode($username), 'Username', 334)) {
            return false;
        }

        if (!$this->sendCommand(base64_encode($password), 'Password', 235)) {
            return false;
        }

        return true;
    }

    /**
     * Send an SMTP command and check its return code.
     *
     * @param string $command The command to send
     * @param string $code    The expected return code
     * @param int    $code2   An alternative return code
     *
     * @return bool
     */
    protected function sendCommand($command, $code = '250', $code2 = '')
    {
        if ($this->do_debug >= self::DEBUG_CLIENT) {
            $this->edebug('CLIENT -> SERVER: ' . $command);
        }

        $result = true;
        $errstr = '';
        $errno = 0;

        if (fwrite($this->smtp_conn, $command . self::LE) === false) {
            $errstr = 'Failed to write to stream';
            $errno = 1;
            $result = false;
        }

        if ($result) {
            $reply = $this->get_lines();

            if ($this->do_debug >= self::DEBUG_SERVER) {
                $this->edebug('SERVER -> CLIENT: ' . $reply);
            }

            if (substr($reply, 0, 3) != $code and ('' == $code2 or substr($reply, 0, 3) != $code2)) {
                $this->error = [
                    'error' => $command . ' failed',
                    'detail' => $reply,
                    'smtp_code' => substr($reply, 0, 3),
                    'smtp_code_ex' => '',
                ];
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Get the lines from the server.
     *
     * @return string
     */
    protected function get_lines()
    {
        $data = '';
        while (substr($data, 3, 1) != ' ') {
            if (!($str = fgets($this->smtp_conn, 515))) {
                $this->error = [
                    'error' => 'Connection died',
                    'detail' => $data,
                    'smtp_code' => '',
                    'smtp_code_ex' => '',
                ];
                return '';
            }
            $data .= $str;
            if ($this->do_debug >= self::DEBUG_LOWLEVEL) {
                $this->edebug('SMTP -> ' . str_replace("\r\n", "\n", $str));
            }
        }
        return $data;
    }

    /**
     * Close the socket and clean up the state of the class.
     */
    public function close()
    {
        $this->error = [
            'error' => '',
            'detail' => '',
            'smtp_code' => '',
            'smtp_code_ex' => '',
        ];
        if (null != $this->smtp_conn) {
            // Close the connection and cleanup
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }

    /**
     * Check if we are connected to an SMTP server.
     *
     * @return bool
     */
    public function connected()
    {
        if (!empty($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                // The socket is valid but we are not connected
                if ($this->do_debug >= self::DEBUG_LOWLEVEL) {
                    $this->edebug('SMTP NOTICE: EOF caught while checking ' . $this->host);
                }
                $this->close();
                return false;
            }
            return true; // everything looks good
        }
        return false;
    }

    /**
     * Get the current error information.
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set the debug output mode.
     *
     * @param int $level The debug level
     */
    public function setDebugLevel($level = 0)
    {
        $this->do_debug = $level;
    }

    /**
     * Output debugging information via a user-selected method.
     *
     * @param string $str Debug string to output
     */
    protected function edebug($str)
    {
        switch ($this->do_debug) {
            case self::DEBUG_OFF:
                return;
            case self::DEBUG_CLIENT:
            case self::DEBUG_SERVER:
            case self::DEBUG_CONNECTION:
            case self::DEBUG_LOWLEVEL:
                echo date('Y-m-d H:i:s') . ' ' . $str . "\n";
                break;
        }
    }
} 