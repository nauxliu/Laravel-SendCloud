<?php

namespace Naux\Mail;

use GuzzleHttp\Client;
use Illuminate\Mail\Transport\Transport;
use Psr\Http\Message\ResponseInterface;
use Swift_Mime_Message;

/**
 * Created by PhpStorm.
 * User: Xuan
 * Date: 11/19/15
 * Time: 9:39 AM.
 */
class SendCloudTransport extends Transport
{
    const SEND_HTML_URL     = 'http://sendcloud.sohu.com/webapi/mail.send.json';
    const SEND_TEMPLATE_URL = 'http://sendcloud.sohu.com/webapi/mail.send_template.json';

    private $query = [];

    /**
     * SendCloudTransport constructor.
     *
     * @param $api_user
     * @param $api_key
     */
    public function __construct($api_user, $api_key)
    {
        $this->query['api_user'] = $api_user;
        $this->query['api_key']  = $api_key;
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param Swift_Mime_Message $message
     * @param string[]           $failedRecipients An array of failures by-reference
     *
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->query['subject']  = $message->getSubject();
        $this->query['from']     = $this->getAddress($message->getFrom());
        $this->query['fromname'] = $this->getFromName($message);
        $this->query['replyto']  = $this->getAddress($message->getReplyTo());
        $this->query['cc']       = $this->getAddresses($message->getCc());
        $this->query['bcc']      = $this->getAddresses($message->getBcc());

        $this->query = array_filter($this->query);

        $body = $message->getBody();

        if ($body instanceof SendCloudTemplate) {
            $this->sendTemplate($message);
        } else {
            $this->sendRawMessage($message);
        }
    }

    /**
     * 获取地址.
     *
     * @param $data
     *
     * @return mixed
     */
    protected function getAddress($data)
    {
        if (!$data) {
            return;
        }

        return array_get(array_keys($data), 0, null);
    }

    /**
     * 获取发件人名.
     *
     * @param Swift_Mime_Message $message
     *
     * @return mixed
     */
    protected function getFromName(Swift_Mime_Message $message)
    {
        return array_get(array_values($message->getFrom()), 0);
    }

    /**
     * 获取多个地址,用 ; 分隔.
     *
     * @param $data
     *
     * @return string
     */
    protected function getAddresses($data)
    {
        if (!$data) {
            return;
        }
        $data = array_keys($data);

        if (is_array($data) && !empty($data)) {
            return implode(';', $data);
        }

        return;
    }

    /**
     * 发送普通邮件.
     *
     * @param Swift_Mime_Message $message
     *
     * @return bool
     *
     * @throws SendCloudException
     */
    protected function sendRawMessage(Swift_Mime_Message $message)
    {
        $http = new Client();

        $this->query['html'] = $message->getBody() ?: '';
        $this->query['to']   = $this->getAddress($message->getTo());

        $response = $http->post(self::SEND_HTML_URL, [
            'form_params' => $this->query,
        ]);

        return $this->response($response);
    }

    /**
     * 发送模板邮件.
     *
     * @param Swift_Mime_Message $message
     *
     * @return bool
     *
     * @throws SendCloudException
     *
     * @internal param SendCloudTemplate $template
     */
    protected function sendTemplate(Swift_Mime_Message $message)
    {
        $http = new Client();

        $template                            = $message->getBody();
        $this->query['template_invoke_name'] = $template->getName();
        $this->query['substitution_vars']    = json_encode([
            'to'  => [$this->getAddress($message->getTo())],
            'sub' => $template->getBindData(),
        ]);

        $response = $http->post(self::SEND_TEMPLATE_URL, [
            'form_params' => $this->query,
        ]);

        return $this->response($response);
    }

    /**
     * 解析 SendCloud 返回值,失败抛出异常.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     *
     * @throws SendCloudException
     */
    protected function response(ResponseInterface $response)
    {
        $res = json_decode($response->getBody()->getContents());

        if (isset($res->errors)) {
            throw new SendCloudException(array_get($res->errors, 0));
        }

        return true;
    }
}
