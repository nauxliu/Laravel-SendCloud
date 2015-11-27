<?php
/**
 * Created by PhpStorm.
 * User: xuan
 * Date: 11/19/15
 * Time: 9:42 AM.
 */
namespace Naux\Mail;

use Illuminate\Mail\TransportManager;
use Illuminate\Support\ServiceProvider;

class SendCLoudServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->resolving('swift.transport', function (TransportManager $tm) {
            $tm->extend('sendcloud', function () {
                $api_user = config('services.sendcloud.api_user');
                $api_key = config('services.sendcloud.api_key');

                return new SendCloudTransport($api_user, $api_key);
            });
        });
    }
}
