<?php
/**
 * Created by PhpStorm.
 * User: xuan
 * Date: 11/19/15
 * Time: 9:40 AM.
 */
namespace Naux\Mail;

use Illuminate\Mail\TransportManager as IlluminateTransportManager;

class TransportManager  extends IlluminateTransportManager
{
    /**
     * 创建一个SendCloud的 Transport 实例.
     *
     * @return \Swift_SendmailTransport
     */
    public function createSendCloudDriver()
    {
        $api_user = config('services.sendcloud.api_user');
        $api_key  = config('services.sendcloud.api_key');

        return new SendCloudTransport($api_user, $api_key);
    }
}
