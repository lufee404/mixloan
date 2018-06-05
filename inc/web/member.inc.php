<?php
defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
$config = $this->module['config'];
if (empty($_GPC['op'])) {
    $operation = 'list';
} else {
    $operation = $_GPC['op'];
}
if ($operation == 'list') {
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $wheres = ' AND status!=-1';
    if (!empty($_GPC['id'])) {
        $wheres.= " AND id='{$_GPC['id']}'";
    }
    if (!empty($_GPC['nickname'])) {
        $wheres.= " AND nickname LIKE '%{$_GPC['nickname']}%'";
    }
    $sql = 'select * from ' . tablename('xuan_mixloan_member') . "where uniacid={$_W['uniacid']} "  . $wheres . ' ORDER BY ID DESC';
    if ($_GPC['export'] != 1) {
        $sql.= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql);
        foreach ($list as &$row) {
            $row['type'] = m('member')->checkAgent($row['id'])['code'];
        }
        unset($row);
    } else {
        $list = pdo_fetchall($sql);
        m('excel')->export($list, array("title" => "会员数据-" . date('Y-m-d-H-i', time()), "columns" => array(array('title' => '昵称', 'field' => 'nickname', 'width' => 12), array('title' => '姓名', 'field' => 'realname', 'width' => 12), array('title' => '昵称', 'field' => 'nickname', 'width' => 12),)));
    }
    $total = pdo_fetchcolumn( 'select count(1) from ' . tablename('xuan_mixloan_member') . "where uniacid={$_W['uniacid']} "  . $wheres . ' ORDER BY ID DESC' );
    $pager = pagination($total, $pindex, $psize);
} else if ($operation == 'delete') {
    $member = m('member')->getMember($_GPC['id']);
    pdo_update('xuan_mixloan_member', array("status" => -1, 'openid'=>'', 'uid'=>0, 'phone'=>''), array('id'=>$_GPC['id']));
    pdo_delete('xuan_mixloan_inviter', array("phone" => $member["phone"]));
    pdo_delete('xuan_mixloan_inviter', array("uid" => $_GPC["id"]));
    pdo_delete('xuan_mixloan_payment', array("uid" => $_GPC["id"]));
    message("删除成功", $this->createWebUrl('member'), 'success');
} else if ($operation == 'agent') {
    //设为代理
    $res = m('member')->checkAgent($_GPC['id']);
    if ($res['code'] == 1) {
        message("此会员已经是代理，取消代理可以去“代理会员”取消", "", "error");
    }
    $insert = array(
            "uniacid"=>$_W["uniacid"],
            "uid"=>$_GPC['id'],
            "createtime"=>time(),
            "tid"=>"20001" . date('YmdHis', time()),
            "fee"=>0,
    );
    pdo_insert("xuan_mixloan_payment",$insert);
    message("设置成功", $this->createWebUrl('member'), "success");
} else if ($operation == 'send_msg') {
    //发送信息
    if ($_GPC['post'] == 1) {
        $msg = $_GPC['msg'];
        $url = $_GPC['url'];
        $members = pdo_fetchall("select b.openid from ".tablename('xuan_mixloan_payment').' a left join '. tablename('xuan_mixloan_member').' b on a.uid=b.id where a.msg=1 and a.uniacid=:uniacid group by a.uid', [':uniacid'=>$_W['uniacid']]);
        foreach ($members as $member) {
            sendCustomNotice($member['openid'], $msg, $url, $account);
        }
        message('发送成功', '', 'success');
    }
} else if ($operation == 'update') {
    $id = $_GPC['id'];
    $member = pdo_fetch("select * from ".tablename("xuan_mixloan_member")." where id={$id}");
    if ($_GPC['post'] == 1) {
        if (!empty($_GPC['data']['phone']) && $_GPC['data']['phone'] != $member['phone']) {
            $count = pdo_fetchcolumn('select count(*) from ' .tablename('xuan_mixloan_member'). '
                where phone=:phone and uniacid=:uniacid', array(':uniacid'=>$_W['uniacid'], ':phone'=>$_GPC['data']['phone']));
            if ($count) {
                message('该手机号已存在，请更换手机号绑定', '', 'error');
            }
        }
        if (!empty($_GPC['data']['certno']) && $_GPC['data']['certno'] != $member['certno']) {
            $count = pdo_fetchcolumn('select count(*) from ' .tablename('xuan_mixloan_member'). '
                where certno=:certno and uniacid=:uniacid', array(':uniacid'=>$_W['uniacid'], ':phone'=>$_GPC['data']['certno']));
            if ($count) {
                message('该身份证已存在，请更换身份证绑定', '', 'error');
            }
        }
        pdo_update("xuan_mixloan_member", $_GPC['data'], array("id"=>$id));
        message('更新成功', $this->createWebUrl('member'), 'success');
    }
} else if ($operation == 'send_notice') {
    //发送模板消息，签档提醒
    if ($_GPC['post'] == 1) {
        $time = date("Y-m-d H-i");
        $createtime = time();
        if ($_GPC['type'] == 1) {
            $first = "尊敬的，您好！\n米缸服务新口子已更新，请订阅查看！";
            $title = $_GPC['type1_title'];
            $author = $_GPC['type1_author'];
            $remark = "米缸服务已经更新产品，您可以点击详情查看今日更多产品发布！\n（如无需订阅，请在个人中心取消订阅！";
            $url = $_GPC['type1_url'];
            $template_id = $config['tpl_notice3'];
        } else if ($_GPC['type'] == 2) {
            $keyword1 = $_GPC['type2_keyword1'];
            $keyword2 = $_GPC['type2_keyword2'];
            $keyword4 = $_GPC['type2_keyword4'];
            $first = "尊敬的代理，您好！{$config['title']}上线{$keyword1}啦，特此通知，请知悉！";
            $remark = "您可以点击【详情】生成自己的专属二维码，立马赚钱！感谢您对我们的支持";
            $url = $_GPC['type2_url'];
            $template_id = $config['tpl_notice6'];
        } else if ($_GPC['type'] == 3) {
            $keyword1 = $_GPC['type3_keyword1'];
            $keyword3 = $_GPC['type3_keyword3'];
            $first = "尊敬的代理，您好！{$keyword1}临时下架，特此通知，请知悉！";
            $remark = "{$keyword1}在{$time}钱的佣金正常结算，之后将停止结算，请大家停止推广此产品，如有变动择日另行通知";
            $url = $_GPC['type3_url'];
            $template_id = $config['tpl_notice7'];
        } else {
            message('请选择发送消息类型', '', 'error');
        }
        $members = pdo_fetchall("SELECT openid FROM `ims_mc_mapping_fans` WHERE uniacid=:uniacid AND follow=1", [':uniacid'=>$_W['uniacid']]);
        foreach ($members as $member) {
            $openid = $member['openid'];
            if ($_GPC['type'] == 1) {
                $datam = array(
                    "first" => array(
                        "value" => $first,
                        "color" => "#173177"
                    ) ,
                    "keyword1" => array(
                        "value" => $title,
                        "color" => "#FF0000"
                    ) ,
                    "keyword2" => array(
                        "value" => $author,
                        "color" => "#173177"
                    ) ,
                    "keyword3" => array(
                        "value" => $time,
                        "color" => "#173177"
                    ) ,
                    "remark" => array(
                        "value" => $remark,
                        "color" => "#A4D3EE"
                    ) ,
                ); 
            } else if ($_GPC['type'] == 2){
                $datam = array(
                    "first" => array(
                        "value" => $first,
                        "color" => "#173177"
                    ) ,
                    "keyword1" => array(
                        "value" => "【{$keyword1}】",
                        "color" => "red"
                    ) ,
                    "keyword2" => array(
                        "value" => $keyword2,
                        "color" => "#173177"
                    ) ,
                    "keyword3" => array(
                        "value" => $time,
                        "color" => "#173177"
                    ) ,
                    "keyword4" => array(
                        "value" => $keyword4,
                        "color" => "#173177"
                    ) ,
                    "keyword5" => array(
                        "value" => $time,
                        "color" => "#173177"
                    ) ,
                    "remark" => array(
                        "value" => $remark,
                        "color" => "#A4D3EE"
                    ) ,
                ); 
            } else if ($_GPC['type'] == 3){
                $datam = array(
                    "first" => array(
                        "value" => $first,
                        "color" => "#173177"
                    ) ,
                    "keyword1" => array(
                        "value" => $keyword1,
                        "color" => "#173177"
                    ) ,
                    "keyword2" => array(
                        "value" => $time,
                        "color" => "#173177"
                    ) ,
                    "keyword3" => array(
                        "value" => $keyword3,
                        "color" => "#173177"
                    ) ,
                    "remark" => array(
                        "value" => $remark,
                        "color" => "#A4D3EE"
                    ) ,
                ); 
            }
            $temp = array(
                'uniacid' => $_W['uniacid'],
                'openid' => "'{$openid}'",
                'template_id' => "'{$template_id}'",
                'data' => "'" . addslashes(json_encode($datam)) . "'",
                'url' => "'{$url}'",
                'createtime'=>$createtime,
                'status'=>0
            );
            $temp_string = '('. implode(',', array_values($temp)) . ')';
            $insert[] = $temp_string;
        }
        if (!empty($insert)) {
            $insert_string =  implode(',', $insert);
            pdo_run("INSERT ".tablename("xuan_mixloan_notice"). " ( `uniacid`, `openid`, `template_id`, `data`, `url`, `createtime`, `status`) VALUES {$insert_string}");
        }
        
        $count = count($insert);
        message("发送成功，总计发送{$count}条，已转入消息发送队列", "", "success");
        
    }
}
include $this->template('member');
?>