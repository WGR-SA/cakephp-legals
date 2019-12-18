<?php
namespace Wgr\Legals\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Configure;

class CookieConsentMiddleware
{
  use InstanceConfigTrait;

  protected $_defaultConfig = [
    'cookieConsent' => true,
  ];

  protected function _init()
  {
    if( empty(Configure::read('WGR.legals')) )
    {
      $key = 'legals';
      try {
        Configure::load($key, 'legals');
      } catch (Exception $ex) {
        throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', $key), 1);
      }
    }
    $this->setConfig(Configure::read('WGR.legals'));
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
  {
    $response = $next($request, $response);
    $this->_init();

    if (! $this->getConfig('cookieConsent')) return $response;
    if (! $this->containsBodyTag($response)) return $response;
    return $this->addCookieConsentScriptToResponse($response);
  }

  protected function containsBodyTag(ResponseInterface $response): bool
  {
    return $this->getLastClosingBodyTagPosition($response->getBody()) !== false;
  }

  protected function addCookieConsentScriptToResponse(ResponseInterface $response)
  {
    $content = $response->getBody();
    $closingBodyTagPosition = $this->getLastClosingBodyTagPosition($content);
    $content = ''
    .substr($content, 0, $closingBodyTagPosition)
    //.view('cookieConsent::index')->render()
    .'<span class="d-none"></span>'
    .substr($content, $closingBodyTagPosition);

    $body = $response->getBody();
    $body->write($content);

    return $response;
  }

  protected function getLastClosingBodyTagPosition(string $content = '')
  {
    return strripos($content, '</body>');
  }
}
