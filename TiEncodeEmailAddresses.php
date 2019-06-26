<?php
/*
Plugin Name: TI WP Encode E-Mail Addresses
Plugin URI: https://github.com/thammit/ti-wp-encode-mail-addresses
Description: A lightweight plugin to protect email addresses from email-harvesting robots by encoding them into decimal and hexadecimal entities.
Version: 0.0.1
Author: Thamm IT (Peter Pfeufer)
Author URI: https://www.thamm-it.de/
Text Domain: ti-wp-encode-mail-addresses
Domain Path: /l10n
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace WordPress\ThammIT\Plugins\TiWpEncodeEmailAddresses;

class TiWpEncodeEmailAddresses {
    /**
     * filterPriority
     *
     * @var int
     */
    protected $filterPriority = 1000;

    /**
     * Constructor
     */
    public function __construct() {
        /**
         * Register filters to encode plain email addresses in posts, pages, excerpts,
         * comments and text widgets.
         */
        foreach(['the_content', 'the_excerpt', 'widget_text', 'comment_text', 'comment_excerpt'] as $filter) {
            \add_filter($filter, [$this, 'tiEncodeMails'], $this->getFilterPriority());
        }
    }

    /**
     * getFilterPriority
     *
     * @return int
     */
    private function getFilterPriority() {
        return $this->filterPriority;
    }

    /**
     * encode E-Mails
     *
     * @param string $content
     * @return string
     */
    public function tiEncodeMails($content) {
        // abort if `$content` isn't a string
        if(!\is_string($content)) {
            return $content;
        }

        // abort if `ti-wp-encode-mail-addresses_at-sign-check` is true and `$content` doesn't contain a @-sign
        if(\apply_filters('ti-wp-encode-mail-addresses_at-sign-check', true ) && \strpos($content, '@') === false) {
            return $content;
        }

        // override encoding function with the 'ti-wp-encode-mail-addresses_metod' filter
        \apply_filters('ti-wp-encode-mail-addresses_metod', [$this, 'tiEncodeString']);

        // override regex pattern with the 'ti-encode-email-address_regexp' filter
        $regexp = \apply_filters(
            'ti-wp-encode-mail-addresses_regexp',
            '{
                (?:mailto:)?
                (?:
                    [-!#$%&*+/=?^_`.{|}~\w\x80-\xFF]+
                |
                    ".*?"
                )
                \@
                (?:
                    [-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+
                |
                    \[[\d.a-fA-F:]+\]
                )
            }xi'
        );

        return \preg_replace_callback(
            $regexp,
            function($matches) {
                return $this->tiEncodeString($matches[0]);
            },
            $content
        );
    }

    /**
     * encode a certain string
     *
     * @param string $string
     * @return string
     */
    private function tiEncodeString(string $string) {
        $chars = \str_split($string);
        $seed = \mt_rand(0, (int) \abs(\crc32($string) / \strlen($string)));

        foreach($chars as $key => $char) {
            $ord = \ord($char);

            if($ord < 128) { // ignore non-ascii chars
                $r = ($seed * (1 + $key)) % 100; // pseudo "random function"

                if($r > 60 && $char != '@') {
                    // plain character (not encoded), if not @-sign
                    ;
                } elseif($r < 45) {
                    $chars[$key] = '&#x' . \dechex($ord) . ';'; // hexadecimal
                } else {
                    $chars[$key] = '&#' . $ord . ';'; // decimal (ascii)
                }
            }
        }

        return \implode('', $chars);
    }
}

new TiWpEncodeEmailAddresses;
