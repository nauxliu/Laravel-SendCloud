<?php
/**
 * Created by PhpStorm.
 * User: xuan
 * Date: 11/19/15
 * Time: 2:14 PM.
 */
namespace Naux\Mail;

class SendCloudTemplate
{
    /**
     * 调用的模板名.
     *
     * @var string
     */
    private $name;

    /**
     * SendCloudTemplate constructor.
     *
     * @param string $name      调用的模板名
     * @param array  $bind_data 模板中的变量
     */
    public function __construct($name, array $bind_data)
    {
        $this->name      = $name;
        $this->bind_data = $bind_data;
    }

    /**
     * 模板中的变量.
     *
     * @var array
     */
    private $bind_data;

    /**
     * 获取模板名.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取模板变量.
     *
     * @return array
     */
    public function getBindData()
    {
        $data = [];
        foreach ($this->bind_data as $key => $value) {
            $data["%$key%"] = [$value];
        }

        return $data;
    }
}
