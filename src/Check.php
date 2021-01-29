<?php

namespace XuanChen\UnionPay;

use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use XuanChen\UnionPay\Models\UnionpayCheck;
use XuanChen\UnionPay\Models\UnionpayCheckLog;

class Check
{

    //路径
    protected $path;

    //4位文件类型 JYMX
    protected $type;

    //文件类型
    protected $file_type;

    //服务器参数
    protected $sftpadapter;

    public    $date;

    public    $msg_sender;

    public    $adapter;

    public    $filesystem;

    public    $code = true;

    public    $msg  = '';

    /**
     * Notes: 设置参数
     * @Author: 玄尘
     * @Date  : 2021/1/29 13:47
     */
    public function setConfig()
    {
        $this->type       = config('unionpay.check.type');
        $this->file_type  = config('unionpay.check.file_type');
        $this->date       = now()->format('Ymd');
        $this->msg_sender = config('unionpay.msg_sender');

    }

    /**
     * Notes: 链接
     * @Author: 玄尘
     * @Date  : 2021/1/29 14:10
     */
    public function login()
    {
        $sftpadapter      = config('unionpay.check.sftpadapter');
        $this->adapter    = new SftpAdapter($sftpadapter);
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * Notes: description
     * @Author: 玄尘
     * @Date  : 2021/1/28 9:05
     */
    public function start()
    {
        if (!$this->filesystem) {
            $this->login();
        }

        // 判断文件是否存在
        if ($this->hasFile()) {

            $content    = $this->filesystem->read($this->getFileName());
            $content    = str_replace("\n", "br", $content);
            $content    = explode("br", $content);
            $check_data = $content[0];
            $check_data = explode('|', $check_data);

            $check_model = UnionpayCheck::updateOrCreate(
                [
                    'sender' => $check_data[0],
                    'date'   => $this->date,
                ],
                [
                    'number'     => $check_data[1],
                    'total'      => $check_data[2],
                    'commission' => $check_data[3],
                ]);

            unset($content[0]);

            foreach ($content as $row) {
                if (empty($row)) {
                    continue;
                }

                $row = explode('|', $row);
                UnionpayCheckLog::updateOrCreate(
                    [
                        'code'              => $row[23],
                        'req_serial_no'     => $row[7],
                        'shop'              => $row[0],
                        'unionpay_check_id' => $check_model->id,
                    ],
                    [
                        'msg_txn_code' => $row[2],
                        'total'        => $row[9],
                        'price'        => $row[27],
                        'amount'       => $row[14],
                        'sett_date'    => $row[1],
                        'source'       => $row,
                    ]);
            }
            $this->msg = '完成';
        } else {
            $this->code = false;
            $this->msg  = '文件不存在';
        }

    }

    public function copy($path, $newpath)
    {
        return $this->filesystem->copy($path, $newpath);
    }

    /**
     * Notes: 返回文件名
     * @Author: 玄尘
     * @Date  : 2021/1/29 13:52
     */
    public function getFileName()
    {
        return $this->type . $this->msg_sender . $this->date . '.' . $this->file_type;
    }

    /**
     * Notes: 文件是否存在
     * @Author: 玄尘
     * @Date  : 2021/1/29 14:04
     * @return mixed
     */
    public function hasFile($filename = '')
    {
        try {
            if (!$filename) {
                $filename = $this->getFileName();
            }

            return $this->filesystem->has($filename);

        } catch (\Exception $exception) {
            $this->msg = $exception->getMessage();

            return $this->code = false;
        }

    }

    /**
     * Notes: 获取文件系统
     * @Author: 玄尘
     * @Date  : 2021/1/29 14:05
     */
    public function getFileSystem()
    {
        return $this->filesystem;
    }

    /**
     * Notes: 获取文件列表
     * @Author: 玄尘
     * @Date  : 2021/1/29 14:05
     */
    public function getFiles()
    {
        return $this->filesystem->listContents();
    }

}