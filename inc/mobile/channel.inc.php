<?php  
defined('IN_IA') or exit('Access Denied');
global $_GPC,$_W;
$config = $this->module['config'];
(!empty($_GPC['op']))?$operation=$_GPC['op']:$operation='index';
$openid = m('user')->getOpenid();
$member = m('member')->getMember($openid);
if ($member['status'] == '0') {
    // 冻结
    die("<!DOCTYPE html>
    <html>
        <head>
            <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
            <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
        </head>
        <body>
        <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>账号已冻结，联系客服处理</h4></div></div></div>
        </body>
    </html>");
}
if($operation=='index'){
	//首页
	$advs = m('channel')->getAdvs();
	$subjects = array();
	$subjects_list = m('channel')->getSubjectList(['id', 'name', 'ext_info']);
    $count = 0;
    foreach ($subjects_list as $key => $value) {
        $count++;
        $subject[] = $value;
        if ($count==8) {
            $subjects[] = $subject;
            $subject = array();
            $count = 0;
        } else if ((count($subjects_list) - 1) == $key) {
            $subjects[] = $subject;
            $subject = array();
            $count = 0;
        }
    }
	$channel_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>1], 'sort DESC', 3);
    $channel_low_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>1], 'id DESC', 6);
	$credit_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>2], 'sort DESC', 3);
	$course_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>3], 'sort DESC', 3);
	$hot_list = m('channel')->getList(['id', 'title', 'apply_nums'], ['type'=>1, 'is_hot'=>1], 'sort DESC', 3);
	include $this->template('channel/index');
} elseif ($operation == 'credit_card') {
	//信用卡
	$advs = m('channel')->getAdvs();
	$subjects_list = m('channel')->getSubjectList(['id', 'name', 'ext_info']);
    $count = 0;
    foreach ($subjects_list as $key => $value) {
        $count++;
        $subject[] = $value;
        if ($count==8) {
            $subjects[] = $subject;
            $subject = array();
            $count = 0;
        }
        if ((count($subjects_list) - 1) == $key) {
            $subjects[] = $subject;
            $subject = array();
            $count = 0;
        }
    }
	$channel_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>1], 'sort DESC', 3);
    $credit_low_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>2], 'id DESC', 6);
	$credit_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>2], 'sort DESC', 3);
	$course_list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['type'=>3], 'sort DESC', 3);
	$hot_list = m('channel')->getList(['id', 'title', 'apply_nums'], ['type'=>2, 'is_hot'=>1], 'sort DESC', 3);
	include $this->template('channel/credit_card');
} elseif ($operation == 'course') {
	//新手教程
	$subjects_list = m('channel')->getSubjectList(['id', 'name', 'ext_info']);
    $count = 0;
    foreach ($subjects_list as $key => $value) {
        $count++;
        $subject[] = $value;
        if ($count==8) {
            $subjects[] = $subject;
            $subject = array();
            $count = 0;
        }
        if ((count($subjects_list) - 1) == $key) {
            $subjects[] = $subject;
            $subject = array();
            $count = 0;
        }
    }
	$course_list = m('channel')->getList(['id', 'title'], ['type'=>3], 'sort DESC');
	include $this->template('channel/course');
} else if ($operation == 'getNew') {
    //ajax获取新数据
    $type = intval($_GPC['type']);
    $offset = intval($_GPC['rollcount']);
    // $subject = m('channel')->getSubjectList(['id', 'ext_info'], ['type'=>$type], FALSE, 1, $offset);
    // if (empty($subject)) {
    // 	show_json(-1);
    // } else {
    // 	$ids = array_keys($subject);
    // 	$subjectRes = $subject[$ids[0]];
    // }
    // $list = m('channel')->getList(['id', 'title', 'subject_id', 'createtime', 'ext_info', 'apply_nums'], ['subject_id'=>$subjectRes['id']], 'sort DESC', 4);
    $list = m('channel')->getList(['id', 'title', 'subject_id', 'createtime', 'ext_info', 'apply_nums'], ['type'=>$type], 'id DESC', 4, $offset);
    if (empty($list)) {
        show_json(-1);
    }
    // $min_k = min(array_keys($list));
    // $list[$min_k]['stress'] = 1;
    // $list[$min_k]['ext_info']['pic'] = tomedia($subjectRes['ext_info']['pic']);
    show_json(1,array_values($list));
} else if ($operation == 'artical') {
	//详情
	if ($config['vip_channel']) {
		$agent = m('member')->checkAgent($member['id']);
		if ($agent['code']!=1) {
	        header("location:{$this->createMobileUrl('vip', array('op'=>'buy'))}");
	        exit();
		}
	}
	$id = intval($_GPC['id']);
	if (!$id) {
		message('id不能为空', '', 'error');
	}
	$res = m('channel')->getList([],['id'=>$id]);
	if (!$res) {
		message('抱歉，文章已不存在', '', 'error');
	}
	$item = $res[$id];
	if ($item['ext_info']['agent']) {
		$agent = m('member')->checkAgent($member['id']);
		if ($agent['code'] != 1) {
	        header("location:{$this->createMobileUrl('vip', array('op'=>'buy'))}");
	        exit();
		}
	}
	pdo_update('xuan_mixloan_channel', array('apply_nums'=>$item['apply_nums']+1), array('id'=>$item['id']));
	$item['praise'] = pdo_fetchcolumn('select count(1) from ' . tablename('xuan_mixloan_artical_praise') . '
		where relate_id=:relate_id', array(':relate_id' => $id));
	$praise = pdo_fetchcolumn('select count(1) from ' . tablename('xuan_mixloan_artical_praise') . '
		where relate_id=:relate_id and uid=:uid', array(':relate_id' => $id, ':uid' => $member['id'])) ? : 0;
	if (preg_match('/src=[\'\"]?([^\'\"]*)[\'\"]?/i', $item['ext_info']['content'], $result)) {
		$share_image = $result[1];
	} else {
		$share_image = tomedia($config['share_image']);
	}
	include $this->template('channel/artical');
} else if ($operation == 'search') {
	//搜索
	if ($_GPC['post'] == 1) {
		if ($_GPC['keyword']) {
			$keyword = trim($_GPC['keyword']);
		}
		$subjects = m('channel')->getSubjectList(['id'], ['name'=>$keyword]);
		if (!empty($subjects)) {
			$subjectIds = array_keys($subjects);
			$list =  m('channel')->getList(['id', 'title', 'apply_nums', 'createtime', 'ext_info'], ['subject_id'=>$subjectIds]);
		} else {
			$list = m('channel')->getList(['id', 'title', 'apply_nums', 'createtime', 'ext_info'], ['title'=>$keyword]);
		}
		if (!empty($list)) {
			show_json(1, array_values($list));
		}
		show_json(-1);
	}
	include $this->template('channel/search');
} else if ($operation == 'keyword') {
	//关键词联想
	if ($_GPC['keyword']) {
		$keyword = trim($_GPC['keyword']);
	}
	$list = m('channel')->getList(['id', 'title'], ['title'=>$keyword]);
	if (!empty($list)) {
		show_json(1, array_values($list));
	} else {
		show_json(-1);
	}
} else if ($operation == 'getCommendSubjects') {
	//随机出专题
	$subjects = m('channel')->getCommendSubjects();
	if (!empty($subjects)) {
		show_json(1, array_values($subjects));
	} else {
		show_json(-1);
	}
} else if ($operation == 'hot') {
	//热门文章
	$hot_list = m('channel')->getList([], ['is_hot'=>1]);
	include $this->template('channel/hot');
} else if ($operation == 'subject') {
	//专题
	$subject = m('channel')->getSubjectList(['id', 'name', 'ext_info'], ['id'=>$_GPC['id']]);
	if (empty($subject)) {
		message("专题已被删除啦");
	} else {
		$ids = array_keys($subject);
		$subjectRes = $subject[$ids[0]];
	}
	$list = m('channel')->getList(['id', 'title', 'createtime', 'ext_info', 'apply_nums'], ['subject_id'=>$subjectRes['id']]);
	include $this->template('channel/subject');
} else if ($operation == 'praise') {
	// 点赞
	$is_praise = intval($_GPC['is_praise']);
	$id        = intval($_GPC['id']);
	if ($is_praise == 1) {
		$cond = array();
		$cond['uid'] = $member['id'];
		$cond['relate_id'] = $id;
		pdo_delete('xuan_mixloan_artical_praise', $cond);
		show_json(1, [], '已取消点赞');
	} else {
		$insert = array();
		$insert['uid'] = $member['id'];
		$insert['relate_id'] = $id;
		pdo_insert('xuan_mixloan_artical_praise', $insert);
		show_json(1, [], '已点赞');
	}
}