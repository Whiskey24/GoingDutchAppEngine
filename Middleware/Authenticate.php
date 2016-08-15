<?php


namespace Middleware;
use Db;

class Authenticate
{
    /**
     * Authenticate middleware invokable class
     *
     * Uses basic authorization field: https://en.wikipedia.org/wiki/Basic_access_authentication#Client_side
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */

    public static $requestUid;

    public function __invoke($request, $response, $next)
    {
        global $app_config;

        $authorized = false;
        $name = '';
        $pass = '';

        // first check header
        if ($request->hasHeader('PHP_AUTH_USER')) {
            $name = $request->getHeader('PHP_AUTH_USER')[0];
            $pass = $request->getHeader('PHP_AUTH_PW')[0];
        }

        // now check if running under fastcgi
        else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            list($name, $pass) = explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
        }

        // validate the credentials
        if (!empty($name) && !empty($pass) && $pass !== 0) {
            $salt = $app_config['secret']['hash'];
            $hash = md5($salt . $pass . $salt);

            // validate credentials
            $stmt = Db::getInstance()->prepare("SELECT users.* FROM users WHERE email = :name 
                                                AND (password = :hash OR pwd_recovery = :hash)
                                                AND account_deleted = 0");
            $stmt->execute(array(':name' => $name, ':hash' => $hash));
            $result = $stmt->fetch();
            if ($result) {
                $authorized = true;
                self::$requestUid = intval($result['user_id']);
            }
            else {
                $stmt = Db::getInstance()->prepare("SELECT users.* FROM users WHERE username = :name 
                                                    AND (password = :hash OR pwd_recovery = :hash)
                                                    AND account_deleted = 0");
                $stmt->execute(array(':name' => $name, ':hash' => $hash));
                $result = $stmt->fetch();
                if ($result) {
                    $authorized = true;
                    self::$requestUid = intval($result['user_id']);
                }
            }
        }

        if (!$authorized)
            return $response->withStatus(403)->write('Not authorized');

        $response = $next($request, $response);
        return $response;
    }

}