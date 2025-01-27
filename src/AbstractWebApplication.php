<?php

/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application;

use Joomla\Application\Event\ApplicationErrorEvent;
use Joomla\Application\Exception\UnableToWriteBody;
use Joomla\Application\Web\WebClient;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Uri\Uri;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;

/**
 * Base class for a Joomla! Web application.
 *
 * @since  1.0.0
 *
 * @property-read  Input $input  The application input object
 */
abstract class AbstractWebApplication extends AbstractApplication implements WebApplicationInterface
{
    /**
     * The application input object.
     *
     * @var    Input
     * @since  1.0.0
     */
    protected $input;

    /**
     * Character encoding string.
     *
     * @var    string
     * @since  1.0.0
     */
    public $charSet = 'utf-8';

    /**
     * Response mime type.
     *
     * @var    string
     * @since  1.0.0
     */
    public $mimeType = 'text/html';

    /**
     * HTTP protocol version.
     *
     * @var    string
     * @since  1.9.0
     */
    public $httpVersion = '1.1';

    /**
     * The body modified date for response headers.
     *
     * @var    \DateTime
     * @since  1.0.0
     */
    public $modifiedDate;

    /**
     * The application client object.
     *
     * @var    Web\WebClient
     * @since  1.0.0
     */
    public $client;

    /**
     * The application response object.
     *
     * @var    ResponseInterface
     * @since  1.0.0
     */
    protected $response;

    /**
     * Is caching enabled?
     *
     * @var    boolean
     * @since  2.0.0
     */
    private $cacheable = false;

