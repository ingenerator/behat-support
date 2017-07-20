<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\BehatSupport\Mink;


use Behat\Mink\Session;

/**
 * Downloads binary resources using the user's current session
 *
 * Use when you need to access protected resources, such as profile images, generated export files,
 * etc. This will obviously be slow : use it sparingly and only if you plan to do something with the
 * downloaded content.
 *
 * @package Ingenerator\BehatSupport\Mink
 */
class MinkResourceDownloader
{

    const DL_SCRIPT = <<<'JS'
        (function (resource_url) {
            var req = new XMLHttpRequest(),
                reader = new FileReader(),
                result = null;

            req.open('get', resource_url, true);
            req.responseType = 'blob';
            req.onerror = function () {
                result = {success: false, error: 'Request Error: '+req.statusText};
            }
            req.onload = function () {
                if (req.status !== 200) {
                    result = {success: false, error: 'Bad response: ['+req.status+'] '+ req.statusText};
                } else {
                    reader.readAsDataURL(req.response);
                }
            }
            reader.onload = function () {
                var data_url = reader.result;
                result = {success: true, base64_content: data_url.split(',')[1]};
            }
            reader.onerror = function () {
                result = {success: false, error: 'Reader Error: '+reader.error};
            }
            req.send(null);

            window.HANDLER = {
                isComplete: function () { return result !== null},
                getResult:  function () { return result}
            }
        })(RESOURCE_URL)
JS;


    /**
     * @param \Behat\Mink\Session $session
     *
     * @return static
     */
    public static function forSession(Session $session)
    {
        return new static($session);
    }

    /**
     * @param \Behat\Mink\Session $session
     */
    protected function __construct(Session $session)
    {
        $this->mink = $session;
    }

    /**
     * @param string $url        URL to download
     * @param string $local_path The local file path to save the download to
     * @param int    $timeout_ms
     */
    public function download($url, $local_path, $timeout_ms = 2000)
    {
        $handle = uniqid('_dl_handle');
        $script = strtr(
            static::DL_SCRIPT,
            ['RESOURCE_URL' => json_encode($url), 'HANDLER' => $handle]
        );
        $this->mink->executeScript($script);
        if ( ! $this->mink->wait($timeout_ms, "window.$handle.isComplete();")) {
            throw new \RuntimeException('Timed out waiting for '.$url.' to download');
        }

        $result = $this->mink->evaluateScript("return window.$handle.getResult();");
        if ( ! $result['success']) {
            throw new \RuntimeException('Error downloading '.$url.' : '.$result['error']);
        }

        if ( ! file_put_contents($local_path, base64_decode($result['base64_content']))) {
            throw new \RuntimeException('Could not save downloaded content to '.$local_path);
        }
    }

}
