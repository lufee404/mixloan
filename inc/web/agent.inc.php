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
    //会员列表
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $wheres = '';
    if (!empty($_GPC['name'])) {
        $wheres.= " AND b.nickname LIKE '%{$_GPC['name']}%'";
    }
    $sql = 'select a.id,a.uid,b.nickname,b.avatar,b.phone,a.createtime,a.fee,a.tid from ' . tablename('xuan_mixloan_payment') . " a left join ".tablename("xuan_mixloan_member")." b ON a.uid=b.id where a.uniacid={$_W['uniacid']} " . $wheres . ' ORDER BY a.id DESC';
    if ($_GPC['export'] != 1) {
        $sql.= " limit " . ($pindex - 1) * $psize . ',' . $psize;
    }
    $list = pdo_fetchall($sql);
    foreach ($list as &$row) {
        $row['upgrade_fee'] = pdo_fetchcolumn('select sum(fee) from ' .tablename('xuan_mixloan_upgrade'). '
            where uniacid=:uniacid and uid=:uid', array(':uniacid' => $_W['uniacid'], ':uid' => $row['uid'])) ? : 0;
    }
    unset($row);
    $total = pdo_fetchcolumn( 'select count(1) from ' . tablename('xuan_mixloan_payment') . " a left join ".tablename("xuan_mixloan_member")." b ON a.uid=b.id where a.uniacid={$_W['uniacid']} " . $wheres );
    $pager = pagination($total, $pindex, $psize);
} else if ($operation == 'apply_list') {
    //申请列表
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $wheres = '';
    if (!empty($_GPC['name'])) {
        $wheres.= " AND a.realname LIKE '%{$_GPC['name']}%'";
    }
    if (!empty($_GPC['uid'])) {
        $wheres.= " AND a.inviter='{$_GPC['uid']}'";
    }
    if (!empty($_GPC['type'])) {
        $wheres.= " AND c.type='{$_GPC['type']}'";
    }
    if (!empty($_GPC['relate_id'])) {
        $wheres.= " AND c.relate_id='{$_GPC['relate_id']}'";
    }
    $c_arr = m('bank')->getCard(['id', 'name']);
    $s_arr = m('loan')->getList(['id', 'name']);
    foreach ($c_arr as &$row) {
        $row['type'] = 1;
    }
    unset($row);
    foreach ($s_arr as &$row) {
        $row['type'] = 2;
    }
    unset($row);
    $c_json = $c_arr ? json_encode(array_values($c_arr)) : json_encode([]);
    $s_json = $s_arr ? json_encode(array_values($s_arr)) : json_encode([]);
    $sql = 'select a.*,b.avatar,c.name,c.count_time from ' . tablename('xuan_mixloan_product_apply') . " a left join ".tablename("xuan_mixloan_member")." b ON a.uid=b.id LEFT JOIN ".tablename("xuan_mixloan_product")." c ON a.pid=c.id where a.uniacid={$_W['uniacid']} and a.status<>-2 " . $wheres . ' ORDER BY a.id DESC';
    if ($_GPC['export'] != 1) {
        $sql.= " limit " . ($pindex - 1) * $psize . ',' . $psize;
    }
    $list = pdo_fetchall($sql);
    foreach ($list as &$row) {
        if ($row['pid'] == 0) {
            $row['realname'] = pdo_fetchcolumn('SELECT nickname FROM '.tablename('xuan_mixloan_member').' WHERE id=:id', array(':id'=>$row['uid']));
            $row['name'] = '邀请购买代理';
        } else if ($row['pid'] == -1) {
            $row['realname'] = pdo_fetchcolumn('SELECT nickname FROM '.tablename('xuan_mixloan_member').' WHERE id=:id', array(':id'=>$row['uid']));
            $row['name'] = '升级代理';
        }
        $man = pdo_fetch("select id,avatar,nickname from ".tablename("xuan_mixloan_member")." where id = {$row['uid']}");
        $row['nickname'] = $man['nickname'];
        $row['avatar'] = $man['avatar'];
        if (empty($row['realname'])) {
            $row['realname'] = $row['nickname'];
        }
        $row['inviter'] = pdo_fetch("select id,avatar,nickname from ".tablename("xuan_mixloan_member")." where id = {$row['inviter']}");
    }
    unset($row);
    if ($_GPC['export'] == 1) {
        foreach ($list as &$row) {
            if ($row['status'] == -2){
                $row['status'] = '邀请用户已注册过，不产生佣金';
            } else if ($row['status'] == -1){
                $row['status'] = '注册失败';
            } else if ($row['status'] == 0){
                $row['status'] = '邀请中';
            } else if ($row['status'] == 1){
                $row['status'] = '已注册';
            } else if ($row['status'] == 1){
                $row['status'] = '已完成';
            }
            $row['createtime'] = date('Y-m-d H:i:s', $row['createtime']);
            if ($row['inviter']) {
                $row['inviter_name'] = $row['inviter']['nickname'];
                $row['inviter_count'] = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename("xuan_mixloan_bonus")." WHERE inviter={$row['inviter']['id']} AND status>1 AND relate_id={$row['relate_id']}") ? : 0;
                $row['inviter_sum'] = pdo_fetchcolumn("SELECT SUM(relate_money) FROM ".tablename("xuan_mixloan_bonus")." WHERE inviter={$row['inviter']['id']} AND status>1 AND relate_id={$row['relate_id']}") ? : 0;
            } else {
                $row['inviter_name'] = '无';
                $row['inviter_count'] = 0;
                $row['inviter_sum'] = 0;
            }
            if ($row['count_time'] == 1) {
                $row['count_time'] = '日结';
            } else if ($row['count_time'] == 7) {
                $row['count_time'] = '周结';
            } else if ($row['count_time'] == 30) {
                $row['count_time'] = '月结';
            } else {
                $row['count_time'] = '现结';
            }
        }
        unset($row);
        m('excel')->export($list, array(
            "title" => "申请资料",
            "columns" => array(
                array(
                    'title' => 'id',
                    'field' => 'id',
                    'width' => 10
                ),
                array(
                    'title' => '邀请人',
                    'field' => 'inviter_name',
                    'width' => 20
                ),
                array(
                    'title' => '被邀请人',
                    'field' => 'realname',
                    'width' => 20
                ),
                array(
                    'title' => '关联产品',
                    'field' => 'name',
                    'width' => 20
                ),
                array(
                    'title' => '身份证',
                    'field' => 'certno',
                    'width' => 20
                ),
                array(
                    'title' => '手机号',
                    'field' => 'phone',
                    'width' => 20
                ),
                array(
                    'title' => '结算方式',
                    'field' => 'count_time',
                    'width' => 20
                ),
                array(
                    'title' => '下款金额',
                    'field' => 'relate_money',
                    'width' => 20
                ),
                array(
                    'title' => '注册奖励',
                    'field' => 're_bonus',
                    'width' => 20
                ),
                array(
                    'title' => '下款/卡奖励',
                    'field' => 'done_bonus',
                    'width' => 20
                ),
                array(
                    'title' => '额外奖励',
                    'field' => 'extra_bonus',
                    'width' => 20
                ),
                array(
                    'title' => '邀请时间',
                    'field' => 'createtime',
                    'width' => 20
                ),
                array(
                    'title' => '该产品已成功邀请总数',
                    'field' => 'inviter_count',
                    'width' => 30
                ),
                array(
                    'title' => '该产品已邀请下款总额',
                    'field' => 'inviter_sum',
                    'width' => 30
                ),
            )
        ));
        unset($row);
    }
    $total = pdo_fetchcolumn( 'select count(*) from ' . tablename('xuan_mixloan_product_apply') . " a left join ".tablename("xuan_mixloan_member")." b ON a.uid=b.id LEFT JOIN ".tablename("xuan_mixloan_product")." c ON a.pid=c.id where a.uniacid={$_W['uniacid']} and a.status<>-2  " . $wheres );
    $pager = pagination($total, $pindex, $psize);
} else if ($operation == 'withdraw_list') {
    //提现列表
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $wheres = '';
    if (isset($_GPC['status']) && $_GPC['status'] != "") {
        $wheres .= " and a.status={$_GPC['status']}";
    }
    $sql = 'select a.id,b.nickname,b.avatar,a.createtime,a.bonus,a.status,a.uid from ' . tablename('xuan_mixloan_withdraw') . " a left join ".tablename("xuan_mixloan_member")." b ON a.uid=b.id where a.uniacid={$_W['uniacid']} " . $wheres . ' ORDER BY a.id DESC';
    $sql.= " limit " . ($pindex - 1) * $psize . ',' . $psize;
    $list = pdo_fetchall($sql);
    foreach ($list as &$row) {
        $all = pdo_fetchcolumn("SELECT SUM(re_bonus+done_bonus+extra_bonus) FROM ".tablename("xuan_mixloan_product_apply")." WHERE uniacid={$_W['uniacid']} AND inviter={$row['uid']}");
        $row['left_bonus'] = $all - m('member')->sumWithdraw($row['uid']);
    }
    unset($row);
    $total = pdo_fetchcolumn( 'select count(1) from ' . tablename('xuan_mixloan_withdraw') . " a left join ".tablename("xuan_mixloan_member")." b ON a.uid=b.id where a.uniacid={$_W['uniacid']} " . $wheres );
    $pager = pagination($total, $pindex, $psize);
} else if ($operation == 'delete') {
    pdo_delete('xuan_mixloan_payment', array("id" => $_GPC["id"]));
    message("提交成功", $this->createWebUrl('agent', array('op' => '')), "sccuess");
} else if ($operation == 'apply_delete') {
    pdo_delete('xuan_mixloan_product_apply', array("id" => $_GPC["id"]));
    message("提交成功", $this->createWebUrl('agent', array('op' => 'apply_list')), "sccuess");
} else if ($operation == 'withdraw_delete') {
    pdo_delete('xuan_mixloan_withdraw', array("id" => $_GPC["id"]));
    message("提交成功", $this->createWebUrl('agent', array('op' => 'withdraw_list')), "sccuess");
} else if ($operation == 'apply_update') {
    //申请编辑
    $id = intval($_GPC['id']);
    $item = pdo_fetch('select * from '.tablename("xuan_mixloan_product_apply"). " where id={$id}");
    if ($item['pid']>0) {
        $info = pdo_fetch('select * from '.tablename("xuan_mixloan_product")." where id=:id", array(':id'=>$item['pid']));
        $agent = m('member')->checkAgent($item['inviter'], $config);
        $info['ext_info'] = json_decode($info['ext_info'], true);
        if ($agent['level'] == 1) {
            if ($item['degree'] == 1) {
                $info['done_reward_money'] = $info['ext_info']['done_one_init_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_one_init_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_one_init_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_one_init_reward_per'];
            } else if ($item['degree'] == 2) {
                $info['done_reward_money'] = $info['ext_info']['done_two_init_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_two_init_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_two_init_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_two_init_reward_per'];
            } else if ($item['degree'] == 3) {
                $info['done_reward_money'] = $info['ext_info']['done_thr_init_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_thr_init_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_thr_init_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_thr_init_reward_per'];
            }
        } else if ($agent['level'] == 2) {
            if ($item['degree'] == 1) {
                $info['done_reward_money'] = $info['ext_info']['done_one_mid_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_one_mid_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_one_mid_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_one_mid_reward_per'];
            } else if ($item['degree'] == 2) {
                $info['done_reward_money'] = $info['ext_info']['done_two_mid_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_two_mid_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_two_mid_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_two_mid_reward_per'];
            } else if ($item['degree'] == 3) {
                $info['done_reward_money'] = $info['ext_info']['done_thr_mid_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_thr_mid_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_thr_mid_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_thr_mid_reward_per'];
            }
        } else if ($agent['level'] == 3) {
            if ($item['degree'] == 1) {
                $info['done_reward_money'] = $info['ext_info']['done_one_height_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_one_height_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_one_height_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_one_height_reward_per'];
            } else if ($item['degree'] == 2) {
                $info['done_reward_money'] = $info['ext_info']['done_two_height_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_two_height_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_two_height_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_two_height_reward_per'];
            } else if ($item['degree'] == 3) {
                $info['done_reward_money'] = $info['ext_info']['done_thr_height_reward_money'];
                $info['done_reward_per'] = $info['ext_info']['done_thr_height_reward_per'];
                $info['re_reward_money'] = $info['ext_info']['re_thr_height_reward_money'];
                $info['re_reward_per'] = $info['ext_info']['re_thr_height_reward_per'];
            }
        }
    } else if ($row['pid'] == 0){
        $info['name'] = '邀请购买代理奖励';
    } else if ($row['pid'] == -1){
        $info['name'] = '邀请升级代理奖励';
    }
    $inviter = pdo_fetch('select avatar,nickname from '.tablename("xuan_mixloan_member")." where id=:id",array(':id'=>$item['inviter']));
    $inviter['count'] = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename("xuan_mixloan_product_apply")." WHERE inviter={$item['inviter']} AND status>1 AND pid={$item['pid']}") ? : 0;
    $inviter['sum'] = pdo_fetchcolumn("SELECT SUM(relate_money) FROM ".tablename("xuan_mixloan_product_apply")." WHERE inviter={$item['inviter']} AND status>1 AND pid={$item['pid']}") ? : 0;
    $apply = pdo_fetch('select avatar,nickname,phone,certno from '.tablename("xuan_mixloan_member")." where id=:id",array(':id'=>$item['uid']));
    if ($_GPC['post'] == 1) {
        $re_money = $_GPC['data']['re_bonus'];
        $count_money = $_GPC['data']['done_bonus'] + $_GPC['data']['extra_bonus'];
        $one_man = m('member')->getInviterInfo($item['inviter']);
        $url = $_W['siteroot'] . 'app/' .$this->createMobileUrl('vip', array('op'=>'salary'));
        $account = WeAccount::create($_W['acid']);
        if ($_GPC['data']['status'] == 1 && $re_money>0) {
            $datam = array(
                "first" => array(
                    "value" => "您好，您的下级代理{$item['realname']}成功注册了{$info['name']}，奖励您推广佣金，继续推荐产品，即可获得更多佣金奖励",
                    "color" => "#FF0000"
                ) ,
                "order" => array(
                    "value" => '10000'.$item['id'],
                    "color" => "#173177"
                ) ,
                "money" => array(
                    "value" => $re_money,
                    "color" => "#173177"
                ) ,
                "remark" => array(
                    "value" => '点击后台“我的账户->去提现”，立享提现快感',
                    "color" => "#912CEE"
                ) ,
            );
            $account->sendTplNotice($one_man['openid'], $config['tpl_notice5'], $datam, $url);
        }
        if ($_GPC['data']['status'] == 2 && $count_money>0) {
            $datam = array(
                "first" => array(
                    "value" => "您好，您的下级代理{$item['realname']}成功注册了{$info['name']}，奖励您推广佣金，继续推荐产品，即可获得更多佣金奖励",
                    "color" => "#FF0000"
                ) ,
                "order" => array(
                    "value" => '10000'.$item['id'],
                    "color" => "#173177"
                ) ,
                "money" => array(
                    "value" => $count_money,
                    "color" => "#173177"
                ) ,
                "remark" => array(
                    "value" => '点击后台“我的账户->去提现”，立享提现快感',
                    "color" => "#912CEE"
                ) ,
            );
            $account->sendTplNotice($one_man['openid'], $config['tpl_notice5'], $datam, $url);
        }
        pdo_update('xuan_mixloan_product_apply', $_GPC['data'], array('id'=>$item['id']));
        message("提交成功", $this->createWebUrl('agent', array('op' => 'apply_list')), "sccuess");
    }
} else if ($operation == 'withdraw_update') {
    //提现更改
    $id = intval($_GPC['id']);
    $item = pdo_fetch('select * from '.tablename("xuan_mixloan_withdraw"). " where id={$id}");
    $item['ext_info'] = json_decode($item['ext_info'], true);
    $member = pdo_fetch('select avatar,nickname,openid from '.tablename("xuan_mixloan_member")." where id=:id",array(':id'=>$item['uid']));
    $bank = pdo_fetch('select realname,bankname,banknum,phone from '.tablename("xuan_mixloan_creditCard")." where id=:id",array(':id'=>$item['bank_id']));
    if ($_GPC['post'] == 1) {
        if ($_GPC['data']['status'] == 1) {
            $wx = WeAccount::create();
            $msg = array(
                'first' => array(
                    'value' => "您申请的提现金额已到帐。",
                    "color" => "#4a5077"
                ),
                'keyword1' => array(
                    'value' => date("Y-m-d H:i:s",time()),
                    "color" => "#4a5077"
                ),
                'keyword2' => array(
                    'value' => "微信转账",
                    "color" => "#4a5077"
                ),
                'keyword3' => array(
                    'value' => $item['bonus'],
                    "color" => "#4a5077"
                ),
                'keyword4' => array(
                    'value' => 0,
                    "color" => "#4a5077"
                ),
                'keyword5' => array(
                    'value' => $item['bonus'],
                    "color" => "#4a5077"
                ),
                'remark' => array(
                    'value' => "感谢你的使用。",
                    "color" => "#A4D3EE"
                ),
            );
            $templateId=$config['tpl_notice6'];
            $res = $wx->sendTplNotice($member['openid'],$templateId,$msg);
        }
        if ($_GPC['data']['ext_info']) $_GPC['data']['ext_info'] = json_encode($_GPC['data']['ext_info']);
        pdo_update('xuan_mixloan_withdraw', $_GPC['data'], array('id'=>$item['id']));
        message("提交成功", $this->createWebUrl('agent', array('op' => 'withdraw_list')), "sccuess");
    }
} else if ($operation == 'qrcode') {
    //二维码海报
    $invite_list = pdo_fetchall('SELECT poster FROM '.tablename('xuan_mixloan_poster').' WHERE uid=:uid AND type=3', array(':uid'=>$_GPC['uid']));
    $product_list = pdo_fetchall('SELECT poster FROM '.tablename('xuan_mixloan_poster').' WHERE uid=:uid AND type=2', array(':uid'=>$_GPC['uid']));
}
include $this->template('agent');
?>