    /**
     * A map of integer HTTP response codes to the full HTTP Status for the headers.
     *
     * @var    array
     * @since  1.6.0
     * @link   https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    private $responseMap = [
        100 => 'HTTP/{version} 100 Continue',
        101 => 'HTTP/{version} 101 Switching Protocols',
        102 => 'HTTP/{version} 102 Processing',
        200 => 'HTTP/{version} 200 OK',
        201 => 'HTTP/{version} 201 Created',
        202 => 'HTTP/{version} 202 Accepted',
        203 => 'HTTP/{version} 203 Non-Authoritative Information',
        204 => 'HTTP/{version} 204 No Content',
        205 => 'HTTP/{version} 205 Reset Content',
        206 => 'HTTP/{version} 206 Partial Content',
        207 => 'HTTP/{version} 207 Multi-Status',
        208 => 'HTTP/{version} 208 Already Reported',
        226 => 'HTTP/{version} 226 IM Used',
        300 => 'HTTP/{version} 300 Multiple Choices',
        301 => 'HTTP/{version} 301 Moved Permanently',
        302 => 'HTTP/{version} 302 Found',
        303 => 'HTTP/{version} 303 See other',
        304 => 'HTTP/{version} 304 Not Modified',
        305 => 'HTTP/{version} 305 Use Proxy',
        306 => 'HTTP/{version} 306 (Unused)',
        307 => 'HTTP/{version} 307 Temporary Redirect',
        308 => 'HTTP/{version} 308 Permanent Redirect',
        400 => 'HTTP/{version} 400 Bad Request',
        401 => 'HTTP/{version} 401 Unauthorized',
        402 => 'HTTP/{version} 402 Payment Required',
        403 => 'HTTP/{version} 403 Forbidden',
        404 => 'HTTP/{version} 404 Not Found',
        405 => 'HTTP/{version} 405 Method Not Allowed',
        406 => 'HTTP/{version} 406 Not Acceptable',
        407 => 'HTTP/{version} 407 Proxy Authentication Required',
        408 => 'HTTP/{version} 408 Request Timeout',
        409 => 'HTTP/{version} 409 Conflict',
        410 => 'HTTP/{version} 410 Gone',
        411 => 'HTTP/{version} 411 Length Required',
        412 => 'HTTP/{version} 412 Precondition Failed',
        413 => 'HTTP/{version} 413 Payload Too Large',
        414 => 'HTTP/{version} 414 URI Too Long',
        415 => 'HTTP/{version} 415 Unsupported Media Type',
        416 => 'HTTP/{version} 416 Range Not Satisfiable',
        417 => 'HTTP/{version} 417 Expectation Failed',
        418 => 'HTTP/{version} 418 I\'m a teapot',
        421 => 'HTTP/{version} 421 Misdirected Request',
        422 => 'HTTP/{version} 422 Unprocessable Entity',
        423 => 'HTTP/{version} 423 Locked',
        424 => 'HTTP/{version} 424 Failed Dependency',
        426 => 'HTTP/{version} 426 Upgrade Required',
        428 => 'HTTP/{version} 428 Precondition Required',
        429 => 'HTTP/{version} 429 Too Many Requests',
        431 => 'HTTP/{version} 431 Request Header Fields Too Large',
        451 => 'HTTP/{version} 451 Unavailable For Legal Reasons',
        500 => 'HTTP/{version} 500 Internal Server Error',
        501 => 'HTTP/{version} 501 Not Implemented',
        502 => 'HTTP/{version} 502 Bad Gateway',
        503 => 'HTTP/{version} 503 Service Unavailable',
        504 => 'HTTP/{version} 504 Gateway Timeout',
        505 => 'HTTP/{version} 505 HTTP Version Not Supported',
        506 => 'HTTP/{version} 506 Variant Also Negotiates',
        507 => 'HTTP/{version} 507 Insufficient Storage',
        508 => 'HTTP/{version} 508 Loop Detected',
        510 => 'HTTP/{version} 510 Not Extended',
        511 => 'HTTP/{version} 511 Network Authentication Required',
    ];

    /**
     * Class constructor.
     *
     * @param  Input|null              $input     An optional argument to provide dependency
     *                                            injection for the application's input object.  If
     *                                            the argument is an Input object that object will
     *                                            become the application's input object, otherwise a
     *                                            default input object is created.
     * @param  Registry|null           $config    An optional argument to provide dependency
     *                                            injection for the application's config object.  If
     *                                            the argument is a Registry object that object will
     *                                            become the application's config object, otherwise a
     *                                            default config object is created.
     * @param  WebClient|null          $client    An optional argument to provide dependency
     *                                            injection for the application's client object.  If
     *                                            the argument is a Web\WebClient object that object
     *                                            will become the application's client object,
     *                                            otherwise a default client object is created.
     * @param  ResponseInterface|null  $response  An optional argument to provide dependency
     *                                            injection for the application's response object.
     *                                            If the argument is a ResponseInterface object that
     *                                            object will become the application's response
     *                                            object, otherwise a default response object is
     *                                            created.
     *
     * @since   1.0.0
     */
    public function __construct(
        Input $input = null,
        Registry $config = null,
        WebClient $client = null,
        ResponseInterface $response = null
    ) {
        $this->input  = $input ?: new Input();
        $this->client = $client ?: new WebClient();

        // Setup the response object.
        if (!$response) {
            $response = new Response();
        }

        $this->setResponse($response);

        // Call the constructor as late as possible (it runs `initialise`).
        parent::__construct($config);

        // Set the system URIs.
        $this->loadSystemUris();
    }

    /**
     * Magic method to access properties of the application.
     *
     * @param  string  $name  The name of the property.
     *
     * @return Input|null A value if the property name is valid, null otherwise.
     *
     * @since       2.0.0
     * @deprecated  3.0  This is a B/C proxy for deprecated read accesses
     */
    public function __get($name)
    {
        switch ($name) {
            case 'input':
                \trigger_deprecation(
                    'joomla/application',
                    '2.0.0',
                    'Accessing the input property of %s is deprecated, use the %s::getInput() method instead.',
                    self::class,
                    self::class
                );

                return $this->getInput();

            default:
                $trace = \debug_backtrace();
                \trigger_error(
                    \sprintf(
                        'Undefined property via __get(): %1$s in %2$s on line %3$s',
                        $name,
                        $trace[0]['file'],
                        $trace[0]['line']
                    ),
                    E_USER_NOTICE
                );

                return null;
        }
    }

