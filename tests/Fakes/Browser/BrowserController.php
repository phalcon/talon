<?php

/**
 * This file is part of the Phalcon Talon.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Fakes\Browser;

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;

/**
 * @property \Phalcon\Encryption\Security      $security
 * @property \Phalcon\Http\Request             $request
 * @property \Phalcon\Http\Response            $response
 * @property \Phalcon\Session\ManagerInterface $session
 */
class BrowserController extends Controller
{
    public function bounceAction(): ResponseInterface
    {
        return $this->response->redirect('/browser/landed', true);
    }

    public function cookieAction(): ResponseInterface
    {
        $sent = isset($_COOKIE['talon']) ? (string) $_COOKIE['talon'] : 'none';

        $this->response->setHeader('Set-Cookie', 'baked=yummy; Path=/');

        return $this->response->setContent('cookie sent=' . $sent);
    }

    public function echoAction(): ResponseInterface
    {
        return $this->response->setContent('post:' . (string) $this->request->getPost('q'));
    }

    public function formAction(): ResponseInterface
    {
        $tokenKey = $this->security->getTokenKey();
        $token    = $this->security->getToken();

        $html = '<html><body><h1>Log In</h1>'
            . '<form method="post" action="/browser/submit">'
            . '<input type="hidden" name="' . $tokenKey . '" value="' . $token . '"/>'
            . '<input type="text" name="name" value=""/>'
            . '<select name="active">'
            . '<option value="No">No</option>'
            . '<option value="Yes">Yes</option>'
            . '</select>'
            . '<button type="submit">Log In</button>'
            . '</form></body></html>';

        return $this->response->setContent($html);
    }

    public function landedAction(): ResponseInterface
    {
        return $this->response->setContent('<html><body>landed ok</body></html>');
    }

    public function menuAction(): ResponseInterface
    {
        $html = '<html><body>'
            . '<a href="/browser/landed">Go</a>'
            . '<table><tr><td>Row A</td><td><a href="/browser/landed">Open</a></td></tr></table>'
            . '</body></html>';

        return $this->response->setContent($html);
    }

    public function searchAction(): ResponseInterface
    {
        $html = '<html><body>'
            . '<form method="get" action="/browser/landed"><button type="submit">Search</button></form>'
            . '</body></html>';

        return $this->response->setContent($html);
    }

    public function securedAction(): ResponseInterface
    {
        if (!$this->session->has('user')) {
            return $this->response->setContent('<html><body>Guest</body></html>');
        }

        $user  = (string) $this->session->get('user');
        $flash = (string) $this->session->get('flash', '');

        return $this->response->setContent('<html><body>Welcome ' . $user . '. ' . $flash . '</body></html>');
    }

    public function submitAction(): ResponseInterface
    {
        if (!$this->security->checkToken()) {
            return $this->response->setContent('<html><body>CSRF failed</body></html>');
        }

        $this->session->set('user', (string) $this->request->getPost('name'));
        $this->session->set('flash', 'active=' . (string) $this->request->getPost('active'));

        return $this->response->redirect('/browser/secured', true);
    }
}
