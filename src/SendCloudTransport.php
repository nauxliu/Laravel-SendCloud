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

    protected $query = [];

    private $api_user;
    private $api_key;

    /**
     * SendCloudTransport constructor.
     *
     * @param $api_user
     * @param $api_key
     */
    public function __construct($api_user, $api_key)
    {
        $this->api_user = $api_user;
        $this->api_key  = $api_key;
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
        $this->addQuery('api_user', $this->api_user);
        $this->addQuery('api_key', $this->api_key);
        $this->addQuery('subject', $message->getSubject());
        $this->addQuery('from', $this->getAddress($message->getFrom()));
        $this->addQuery('fromname', $this->getFromName($message));
        $this->addQuery('replyto', $this->getAddress($message->getReplyTo()));
        $this->addQuery('cc', $this->getAddresses($message->getCc()));
        $this->addQuery('bcc', $this->getAddresses($message->getBCc()));

        // 附件
        if (!empty($message->getChildren())) {
            foreach ($message->getChildren() as $file) {
                if ($file instanceof \Swift_MimePart) {
                    continue;
                }
                $this->addQuery('files[]', $file->getBody(), $file->getFilename());
            }
        }

        $this->query = array_filter($this->query);

        $body = $message->getBody();

        if ($body instanceof SendCloudTemplate) {
            $result = $this->sendTemplate($message);
        } else {
            $result = $this->sendRawMessage($message);
        }
        
        $this->query = [];
        
        return $result;
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
     * @return string|null
     */
    protected function getAddresses($data)
    {
        if (!$data || !is_array($data)) {
            return null;
        }
        $data = array_keys($data);

        if (empty($data)) {
            return null;
        }

        return implode(';', $data);
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

        $this->addQuery('html', $message->getBody() ?: '');
        $this->addQuery('to', $this->getAddress($message->getTo()));

        $response = $http->post(self::SEND_HTML_URL, [
            'multipart' => $this->query,
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

        $template = $message->getBody();
        $this->addQuery('template_invoke_name', $template->getName());
        $this->addQuery('substitution_vars', json_encode([
            'to'  => [$this->getAddress($message->getTo())],
            'sub' => $template->getBindData(),
        ]));

        $response = $http->post(self::SEND_TEMPLATE_URL, [
            'multipart' => $this->query,
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

    /**
     * 添加查询条件.
     *
     * @param $name
     * @param $contents
     * @param null $filename
     */
    public function addQuery($name, $contents, $filename = null)
    {
        $query = [
            'name'     => $name,
            'contents' => $contents,
        ];

        if ($filename) {
            $query['filename'] = $filename;
        }

        $this->query[] = $query;
    }
}