    /**
     * Execute the application.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function execute()
    {
        try {
            $this->dispatchEvent(ApplicationEvents::BEFORE_EXECUTE);

            // Perform application routines.
            $this->doExecute();

            $this->dispatchEvent(ApplicationEvents::AFTER_EXECUTE);

            // If gzip compression is enabled in configuration and the server is compliant, compress the output.
            if (
                $this->get('gzip')
                && !\ini_get('zlib.output_compression')
                && (\ini_get('output_handler') != 'ob_gzhandler')
            ) {
                $this->compress();
            }
        } catch (\Throwable $throwable) {
            $this->dispatchEvent(ApplicationEvents::ERROR, new ApplicationErrorEvent($throwable, $this));
        }

        $this->dispatchEvent(ApplicationEvents::BEFORE_RESPOND);

        // Send the application response.
        $this->respond();

        $this->dispatchEvent(ApplicationEvents::AFTER_RESPOND);
    }

    /**
     * Checks the accept encoding of the browser and compresses the data before sending it to the client if possible.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function compress()
    {
        // Supported compression encodings.
        $supported = [
            'x-gzip'  => 'gz',
            'gzip'    => 'gz',
            'deflate' => 'deflate',
        ];

        // Get the supported encoding.
        $encodings = \array_intersect($this->client->encodings, \array_keys($supported));

        // If no supported encoding is detected do nothing and return.
        if (empty($encodings)) {
            return;
        }

        // Verify that headers have not yet been sent, and that our connection is still alive.
        if ($this->checkHeadersSent() || !$this->checkConnectionAlive()) {
            return;
        }

        // Iterate through the encodings and attempt to compress the data using any found supported encodings.
        foreach ($encodings as $encoding) {
            if (($supported[$encoding] == 'gz') || ($supported[$encoding] == 'deflate')) {
                // Verify that the server supports gzip compression before we attempt to gzip encode the data.
                // @codeCoverageIgnoreStart
                if (!\extension_loaded('zlib') || \ini_get('zlib.output_compression')) {
                    continue;
                }

                // @codeCoverageIgnoreEnd

                // Attempt to gzip encode the data with an optimal level 4.
                $data   = $this->getBody();
                $gzdata = \gzencode($data, 4, ($supported[$encoding] == 'gz') ? FORCE_GZIP : FORCE_DEFLATE);

                // If there was a problem encoding the data just try the next encoding scheme.
                // @codeCoverageIgnoreStart
                if ($gzdata === false) {
                    continue;
                }

                // @codeCoverageIgnoreEnd

                // Set the encoding headers.
                $this->setHeader('Content-Encoding', $encoding);
                $this->setHeader('Vary', 'Accept-Encoding');

                // Replace the output with the encoded data.
                $this->setBody($gzdata);

                // Compression complete, let's break out of the loop.
                break;
            }
        }
    }

    /**
     * Method to send the application response to the client.  All headers will be sent prior to the main application
     * output data.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function respond()
    {
        // Send the content-type header.
        if (!$this->getResponse()->hasHeader('Content-Type')) {
            $this->setHeader('Content-Type', $this->mimeType . '; charset=' . $this->charSet);
        }

        // If the response is set to uncachable, et some appropriate headers so browsers don't cache the response.
        if (!$this->allowCache()) {
            // Expires in the past.
            $this->setHeader('Expires', 'Wed, 17 Aug 2005 00:00:00 GMT', true);

            // Always modified.
            $this->setHeader('Last-Modified', \gmdate('D, d M Y H:i:s') . ' GMT', true);
            $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);

            // HTTP 1.0
            $this->setHeader('Pragma', 'no-cache');
        } else {
            // Expires.
            if (!$this->getResponse()->hasHeader('Expires')) {
                $this->setHeader('Expires', \gmdate('D, d M Y H:i:s', \time() + 900) . ' GMT');
            }

            // Last modified.
            if (!$this->getResponse()->hasHeader('Last-Modified') && $this->modifiedDate instanceof \DateTime) {
                $this->modifiedDate->setTimezone(new \DateTimeZone('UTC'));
                $this->setHeader('Last-Modified', $this->modifiedDate->format('D, d M Y H:i:s') . ' GMT');
            }
        }

        // Make sure there is a status header already otherwise generate it from the response
        if (!$this->getResponse()->hasHeader('Status')) {
            $this->setHeader('Status', (string) $this->getResponse()->getStatusCode());
        }

        $this->sendHeaders();

        echo $this->getBody();
    }

    /**
     * Method to get the application input object.
     *
     * @return  Input
     *
     * @since   2.0.0
     */
    public function getInput(): Input
    {
        return $this->input;
    }

