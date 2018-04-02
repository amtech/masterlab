<?php
/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/7/25 0025
 * Time: 上午 10:11
 */

namespace main\app\classes;

use main\app\model\system\MailQueueModel;
use main\app\model\UserModel;


/**
 * 邮件队列逻辑
 *
 */
class MailQueueLogic
{
    /**
     * 每页显示数
     * @var int
     */
    public $page_size = 50;

    public function __construct(int $page_size = 50)
    {
        $this->page_size = $page_size;
    }

    public function getPageHtml($conditions, $page)
    {
        $logModel = MailQueueModel::getInstance();
        $total = $logModel->getCount($conditions);
        $pages = ceil($total / $this->page_size);
        return getPageStrByAjax($pages, $page, $this->page_size);
    }

    public function getPageInfo($conditions, $page)
    {
        $logModel = MailQueueModel::getInstance();
        $total = $logModel->getCount($conditions);
        $pages = ceil($total / $this->page_size);
        return [$total, $pages, $page, getPageStrByAjax($pages, $page, $this->page_size) ,$this->page_size];
    }

    /**
     * 根据条件获取队列内容,并按照视图需要格式化数据
     * @param $conditions
     * @param $page
     * @param $remark
     * @param $order_by
     * @param $sort
     * @return array
     */
    public function query($conditions, $page, $order_by, $sort)
    {
        $start = $this->page_size * ($page - 1);
        $order = empty($order_by) ? '' : " $order_by $sort";
        $limit = " $start, " . $this->page_size;
        $append_sql = null;
        $logModel = MailQueueModel::getInstance();

        $logs = $logModel->getRows($logModel->fields, $conditions, $append_sql, $order, $sort, $limit);

        $i = max(0, ($page - 1) * $this->page_size);
        foreach ($logs as &$log) {
            $i++;
            $log['i'] = $i;
            $log['time_text'] = format_unix_time($log['create_time']);
        }
        return $logs;
    }

    /**
     * 添加队列
     * @param $address
     * @param $title
     * @param null $status
     * @param string $error
     * @return array
     */
    public static function add($address, $title, $status = NULL, $error = '')
    {
        //组装日志内容
        $log = new \stdClass();
        $log->address = $address;
        $log->title = $title;
        $log->status = $status;
        $log->error = $error;
        $log->create_time = time();

        //初始化日志model
        $logModel = new MailQueueModel();
        return $logModel->add($log);
    }

    /**
     * 更新队列
     * @param $id
     * @param null $status
     * @param string $error
     * @return array
     */
    public static function updateQueue($id, $status = NULL, $error = '')
    {
        $info = [];
        if (isset(MailQueueModel::getStatus()[$status])) {
            $info['status'] = $status;
        }
        if (!empty($error)) {
            $info['error'] = $error;
        }

        if (empty($info)) {
            return [false, 'param_is_empty'];
        }

        //初始化日志model
        $logModel = new MailQueueModel();
        return $logModel->updateById($id, $info);
    }
}