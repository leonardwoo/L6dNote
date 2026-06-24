<?php
require_once 'libravatar.php';

// 获取请求中的标识符 (优先 email，其次 openid)
$id = $_GET['email'] ?? $_GET['openid'] ?? '';

// 如果没有提供核心标识符，返回 400 错误
if (empty($id)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: 'email' or 'openid' parameter is required.";
    exit;
}

// 收集可选的控制参数
$options = [];

// s 或 size: 头像尺寸 (1 - 512)
if (isset($_GET['s'])) $options['s'] = $_GET['s'];
if (isset($_GET['size'])) $options['s'] = $_GET['size'];

// d 或 default: 找不到头像时的后备方案 (如 mm, identicon, monsterid, wavatar, retro, 或者是自定图片URL)
if (isset($_GET['d'])) $options['d'] = $_GET['d'];
if (isset($_GET['default'])) $options['d'] = $_GET['default'];

// f 或 forcedefault: 强制使用默认头像 (y)
if (isset($_GET['f'])) $options['f'] = $_GET['f'];
if (isset($_GET['forcedefault'])) $options['f'] = $_GET['forcedefault'];

// 实例化服务
$libravatar = new Libravatar();

// 检测当前服务是处于 HTTP 还是 HTTPS，以便自动匹配 Libravatar 的安全模式
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443);
$libravatar->setSecure($isHttps);

// 生成目标 URL
$avatarUrl = $libravatar->generate($id, $options);

// 通过 302 临时重定向，将用户的图片请求导向最终的 Libravatar 节点
header("Location: " . $avatarUrl, true, 302);
exit;

?>