    /**
     * Redirect to another URL.
     *
     * If the headers have not been sent the redirect will be accomplished using a "301 Moved Permanently" or "303 See
     * Other" code in the header pointing to the new location. If the headers have already been sent this will be
     * accomplished using a JavaScript statement.
     *
     * @param  string           $url     The URL to redirect to. Can only be http/https URL
     * @param  integer|boolean  $status  The HTTP status code to be provided. 303 is assumed by default.
     *
     * @return  void
     *
     * @throws  \InvalidArgumentException
     * @since   1.0.0
     */
    public function redirect($url, $status = 303)
    {
        // Check for relative internal links.
        if (\preg_match('#^index\.php#', $url)) {
            $url = $this->get('uri.base.full') . $url;
        }

        // Perform a basic sanity check to make sure we don't have any CRLF garbage.
        $url = \preg_split("/[\r\n]/", $url);
        $url = $url[0];

        /*
         * Here we need to check and see if the URL is relative or absolute.  Essentially, do we need to
         * prepend the URL with our base URL for a proper redirect.  The rudimentary way we are looking
         * at this is to simply check whether or not the URL string has a valid scheme or not.
         */
        if (!\preg_match('#^[a-z]+://#i', $url)) {
            // Get a Uri instance for the requested URI.
            $uri = new Uri($this->get('uri.request'));

            // Get a base URL to prepend from the requested URI.
            $prefix = $uri->toString(['scheme', 'user', 'pass', 'host', 'port']);

            // We just need the prefix since we have a path relative to the root.
            if ($url[0] == '/') {
                $url = $prefix . $url;
            } else {
                // It's relative to where we are now, so lets add that.
                $parts = \explode('/', $uri->toString(['path']));
                \array_pop($parts);
                $path = \implode('/', $parts) . '/';
                $url  = $prefix . $path . $url;
            }
        }

        if ($this->checkHeadersSent()) {
            // If the headers have already been sent we need to send the redirect statement via JavaScript.
            echo '<script>document.location.href=' . \json_encode($url) . ";</script>\n";
        } elseif (($this->client->engine == WebClient::TRIDENT) && !static::isAscii($url)) {
            // We have to use a JavaScript redirect here because MSIE doesn't play nice with UTF-8 URLs.
            $html = '<html><head>';
            $html .= '<meta http-equiv="content-type" content="text/html; charset=' . $this->charSet . '" />';
            $html .= '<script>document.location.href=' . \json_encode($url) . ';</script>';
            $html .= '</head><body></body></html>';

            echo $html;
        } else {
            // Check if we have a boolean for the status variable for compatability with v1 of the framework
            // @deprecated 3.0
            if (\is_bool($status)) {
                \trigger_deprecation(
                    'joomla/application',
                    '2.0.0',
                    'Passing a boolean value for the $status argument in %s() is deprecated,'
                    . ' an integer should be passed instead.',
                    __METHOD__
                );

                $status = $status ? 301 : 303;
            }

            if (!\is_int($status) && !$this->isRedirectState($status)) {
                throw new \InvalidArgumentException('You have not supplied a valid HTTP status code');
            }

            // All other cases use the more efficient HTTP header for redirection.
            $this->setHeader('Status', (string) $status, true);
            $this->setHeader('Location', $url, true);
        }

        $this->dispatchEvent(ApplicationEvents::BEFORE_RESPOND);

        // Set appropriate headers
        $this->respond();

        $this->dispatchEvent(ApplicationEvents::AFTER_RESPOND);

        // Close the application after the redirect.
        $this->close();
    }

