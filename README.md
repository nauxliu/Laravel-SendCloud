# Laravel-SendCloud

供 Laravel 5.X 使用的 SendCloud 驱动，发送方式完全兼容官方用法，可随时修改配置文件改为其他驱动，而不需要改动业务代码

> `Laravel 5.5` 以下版本请使用 `1.1.3` 版本，并手动添加 ServiceProvier  到 `config/app.php`

## 安装

```
composer require naux/sendcloud
```

## 配置

在 `.env` 中配置你的密钥， 并修改邮件驱动为 `sendcloud`

```ini
MAIL_DRIVER=sendcloud

SEND_CLOUD_USER=   # 创建的 api_user
SEND_CLOUD_KEY=    # 分配的 api_key
```

## 使用

#### 普通方式发送：
用法完全和系统自带的一样, 具体请参照官方文档： http://laravel.com/docs/5.1/mail

```php
Mail::send('emails.welcome', $data, function ($message) {
    $message->from('us@example.com', 'Laravel');

    $message->to('foo@example.com')->cc('bar@example.com');
});
```

#### 模板方式发送
用法和普通发送类似，不过需要将 `body` 设置为 `SendCloudTemplate` 对象

>  注意：使用模板发送不与其他邮件驱动兼容

```php
// 模板变量
$bind_data = ['url' => 'http://naux.me'];
$template = new SendCloudTemplate('模板名', $bind_data);

Mail::raw($template, function ($message) {
    $message->from('us@example.com', 'Laravel');

    $message->to('foo@example.com')->cc('bar@example.com');
});
```
