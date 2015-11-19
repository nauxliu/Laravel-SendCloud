<?php
/**
 * Created by PhpStorm.
 * User: xuan
 * Date: 11/19/15
 * Time: 9:42 AM.
 */
namespace Naux\Mail;

use Illuminate\Mail\MailServiceProvider as IlluminateMailServiceProvider;

class MailServiceProvider extends IlluminateMailServiceProvider
{
    protected function registerSwiftTransport()
    {
        $this->app['swift.transport'] = $this->app->share(function ($app) {
            return new TransportManager($app);
        });
    }
}