    /**
     * Set/get cachable state for the response.
     *
     * If $allow is set, sets the cachable state of the response.  Always returns the current state.
     *
     * @param  boolean  $allow  True to allow browser caching.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function allowCache($allow = null)
    {
        if ($allow !== null) {
            $this->cacheable = (bool) $allow;
        }

        return $this->cacheable;
    }

    /**
     * Method to set a response header.
     *
     * If the replace flag is set then all headers with the given name will be replaced by the new one.
     * The headers are stored in an internal array to be sent when the site is sent to the browser.
     *
     * @param  string   $name     The name of the header to set.
     * @param  string   $value    The value of the header to set.
     * @param  boolean  $replace  True to replace any headers with the same name.
     *
     * @return  $this
     *
     * @since   1.0.0
     */
    public function setHeader($name, $value, $replace = false)
    {
        // Sanitize the input values.
        $name     = (string) $name;
        $value    = (string) $value;
        $response = $this->getResponse();

        // If the replace flag is set, unset all known headers with the given name.
        if ($replace && $response->hasHeader($name)) {
            $response = $response->withoutHeader($name);
        }

        // Add the header to the internal array.
        $this->setResponse($response->withAddedHeader($name, $value));

        return $this;
    }

    /**
     * Method to get the array of response headers to be sent when the response is sent to the client.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getHeaders()
    {
        $return = [];

        foreach ($this->getResponse()->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $return[] = ['name' => $name, 'value' => $value];
            }
        }

        return $return;
    }

    /**
     * Method to clear any set response headers.
     *
     * @return  $this
     *
     * @since   1.0.0
     */
    public function clearHeaders()
    {
        $response = $this->getResponse();

        foreach ($response->getHeaders() as $name => $values) {
            $response = $response->withoutHeader($name);
        }

        $this->setResponse($response);

        return $this;
    }

    /**
     * Send the response headers.
     *
     * @return  $this
     *
     * @since   1.0.0
     */
    public function sendHeaders()
    {
        if (!$this->checkHeadersSent()) {
            foreach ($this->getHeaders() as $header) {
                if (\strtolower($header['name']) == 'status') {
                    // 'status' headers indicate an HTTP status, and need to be handled slightly differently
                    $status = $this->getHttpStatusValue($header['value']);

                    $this->header($status, true, (int) $header['value']);
                } else {
                    $this->header($header['name'] . ': ' . $header['value']);
                }
            }
        }

        return $this;
    }

    /**
     * Set body content.  If body content already defined, this will replace it.
     *
     * @param  string  $content  The content to set as the response body.
     *
     * @return  $this
     *
     * @since   1.0.0
     */
    public function setBody($content)
    {
        $stream = new Stream('php://memory', 'rw');
        $stream->write((string) $content);
        $this->setResponse($this->getResponse()->withBody($stream));

        return $this;
    }

    /**
     * Prepend content to the body content
     *
     * @param  string  $content  The content to prepend to the response body.
     *
     * @return  $this
     *
     * @since   1.0.0
     */
    public function prependBody($content)
    {
        $currentBody = $this->getResponse()->getBody();

        if (!$currentBody->isReadable()) {
            throw new UnableToWriteBody();
        }

        $stream = new Stream('php://memory', 'rw');
        $stream->write((string) $content . (string) $currentBody);
        $this->setResponse($this->getResponse()->withBody($stream));

        return $this;
    }

    /**
     * Append content to the body content
     *
     * @param  string  $content  The content to append to the response body.
     *
     * @return  $this
     *
     * @since   1.0.0
     */
    public function appendBody($content)
    {
        $currentStream = $this->getResponse()->getBody();

        if ($currentStream->isWritable()) {
            $currentStream->write((string) $content);
            $this->setResponse($this->getResponse()->withBody($currentStream));
        } elseif ($currentStream->isReadable()) {
            $stream = new Stream('php://memory', 'rw');
            $stream->write((string) $currentStream . (string) $content);
            $this->setResponse($this->getResponse()->withBody($stream));
        } else {
            throw new UnableToWriteBody();
        }

        return $this;
    }

    /**
     * Return the body content
     *
     * @return  string  The response body as a string.
     *
     * @since   1.0.0
     */
    public function getBody()
    {
        return (string) $this->getResponse()->getBody();
    }

    /**
     * Get the PSR-7 Response Object.
     *
     * @return  ResponseInterface
     *
     * @since   2.0.0
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Check if a given value can be successfully mapped to a valid http status value
     *
     * @param  string|int  $value  The given status as int or string
     *
     * @return  string
     *
     * @since   1.8.0
     */
    protected function getHttpStatusValue($value)
    {
        $code = (int) $value;

        if (\array_key_exists($code, $this->responseMap)) {
            $value = $this->responseMap[$code];
        } else {
            $value = 'HTTP/{version} ' . $code;
        }

        return \str_replace('{version}', $this->httpVersion, $value);
    }

