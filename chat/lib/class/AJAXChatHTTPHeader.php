<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Class to manage HTTP header

/**
 * Class AJAXChatHTTPHeader.
 */
class AJAXChatHTTPHeader
{
    protected $_contentType;
    protected $_constant;
    protected $_noCache;

    /**
     * AJAXChatHTTPHeader constructor.
     *
     * @param string $encoding
     * @param null   $contentType
     * @param bool   $noCache
     */
    public function __construct($encoding = 'UTF-8', $contentType = null, $noCache = true)
    {
        if ($contentType) {
            $this->_contentType = $contentType . '; charset=' . $encoding;
            $this->_constant = true;
        } else {
            if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false)) {
                $this->_contentType = 'application/xhtml+xml; charset=' . $encoding;
            } else {
                $this->_contentType = 'text/html; charset=' . $encoding;
            }
            $this->_constant = false;
        }
        $this->_noCache = $noCache;
    }

    // Method to send the HTTP header:
    public function send()
    {
        // Prevent caching:
        if ($this->_noCache) {
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 UTC');
        }

        // Send the content-type-header:
        header('Content-Type: ' . $this->_contentType);

        // Send vary header if content-type varies (important for proxy-caches):
        if (!$this->_constant) {
            header('Vary: Accept');
        }
    }

    // Method to return the content-type string:

    /**
     * @return string
     */
    public function getContentType()
    {
        // Return the content-type string:
        return $this->_contentType;
    }
}
