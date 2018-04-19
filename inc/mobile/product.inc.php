<?php  
defined('IN_IA') or exit('Access Denied');
global $_GPC,$_W;
$config = $this->module['config'];
(!empty($_GPC['op']))?$operation=$_GPC['op']:$operation='index';
$openid = m('user')->getOpenid();
$member = m('member')->getMember($openid);
if($operation=='index'){
    //首页
    $card = m('product')->getList([], ['type'=>1], FALSE);
    $loan = m('product')->getList([], ['type'=>2], FALSE);
    foreach ($card as &$row) {
        if ($row['done_reward_type'] == 1) {
            $row['ext_info']['init_one'] = $row['ext_info']['done_one_init_reward_money'] . '元';
            $row['ext_info']['mid_one'] = $row['ext_info']['done_one_mid_reward_money'] . '元';
            $row['ext_info']['height_one'] = $row['ext_info']['done_one_height_reward_money'] . '元';
            $row['ext_info']['init_two'] = $row['ext_info']['done_two_init_reward_money'] . '元';
            $row['ext_info']['mid_two'] = $row['ext_info']['done_two_mid_reward_money'] . '元';
            $row['ext_info']['height_two'] = $row['ext_info']['done_two_height_reward_money'] . '元';
        } else if ($row['done_reward_type'] == 2) {
            $row['ext_info']['init_one'] = $row['ext_info']['done_one_init_reward_per'] . '点';
            $row['ext_info']['mid_one'] = $row['ext_info']['done_one_mid_reward_per'] . '点';
            $row['ext_info']['height_one'] = $row['ext_info']['done_one_height_reward_per'] . '点';
            $row['ext_info']['init_two'] = $row['ext_info']['done_two_init_reward_per'] . '点';
            $row['ext_info']['mid_two'] = $row['ext_info']['done_two_mid_reward_per'] . '点';
            $row['ext_info']['height_two'] = $row['ext_info']['done_two_height_reward_per'] . '点';
        } else {
            $row['ext_info']['init_one'] = '无奖励';
            $row['ext_info']['mid_one'] = '无奖励';
            $row['ext_info']['height_one'] = '无奖励';
            $row['ext_info']['init_two'] = '无奖励';
            $row['ext_info']['mid_two'] = '无奖励';
            $row['ext_info']['height_two'] = '无奖励';
        }
    }
    unset($row);
    foreach ($loan as &$row) {
        if ($row['done_reward_type'] == 1) {
            $row['ext_info']['init_one'] = $row['ext_info']['done_one_init_reward_money'] . '元';
            $row['ext_info']['mid_one'] = $row['ext_info']['done_one_mid_reward_money'] . '元';
            $row['ext_info']['height_one'] = $row['ext_info']['done_one_height_reward_money'] . '元';
            $row['ext_info']['init_two'] = $row['ext_info']['done_two_init_reward_money'] . '元';
            $row['ext_info']['mid_two'] = $row['ext_info']['done_two_mid_reward_money'] . '元';
            $row['ext_info']['height_two'] = $row['ext_info']['done_two_height_reward_money'] . '元';
        } else if ($row['done_reward_type'] == 2) {
            $row['ext_info']['init_one'] = $row['ext_info']['done_one_init_reward_per'] . '点';
            $row['ext_info']['mid_one'] = $row['ext_info']['done_one_mid_reward_per'] . '点';
            $row['ext_info']['height_one'] = $row['ext_info']['done_one_height_reward_per'] . '点';
            $row['ext_info']['init_two'] = $row['ext_info']['done_two_init_reward_per'] . '点';
            $row['ext_info']['mid_two'] = $row['ext_info']['done_two_mid_reward_per'] . '点';
            $row['ext_info']['height_two'] = $row['ext_info']['done_two_height_reward_per'] . '点';
        } else {
            $row['ext_info']['init_one'] = '无奖励';
            $row['ext_info']['mid_one'] = '无奖励';
            $row['ext_info']['height_one'] = '无奖励';
            $row['ext_info']['init_two'] = '无奖励';
            $row['ext_info']['mid_two'] = '无奖励';
            $row['ext_info']['height_two'] = '无奖励';
        }
    }
    unset($row);
    include $this->template('product/index');
}  else if ($operation == 'getProduct') {
    //得到产品
    $banner = m('product')->getAdvs();
    $new = m('product')->getRecommends();
    $new = m('product')->packupItems($new);
    $card = m('product')->getList([], ['type'=>1], FALSE);
    $loan = m('product')->getList([], ['type'=>2], FALSE);
    $card = m('product')->packupItems($card);
    $loan = m('product')->packupItems($loan);
    $arr = array(
        'banner'=>$banner,
        'new'=>$new,
        'card'=>$card,
        'loan'=>$loan
    );
    show_json(1, $arr);
} else if ($operation == 'info') {
    //产品详情
    $agent = m('member')->checkAgent($member['id'], $config);
    if ($agent['code']==1) {
        $verify = 1;
    } else {
        $verify = 0;
    }
    $id = intval($_GPC['id']);
    $info = m('product')->getList([],['id'=>$id])[$id];
    if ($info['type'] == 1) {
        $poster_url = shortUrl($_W['siteroot'] . 'app/' .$this->createMobileUrl('product', array('op'=>'apply', 'id'=>$id, 'inviter'=>$member['id'])));
    } else {
        $poster_url = shortUrl($_W['siteroot'] . 'app/' .$this->createMobileUrl('loan', array('op'=>'apply', 'id'=>$info['relate_id'], 'inviter'=>$member['id'], 'pid'=>$info['id'])));
    }
    $poster_path = getNowHostUrl()."/addons/xuan_mixloan/data/poster/{$id}_{$member['id']}.png";
    $top_list = m('product')->getTopBonus($id);
    include $this->template('product/info');
} else if ($operation == 'allProduct') {
    //全部产品
    $inviter = intval($_GPC['inviter']);
    $credits = m('product')->getList(['id', 'name', 'relate_id', 'ext_info'], ['type'=>1]);
    foreach ($credits as $credit) {
        $id[] = $credit['relate_id'];
    }
    $cards = m('bank')->getCard(['id', 'ext_info'], ['id'=>$id]);
    foreach ($credits as $key => $credit) {
        $credits[$key]['v_name'] = $cards[$credit['relate_id']]['ext_info']['v_name'];
        $credits[$key]['card_pic'] = tomedia($cards[$credit['relate_id']]['ext_info']['pic']);
        $credits[$key]['tag'] = $cards[$credit['relate_id']]['ext_info']['tag'];
        $credits[$key]['speed'] = $cards[$credit['relate_id']]['ext_info']['speed'];
        $credits[$key]['limit'] = $cards[$credit['relate_id']]['ext_info']['limit'];
        $credits[$key]['intro'] = $cards[$credit['relate_id']]['ext_info']['intro'];
        $credit_thr[] = $credits[$key];
        if (count($credit_thr) > 2 || $key == max(array_keys($credits))) {
            $credit_all[] = $credit_thr;
            $credit_thr = [];
        }
    }
    $speed_loans = m('product')->getSpecialLoan(9);
    foreach ($speed_loans as $key => $loan) {
        $speed_loan_thr[] = $loan;
        if (count($speed_loan_thr) > 2 || $key == max(array_keys($speed_loans))) {
            $speed_loan_all[] = $speed_loan_thr;
            $speed_loan_thr = [];
        }
    }
    $large_loans = m('product')->getSpecialLoan(7);
    foreach ($large_loans as $key => $loan) {
        $large_loan_thr[] = $loan;
        if (count($large_loan_thr) > 2 || $key == max(array_keys($large_loans))) {
            $large_loan_all[] = $large_loan_thr;
            $large_loan_thr = [];
        }
    }
    $credits_blow = array_slice($credits, (int)count($credits)/2, ceil(count($credits)/5));
    $barrage = m('product')->getBarrage($credits, array_merge($speed_loans, $large_loans));
    include $this->template('product/allProduct');
} else if ($operation == 'apply') {
    //申请产品
    $id = intval($_GPC['id']);
    $inviter = intval($_GPC['inviter']);
    $info = m('product')->getList(['id', 'type', 'ext_info'],['id'=>$id])[$id];
    include $this->template('product/apply');
} else if ($operation == 'apply_submit') {
    //申请产品
    $id = intval($_GPC['id']);
    $inviter_uid = m('member')->getInviter(trim($_GPC['phone']));
    $inviter = $inviter_uid ? : intval($_GPC['inviter']);
    if ($inviter == $member['id']) {
        show_json(-1, [], "您不能自己邀请自己");
    }
    if ($id <= 0) {
        show_json(-1, [], "id为空");
    }
    $info = m('product')->getList(['id', 'name', 'type', 'relate_id'],['id'=>$id])[$id];
    if ($info['type'] == 1) {
        $pro = m('bank')->getCard(['id', 'ext_info'], ['id'=>$info['relate_id']])[$info['relate_id']];
    } else {
        $pro = m('loan')->getList(['id', 'ext_info'], ['id'=>$info['relate_id']])[$info['relate_id']];
    }
    if ($info['type'] == 1) {
        if(!trim($_GPC['name']) || !trim($_GPC['phone']) || !trim($_GPC['idcard'])) {
            show_json(-1, [], '资料不能为空');
        }
        $record = m('product')->getApplyList(['id'], ['pid'=>$id, 'phone'=>$_GPC['phone']]);
        if ($record) {
            show_json(-1, [], "您已经申请过啦");
        }
        if ($config['jdwx_open'] == 1) {
            // $res = m('jdwx')->jd_credit_three($config['jdwx_key'], trim($_GPC['name']), trim($_GPC['phone']), trim($_GPC['idcard']));
            // if ($res['code'] == -1) {
            //     show_json($res['code'], [], $res['msg']);
            // }
        }
        if ($inviter) {
            $inviter_openid = pdo_fetchcolumn("SELECT openid FROM ".tablename("xuan_mixloan_member") . " WHERE id=:id", array(':id'=>$inviter));
            $datam = array(
                "first" => array(
                    "value" => "尊敬的用户您好，有一个用户通过您的邀请申请了{$info['name']}，请及时跟进。",
                    "color" => "#173177"
                ) ,
                "keyword1" => array(
                    'value' => trim($_GPC['name']),
                    "color" => "#4a5077"
                ) ,
                "keyword2" => array(
                    'value' => date('Y-m-d H:i:s', time()),
                    "color" => "#4a5077"
                ) ,
                "remark" => array(
                    "value" => '点击查看详情',
                    "color" => "#4a5077"
                ) ,
            );
            $url = $_W['siteroot'] . 'app/' .$this->createMobileUrl('vip', array('op'=>'salary'));
            $account = WeAccount::create($_W['acid']);
            $account->sendTplNotice($inviter_openid, $config['tpl_notice1'], $datam, $url);
            if ($openid) {
                pdo_update('xuan_mixloan_member', array('phone'=>trim($_GPC['phone']), 'certno'=>trim($_GPC['idcard'])), array('id'=>$member['id']));
            }
            if (!$inviter_uid) {
                $check = m('member')->checkIfRelation($inviter, $member['id']);
                if ($check == false) {
                    $insert_i = array(
                        'uniacid' => $_W['uniacid'],
                        'uid' => $inviter,
                        'phone' => trim($_GPC['phone']),
                        'createtime' => time()
                    );
                    pdo_insert('xuan_mixloan_inviter', $insert_i);
                }
            }
            $status = 0;
        } else {
            $status = -2;
        }
        $insert = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $member['id'],
            'phone' => trim($_GPC['phone']),
            'certno' => trim($_GPC['idcard']),
            'realname' => trim($_GPC['name']),
            'pid' => $id,
            'inviter' => $inviter,
            're_bonus'=>0,
            'done_bonus'=>0,
            'extra_bonus'=>0,
            'status'=>$status,
            'createtime'=>time(),
            'degree'=>1,
        );
        pdo_insert('xuan_mixloan_product_apply', $insert);
        $inviter_info = m('member')->getInviterInfo($inviter);
        $second_inviter = m('member')->getInviter($inviter_info['phone'], $inviter_info['openid']);
        if ($second_inviter) {
            $insert['inviter'] = $second_inviter;
            $insert['degree'] = 2;
            pdo_insert('xuan_mixloan_product_apply', $insert);
        }
        $redirect_url = $pro['ext_info']['url'];
    } else {
        $redirect_url = $this->createMobileUrl('loan', array('op'=>'apply', 'id'=>$pro['id'], 'inviter'=>$inviter, 'pid'=>$info['id']));
    }
    show_json(1, $redirect_url);
} else if ($operation == 'customer') {
    //客户列表
    include $this->template('product/customer');
} else if ($operation == 'customer_list') {
    //客户列表接口
    $month = (int)$_GPC['month'];
    $year = (int)$_GPC['year'];
    $params['begin'] = "{$year}-{$month}-01";
    $params['inviter'] = $member['id'];
    $days_list = m('product')->getList(['id', 'name', 'ext_info'],['count_time'=>1]);
    $weeks_list = m('product')->getList(['id', 'name', 'type'],['count_time'=>7]);
    $months_list = m('product')->getList(['id', 'name', 'type'],['count_time'=>30]);
    $days_ids = m('product')->getIds($days_list);
    $weeks_ids = m('product')->getIds($weeks_list);
    $months_ids = m('product')->getIds($months_list);
    $applys = m('product')->getApplys($params);
    $days_count_list = m('product')->getNums($days_ids, $params, 1);
    $weeks_count_list = m('product')->getNums($weeks_ids, $params, 1);
    $months_count_list = m('product')->getNums($months_ids, $params, 1);
    $weeks_succ_list = m('product')->getNums($weeks_ids, $params, 2);
    $months_succ_list = m('product')->getNums($months_ids, $params, 2);
    $weeks_bonus_list = m('product')->getNums($weeks_ids, $params, 3);
    $months_bonus_list = m('product')->getNums($months_ids, $params, 3);
    foreach ($days_list as &$row) {
        $row['count_num_one'] = $days_count_list[$row['id']][1]['count'] ? : 0;
        $row['count_num_two'] = $days_count_list[$row['id']][2]['count'] ? : 0;
    }
    unset($row);
    foreach ($weeks_list as &$row) {
        $row['count_num_one'] = $weeks_count_list[$row['id']][1]['count'] ? : 0;
        $row['count_num_two'] = $weeks_count_list[$row['id']][2]['count'] ? : 0;
        if ($row['type'] == 1) {
            $row['succ_one'] = $weeks_succ_list[$row['id']][1]['count'] ? $weeks_succ_list[$row['id']][1]['count'].'位' : '0'.'位';
            $row['succ_two'] = $weeks_succ_list[$row['id']][2]['count'] ? $weeks_succ_list[$row['id']][2]['count'].'位' : '0'.'位';
        } else {
            $row['succ_one'] = $weeks_succ_list[$row['id']][1]['relate_money'] ? $weeks_succ_list[$row['id']][1]['relate_money'].'元' : '0'.'元';
            $row['succ_two'] = $weeks_succ_list[$row['id']][2]['relate_money'] ? $weeks_succ_list[$row['id']][2]['relate_money'].'元' : '0'.'元';
        }
        $row['count_bonus_one'] = $weeks_bonus_list[$row['id']][1]['bonus'] ? : 0;
        $row['count_bonus_two'] = $weeks_bonus_list[$row['id']][2]['bonus'] ? : 0;
    }
    unset($row);
    foreach ($months_list as &$row) {
        $row['count_num_one'] = $months_count_list[$row['id']][1]['count'] ? : 0;
        $row['count_num_two'] = $months_count_list[$row['id']][2]['count'] ? : 0;
        if ($row['type'] == 1) {
            $row['succ_one'] = $months_succ_list[$row['id']][1]['count'] ? $months_succ_list[$row['id']][1]['count'].'位' : '0'.'位';
            $row['succ_two'] = $months_succ_list[$row['id']][2]['count'] ? $months_succ_list[$row['id']][2]['count'].'位' : '0'.'位';
        } else {
            $row['succ_one'] = $months_succ_list[$row['id']][1]['relate_money'] ? $months_succ_list[$row['id']][1]['relate_money'].'元' : '0'.'元';
            $row['succ_two'] = $months_succ_list[$row['id']][2]['relate_money'] ? $months_succ_list[$row['id']][2]['relate_money'].'元' : '0'.'元';
        }
        $row['count_bonus_one'] = $months_bonus_list[$row['id']][1]['bonus'] ? : 0;
        $row['count_bonus_two'] = $months_bonus_list[$row['id']][2]['bonus'] ? : 0;
    }
    unset($row);
    $arr = ['days_list'=>array_values($days_list), 'months_list'=>array_values($months_list), 'weeks_list'=>array_values($weeks_list), 'applys'=>$applys];
    show_json(1, $arr);
}