    /**
     * Check if the value is a valid HTTP status code
     *
     * @param  integer  $code  The potential status code
     *
     * @return  boolean
     *
     * @since   1.8.1
     */
    public function isValidHttpStatus($code)
    {
        return \array_key_exists($code, $this->responseMap);
    }

    /**
     * Method to check the current client connection status to ensure that it is alive.  We are
     * wrapping this to isolate the \connection_status() function from our code base for testing reasons.
     *
     * @return  boolean  True if the connection is valid and normal.
     *
     * @codeCoverageIgnore
     * @see     \connection_status()
     * @since   1.0.0
     */
    protected function checkConnectionAlive()
    {
        return \connection_status() === CONNECTION_NORMAL;
    }

    /**
     * Method to check to see if headers have already been sent.
     *
     * @return  boolean  True if the headers have already been sent.
     *
     * @codeCoverageIgnore
     * @see     \headers_sent()
     * @since   1.0.0
     */
    protected function checkHeadersSent()
    {
        return \headers_sent();
    }

    /**
     * Method to detect the requested URI from server environment variables.
     *
     * @return  string  The requested URI
     *
     * @since   1.0.0
     */
    protected function detectRequestUri()
    {
        // First we need to detect the URI scheme.
        $scheme = $this->isSslConnection() ? 'https://' : 'http://';

        /*
         * There are some differences in the way that Apache and IIS populate server environment variables.  To
         * properly detect the requested URI we need to adjust our algorithm based on whether or not we are getting
         * information from Apache or IIS.
         */

        $phpSelf    = $this->input->server->getString('PHP_SELF', '');
        $requestUri = $this->input->server->getString('REQUEST_URI', '');

        $uri = $scheme . $this->input->server->getString('HTTP_HOST');

        if (!empty($phpSelf) && !empty($requestUri)) {
            // If PHP_SELF and REQUEST_URI are both populated then we will assume "Apache Mode".
            // The URI is built from the HTTP_HOST and REQUEST_URI environment variables in an Apache environment.
            $uri .= $requestUri;
        } else {
            // If not in "Apache Mode" we will assume that we are in an IIS environment and proceed.
            // IIS uses the SCRIPT_NAME variable instead of a REQUEST_URI variable... thanks, MS
            $uri       .= $this->input->server->getString('SCRIPT_NAME');
            $queryHost = $this->input->server->getString('QUERY_STRING', '');

            // If the QUERY_STRING variable exists append it to the URI string.
            if (!empty($queryHost)) {
                $uri .= '?' . $queryHost;
            }
        }

        // Extra cleanup to remove invalid chars in the URL to prevent injections through the Host header
        $uri = str_replace(array("'", '"', '<', '>'), array('%27', '%22', '%3C', '%3E'), $uri);

        return \trim($uri);
    }

    /**
     * Method to send a header to the client.
     *
     * @param  string   $string    The header string.
     * @param  boolean  $replace   The optional replace parameter indicates whether the header should replace a
     *                             previous similar header, or add a second header of the same type.
     * @param  integer  $code      Forces the HTTP response code to the specified value. Note that this parameter only
     *                             has an effect if the string is not empty.
     *
     * @return  void
     *
     * @codeCoverageIgnore
     * @see     \header()
     * @since   1.0.0
     */
    protected function header($string, $replace = true, $code = null)
    {
        if ($code === null) {
            $code = 0;
        }

        \header(\str_replace(\chr(0), '', $string), $replace, $code);
    }

    /**
     * Set the PSR-7 Response Object.
     *
     * @param  ResponseInterface  $response  The response object
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * Checks if a state is a redirect state
     *
     * @param  integer  $state  The HTTP status code.
     *
     * @return  boolean
     *
     * @since   1.8.0
     */
    protected function isRedirectState($state)
    {
        $state = (int) $state;

        return $state > 299 && $state < 400 && \array_key_exists($state, $this->responseMap);
    }

