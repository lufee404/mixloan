<?phpdefined('IN_IA') or exit('Access Denied');global $_GPC, $_W;$config = $this->module['config'];(!empty($_GPC['op']))?$op=$_GPC['op']:$op='display';$openid = m('user')->getOpenid();$member = m('member')->getMember($openid);if ($config['vip_friend']) {    $agent = m('member')->checkAgent($member['id']);    if ($agent['code']!=1) {        header("location:{$this->createMobileUrl('vip', array('op'=>'buy'))}");    }}if ($member['status'] == '0') {    // 冻结    die("<!DOCTYPE html>    <html>        <head>            <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>            <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>        </head>        <body>        <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>账号已冻结，联系客服处理</h4></div></div></div>        </body>    </html>");}if ($op == "display") {    $page = $_GPC['page'] ? : 1;    $condition = "";    if ($_GPC['title']) {        $condition .= " AND name LIKE '%{$_GPC['title']}%'";    }    $top = pdo_fetchall("SELECT `id`,`title` FROM " . tablename('xuan_mixloan_friend') . " WHERE uniacid={$_W['uniacid']} AND top=1 {$condition} ORDER BY id desc");    $friend = pdo_fetchall("select * from" . tablename('xuan_mixloan_friend') . "where uniacid={$_W['uniacid']} AND top!=1 {$condition} order by id desc");    foreach ($friend as &$item) {        if ($item['head']) {            $item['head'] = json_decode($item['head'], 1);        }        $item['count_reply'] = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename("xuan_mixloan_friend_comment")." WHERE friend_id={$item['id']}");        switch ($item['tag']) {            case 1:                $data['question'][] = $item;            break;            case 2:                $data['news'][] = $item;            break;            case 3:                $data['share'][] = $item;            break;            default:            break;        }    }    unset($item);    include $this->template('friend/index');} elseif ($op == 'share') {    include $this->template('friend/share');} elseif ($op == 'b') {    $file = $_FILES['file'];    $name = $file['name'];    $type = strtolower(substr($name, strrpos($name, '.') + 1)); //得到文件类型，并且都转化成小写    $allow_type = array('jpg', 'jpeg', 'gif', 'png'); //定义允许上传的类型    $upload_path = "../attachment/images/"; //上传文件的存放路径    $lujing = "images/";    if (move_uploaded_file($file['tmp_name'], $upload_path . $file['name'])) {        $info = array('url' => $_W['attachurl'] . $lujing . $file['name'],);        die(json_encode($info));    } else {        echo "上传失败!";    }    die;} elseif ($op == 'add') {    if ($_GPC['content']) {        $imgs = '';        if (!empty($_GPC['imgs']) && $imgb = $_GPC['imgs']) {            foreach ($imgb as $item) {                if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $item, $match)) {                    $type = $match[2];                    $filename = date('YmdHis') . rand(1000, 9999) . ".{$type}";                    $new_file = ATTACHMENT_ROOT . $filename;                    $content = explode(',', $item);                    if (file_put_contents($new_file, base64_decode($content[1]))) {                        $imgs[] = $filename;                    }                }            }        }        if (is_array($imgs)) {            $imgs = json_encode($imgs);        }        $data = array('uniacid' => $_W['uniacid'], 'head' => $imgs, 'title' => $_GPC['title'], 'tag' => $_GPC['tagId'], 'name' => $_GPC['content'], 'openid' => $member['openid'], 'nickname' => $member['nickname'], 'avatar' => $member['avatar'], 'ctime' => time(),);        pdo_insert("xuan_mixloan_friend", $data);        $id = pdo_insertid();        if ($id) {            exit(json_encode(array('status' => 'OK', 'url' => $this->createMobileUrl('friend'))));        } else {            exit(json_encode(array('status' => '网络错误')));        }    }} elseif ($op == 'info') {    $info = pdo_fetch("select a.*,b.avatar,b.nickname from" . tablename('xuan_mixloan_friend') . " a LEFT JOIN ".tablename('xuan_mixloan_member')." b ON a.openid=b.openid where a.id={$_GPC['id']} and a.uniacid={$_W['uniacid']}");    $info['l_detail'] = pdo_fetch('SELECT COUNT(*) AS looks,SUM(praise) AS praise FROM '.tablename('xuan_mixloan_post_looks').' WHERE post_id=:post_id AND type=:type',array(':post_id'=>$info['id'],':type'=>'friend'));    $info['is_praise'] = pdo_fetch('SELECT id,praise FROM '.tablename('xuan_mixloan_post_looks').' WHERE post_id=:post_id AND type=:type AND openid=:openid', array(':post_id'=>$info['id'],':type'=>'friend', ':openid'=>$member['openid']));    if ($info['head']) {        $info['head'] = json_decode($info['head'], 1);    }    if (!$info['is_praise']) {        $insert = array(            'post_id'=>$info['id'],            'praise'=>0,            'type'=>'friend',            'openid'=>$member['openid']        );        pdo_insert('xuan_mixloan_post_looks', $insert);    }    $comments = pdo_fetchall('SELECT a.*,b.nickname,b.avatar FROM '.tablename('xuan_mixloan_friend_comment').' a LEFT JOIN '.tablename('xuan_mixloan_member').' b ON a.openid=b.openid WHERE a.friend_id=:friend_id AND a.uniacid=:uniacid ORDER BY a.floor ASC', array(':uniacid'=>$_W['uniacid'],':friend_id'=>$_GPC['id']));    foreach ($comments as &$comment) {        $count_comment = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename("xuan_mixloan_friend_comment")." WHERE parent_id=:parent_id", array(":parent_id"=>$comment['id']));        if ($count_comment) {            $comment['count_comment'] = $count_comment;            $comment['comments'] = pdo_fetchall('SELECT a.content,b.nickname FROM '.tablename('xuan_mixloan_friend_comment').' a LEFT JOIN '.tablename('xuan_mixloan_member').' b ON a.openid=b.openid WHERE a.parent_id=:parent_id ORDER BY a.floor ASC LIMIT 2', array(':parent_id'=>$comment['id']));        } else {            $comment['count_comment'] = 0;        }    }    unset($comment);    include $this->template('friend/info');} else if ($op == 'praise') {    $is_praise= pdo_fetch('SELECT id,praise FROM '.tablename('xuan_mixloan_post_looks').' WHERE post_id=:post_id AND type=:type AND openid=:openid', array(':post_id'=>$_GPC['id'],':type'=>'friend', ':openid'=>$member['openid']));    if ($is_praise['praise']) {        pdo_update('xuan_mixloan_post_looks', array('praise'=>0), array('id'=>$is_praise['id']));        message('nocollect', '', 'success');    } else {        pdo_update('xuan_mixloan_post_looks', array('praise'=>1), array('id'=>$is_praise['id']));        message('collected', '', 'success');    }} else if ($op == 'post_reply') {    //回复帖子    $friend_id = intval($_GPC['friend_id']);    $parent_id = intval($_GPC['parent_id']);    if (!$friend_id && !$parent_id) {        message("缺少id","","error");    }    $content = urlencode($_GPC['content']);    if (!$content) {        message("请输入回复内容","","error");    }    $floor = pdo_fetchcolumn('SELECT floor FROM '.tablename('xuan_mixloan_friend_comment').' WHERE friend_id=:friend_id AND parent_id=:parent_id AND uniacid=:uniacid ORDER BY floor DESC LIMIT 1', array(':uniacid'=>$_W['uniacid'],':friend_id'=>$friend_id, 'parent_id'=>$parent_id));    if (!$floor) $floor = 0;    if ($_GPC['imgs_url']) {        $pics = json_encode($_GPC['imgs_url']);    } else {        $pics = '';    }    $insert = array(        'uniacid' => $_W['uniacid'],        'openid' => $member['openid'],        'createtime' => time(),        'content' => $content,        'pics' => $pics,        'status' => 1,        'floor' => $floor+1,        'friend_id'=>$friend_id,        'parent_id'=>$parent_id,    );    pdo_insert('xuan_mixloan_friend_comment', $insert);    if ($friend_id) {        $relate_id = $friend_id;        $receive = pdo_fetchcolumn('SELECT openid FROM '.tablename("xuan_mixloan_friend")." WHERE id=:id", array(':id'=>$friend_id));        $desc = "{$member['nickname']}在您的帖子留言了";        $url = $_W['siteroot'] . 'app/' .$this->createMobileUrl('friend', array('op'=>'info', 'id'=>$friend_id));    } else {        $relate_id = $parent_id;        $desc = "{$member['nickname']}在您的回复留言了";        $url = $_W['siteroot'] . 'app/' .$this->createMobileUrl('friend', array('op'=>'comment_reply', 'id'=>$parent_id));        if($_GPC['reply_type'] == 'landlord') {            $receive = pdo_fetchcolumn('SELECT openid FROM '.tablename("xuan_mixloan_friend_comment")." WHERE id=:id", array(':id'=>$parent_id));        } else {            $receive = pdo_fetchcolumn('SELECT openid FROM '.tablename("xuan_mixloan_friend_comment")." WHERE id=:id", array(':id'=>$_GPC['reply_id']));        }          }    $insert = array(        'desc'=>$desc,        'time'=>time(),        'relate_id'=>$relate_id,        'send'=>$openid,        'receive'=>$receive,        'type'=>'friend_reply',        'url'=>$url    );    pdo_insert('xuan_mixloan_message', $insert);    message('回复成功','','success');} elseif ($op == 'center') {    $page = $_GPC['page'] ? : 0;    $pageIndex = max(1, $page);    $pageCount = 10;    $condition = " LIMIT " . ($pageIndex - 1) * $pageCount . ",{$pageCount}";    $notify = pdo_fetchall("SELECT a.*,b.avatar FROM " .tablename('xuan_mixloan_message')." a LEFT JOIN ".tablename("xuan_mixloan_member")." b ON a.send=b.openid WHERE a.receive=:openid AND a.type='friend_reply' ORDER BY a.id desc", array(':openid' => $member['openid']));    $list = pdo_fetchall('SELECT * FROM '.tablename('xuan_mixloan_friend').' WHERE openid=:openid ORDER BY id DESC', array(':openid'=>$member['openid']));    foreach ($list as $item) {        $item['replys'] = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('xuan_mixloan_friend_comment').' WHERE friend_id=:friend_id', array(':friend_id'=>$item['id']));        if ($item['head']) {            $item['head'] = json_decode($item['head'], 1);        }        switch ($item['tag']) {            case 1:                $data['question'][] = $item;            break;            case 2:                $data['news'][] = $item;            break;            case 3:                $data['share'][] = $item;            break;        }    }    unset($item);    $list = pdo_fetchall('SELECT b.* FROM '.tablename('xuan_mixloan_friend').' b RIGHT JOIN '.tablename('xuan_mixloan_friend_comment').' a ON a.friend_id=b.id WHERE a.openid=:openid GROUP BY a.friend_id ORDER BY a.id DESC', array(':openid'=>$member['openid']));    foreach ($list as $item) {        $item['replys'] = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('xuan_mixloan_friend_comment').' WHERE friend_id=:friend_id', array(':friend_id'=>$item['id']));        if ($item['head']) {            $item['head'] = json_decode($item['head'], 1);        }        switch ($item['tag']) {            case 1:                $replys['question'][] = $item;            break;            case 2:                $replys['news'][] = $item;            break;            case 3:                $replys['share'][] = $item;            break;        }    }    unset($item);    $looks = pdo_fetchall('SELECT b.*,a.praise FROM '.tablename('xuan_mixloan_friend').' b RIGHT JOIN '.tablename('xuan_mixloan_post_looks').' a ON a.post_id=b.id WHERE a.openid=:openid AND a.type=:type ORDER BY a.id DESC', array(':openid'=>$member['openid'], ':type'=>'friend'));    foreach ($looks as &$item) {        if ($item['head']) {            $item['head'] = json_decode($item['head'], 1);        }        $item['replys'] = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('xuan_mixloan_friend_comment').' WHERE friend_id=:friend_id', array(':friend_id'=>$item['id']));        if ($item['praise']) {            $praise[] = $item;        }    }    unset($item);    include $this->template('friend/center');} else if ($op == 'comment_reply') {    //回复的回复    $info = pdo_fetch("SELECT a.*,b.avatar,b.nickname FROM ".tablename("xuan_mixloan_friend_comment")." a LEFT JOIN ".tablename("xuan_mixloan_member")." b ON a.openid=b.openid WHERE a.id=:id", array(':id'=>$_GPC['id']));    $info['pics'] = json_decode($info['pics'],1);    $comments = pdo_fetchall('SELECT a.*,b.nickname,b.avatar FROM '.tablename('xuan_mixloan_friend_comment').' a LEFT JOIN '.tablename('xuan_mixloan_member').' b ON a.openid=b.openid WHERE a.parent_id=:parent_id AND a.uniacid=:uniacid ORDER BY a.floor ASC', array(':uniacid'=>$_W['uniacid'],':parent_id'=>$_GPC['id']));    include $this->template('friend/comment_reply');}else if ($op == 'upload') {    //上传图片    $setting = $_W['setting']['upload'][$type];    $result = array(        'jsonrpc' => '2.0',        'id' => 'id',        'error' => array('code' => 1, 'message'=>''),    );    load()->func('file');    if (empty($_FILES['file']['tmp_name'])) {        $binaryfile = file_get_contents('php://input', 'r');        if (!empty($binaryfile)) {            mkdirs(ATTACHMENT_ROOT . '/temp');            $tempfilename = random(5);            $tempfile = ATTACHMENT_ROOT . '/temp/' . $tempfilename;            if (file_put_contents($tempfile, $binaryfile)) {                $imagesize = @getimagesize($tempfile);                $imagesize = explode('/', $imagesize['mime']);                $_FILES['file'] = array(                    'name' => $tempfilename . '.' . $imagesize[1],                    'tmp_name' => $tempfile,                    'error' => 0,                );            }        }    }    if (!empty($_FILES['file']['name'])) {        if ($_FILES['file']['error'] != 0) {            $result['error']['message'] = '上传失败，请重试！';            die(json_encode($result));        }        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);        $ext = strtolower($ext);        $file = file_upload($_FILES['file']);        if (is_error($file)) {            $result['error']['message'] = $file['message'];            die(json_encode($result));        }        $pathname = $file['path'];        $fullname = ATTACHMENT_ROOT . '/' . $pathname;        $thumb = empty($setting['thumb']) ? 0 : 1;          $width = intval($setting['width']);             if ($thumb == 1 && $width > 0 && (!isset($_GPC['thumb']) || (isset($_GPC['thumb']) && !empty($_GPC['thumb'])))) {            $thumbnail = file_image_thumb($fullname, '', $width);            @unlink($fullname);            if (is_error($thumbnail)) {                $result['message'] = $thumbnail['message'];                die(json_encode($result));            } else {                $filename = pathinfo($thumbnail, PATHINFO_BASENAME);                $pathname = $thumbnail;                $fullname = ATTACHMENT_ROOT .'/'.$pathname;            }        }        $info = array(            'name' => $_FILES['file']['name'],            'ext' => $ext,            'filename' => $pathname,            'attachment' => $pathname,            'url' => tomedia($pathname),            'is_image' => 1,            'filesize' => filesize($fullname),        );        $size = getimagesize($fullname);        $info['width'] = $size[0];        $info['height'] = $size[1];                setting_load('remote');        if (!empty($_W['setting']['remote']['type'])) {            $remotestatus = file_remote_upload($pathname);            if (is_error($remotestatus)) {                $result['message'] = '元程附件上传失败，请检查配置并重新上传';                file_delete($pathname);                die(json_encode($result));            } else {                file_delete($pathname);                $info['url'] = tomedia($pathname);            }        }                pdo_insert('core_attachment', array(            'uniacid' => $uniacid,            'uid' => $_W['uid'],            'filename' => $_FILES['file']['name'],            'attachment' => $pathname,            'type' => $type == 'image' ? 1 : 2,            'createtime' => TIMESTAMP,        ));        die(json_encode($info));    } else {        $result['error']['message'] = '请选择要上传的图片！';        die(json_encode($result));    }} 