    /**
     * Determine if we are using a secure (SSL) connection.
     *
     * @return  boolean  True if using SSL, false if not.
     *
     * @since   1.0.0
     */
    public function isSslConnection()
    {
        $serverSSLVar = $this->input->server->getString('HTTPS', '');

        if (!empty($serverSSLVar) && \strtolower($serverSSLVar) !== 'off') {
            return true;
        }

        $serverForwarderProtoVar = $this->input->server->getString('HTTP_X_FORWARDED_PROTO', '');

        return !empty($serverForwarderProtoVar) && \strtolower($serverForwarderProtoVar) === 'https';
    }

    /**
     * Method to load the system URI strings for the application.
     *
     * @param  string  $requestUri  An optional request URI to use instead of detecting one from the server environment
     *                              variables.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function loadSystemUris($requestUri = null)
    {
        // Set the request URI.
        if (!empty($requestUri)) {
            $this->set('uri.request', $requestUri);
        } else {
            $this->set('uri.request', $this->detectRequestUri());
        }

        // Check to see if an explicit base URI has been set.
        $siteUri = \trim($this->get('site_uri', ''));

        if ($siteUri !== '') {
            $uri  = new Uri($siteUri);
            $path = $uri->toString(['path']);
        } else {
            // No explicit base URI was set so we need to detect it. Start with the requested URI.
            $uri = new Uri($this->get('uri.request'));

            $requestUri = $this->input->server->getString('REQUEST_URI', '');

            // If we are working from a CGI SAPI with the 'cgi.fix_pathinfo' directive disabled we use PHP_SELF.
            if (\strpos(PHP_SAPI, 'cgi') !== false && !\ini_get('cgi.fix_pathinfo') && !empty($requestUri)) {
                // We aren't expecting PATH_INFO within PHP_SELF so this should work.
                $path = \dirname($this->input->server->getString('PHP_SELF', ''));
            } else {
                // Pretty much everything else should be handled with SCRIPT_NAME.
                $path = \dirname($this->input->server->getString('SCRIPT_NAME', ''));
            }
        }

        // Get the host from the URI.
        $host = $uri->toString(['scheme', 'user', 'pass', 'host', 'port']);

        // Check if the path includes "index.php".
        if (\strpos($path, 'index.php') !== false) {
            // Remove the index.php portion of the path.
            $path = \substr_replace($path, '', \strpos($path, 'index.php'), 9);
        }

        $path = \rtrim($path, '/\\');

        // Set the base URI both as just a path and as the full URI.
        $this->set('uri.base.full', $host . $path . '/');
        $this->set('uri.base.host', $host);
        $this->set('uri.base.path', $path . '/');

        // Set the extended (non-base) part of the request URI as the route.
        if (\stripos($this->get('uri.request'), $this->get('uri.base.full')) === 0) {
            $this->set(
                'uri.route',
                \substr_replace($this->get('uri.request'), '', 0, \strlen($this->get('uri.base.full')))
            );
        }

        // Get an explicitly set media URI is present.
        $mediaURI = \trim($this->get('media_uri', ''));

        if ($mediaURI !== '') {
            if (\strpos($mediaURI, '://') !== false) {
                $this->set('uri.media.full', $mediaURI);
            } else {
                // Normalise slashes.
                $mediaURI = \trim($mediaURI, '/\\');
                $mediaURI = !empty($mediaURI) ? '/' . $mediaURI . '/' : '/';
                $this->set('uri.media.full', $this->get('uri.base.host') . $mediaURI);
            }
            $this->set('uri.media.path', $mediaURI);
        } else {
            // No explicit media URI was set, build it dynamically from the base uri.
            $this->set('uri.media.full', $this->get('uri.base.full') . 'media/');
            $this->set('uri.media.path', $this->get('uri.base.path') . 'media/');
        }
    }

    /**
     * Tests whether a string contains only 7bit ASCII bytes.
     *
     * You might use this to conditionally check whether a string
     * needs handling as UTF-8 or not, potentially offering performance
     * benefits by using the native PHP equivalent if it's just ASCII e.g.;
     *
     * @param  string  $str  The string to test.
     *
     * @return  boolean  True if the string is all ASCII
     *
     * @since   1.4.0
     */
    public static function isAscii($str)
    {
        // Search for any bytes which are outside the ASCII range...
        return \preg_match('/[^\x00-\x7F]/', $str) !== 1;
    }
}
