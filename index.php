<?php
session_start();
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');
ini_set('max_input_vars', '10000');

$CONFIG = [
    'password' => '#Root@AnTor999',
    'max_upload' => 500,
    'theme' => 'dark',
];

if (isset($_POST['login']) && $_POST['password'] === $CONFIG['password']) {
    $_SESSION['auth'] = true;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    if (isset($_POST['login'])) {
        $login_error = true;
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>File Manager - Login</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);min-height:100vh;display:flex;align-items:center;justify-content:center;color:#fff}
.login-box{background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);border-radius:24px;padding:48px 40px;width:100%;max-width:400px;box-shadow:0 25px 60px rgba(0,0,0,0.5)}
.login-box h1{text-align:center;font-size:28px;margin-bottom:8px;background:linear-gradient(90deg,#00d2ff,#3a7bd5);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.login-box p{text-align:center;color:#888;margin-bottom:32px;font-size:14px}
.input-group{margin-bottom:20px;position:relative}
.input-group label{display:block;margin-bottom:8px;font-size:13px;color:#aaa;text-transform:uppercase;letter-spacing:1px}
.input-group input{width:100%;padding:14px 18px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:12px;color:#fff;font-size:16px;outline:none;transition:all .3s}
.input-group input:focus{border-color:#3a7bd5;box-shadow:0 0 20px rgba(58,123,213,0.2)}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#00d2ff,#3a7bd5);border:none;border-radius:12px;color:#fff;font-size:16px;font-weight:600;cursor:pointer;transition:all .3s;text-transform:uppercase;letter-spacing:1px}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(58,123,213,0.4)}
.error{background:rgba(255,59,48,0.15);border:1px solid rgba(255,59,48,0.3);color:#ff3b30;padding:12px;border-radius:10px;margin-bottom:20px;text-align:center;font-size:14px}
.icon{font-size:48px;text-align:center;margin-bottom:16px}
</style>
</head>
<body>
<div class="login-box">
<div class="icon">🔐</div>
<h1>File Manager</h1>
<p>Enter password to access</p>
<?php if (!empty($login_error)): ?>
<div class="error">⚠️ Invalid password. Try again.</div>
<?php endif; ?>
<form method="POST">
<div class="input-group">
<label>Password</label>
<input type="password" name="password" placeholder="Enter password..." autofocus required>
</div>
<button type="submit" name="login" value="1" class="btn">⚡ Access Dashboard</button>
</form>
</div>
</body>
</html>
<?php exit; }

$root = $_SERVER['DOCUMENT_ROOT'];
$current = isset($_GET['path']) ? $_GET['path'] : '/';
$current = str_replace(['../', '..\\'], '', $current);
$current = rtrim($current, '/');
if (empty($current)) $current = '/';

$fullPath = rtrim($root, '/') . '/' . ltrim($current, '/');

$action = $_GET['action'] ?? null;
$msg = '';
$msgType = '';

function deleteRecursive($target) {
    if (is_dir($target)) {
        $it = new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) rmdir($file->getRealPath());
            else unlink($file->getRealPath());
        }
        rmdir($target);
    } else {
        unlink($target);
    }
}

function copyDirRecursive($s, $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($s, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $rel = str_replace($s, '', $file->getRealPath());
        if ($file->isDir()) {
            @mkdir($d . $rel, 0755, true);
        } else {
            copy($file->getRealPath(), $d . $rel);
        }
    }
}

if ($action === 'create_folder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = basename($_POST['foldername'] ?? '');
    $path = rtrim($fullPath, '/') . '/' . $name;
    if (!empty($name) && !file_exists($path)) {
        mkdir($path, 0755, true);
        $msg = "Folder '$name' created successfully";
        $msgType = 'success';
    } else {
        $msg = "Failed to create folder";
        $msgType = 'error';
    }
}

if ($action === 'create_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = basename($_POST['filename'] ?? '');
    $path = rtrim($fullPath, '/') . '/' . $name;
    if (!empty($name) && !file_exists($path)) {
        file_put_contents($path, $_POST['content'] ?? '');
        $msg = "File '$name' created successfully";
        $msgType = 'success';
    } else {
        $msg = "Failed to create file";
        $msgType = 'error';
    }
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded = 0;
    $failed = 0;
    $folderPaths = json_decode($_POST['folder_paths'] ?? '[]', true);
    $folderFiles = $_FILES['folder_files'] ?? null;
    $regularFiles = $_FILES['files'] ?? null;
    $regCount = !empty($regularFiles['name'][0]) ? count($regularFiles['name']) : 0;
    for ($i = 0; $i < $regCount; $i++) {
        $name = $regularFiles['name'][$i];
        if (empty($name) || $regularFiles['error'][$i] !== UPLOAD_ERR_OK) continue;
        $dest = rtrim($fullPath, '/') . '/' . basename($name);
        if (move_uploaded_file($regularFiles['tmp_name'][$i], $dest)) {
            $uploaded++;
        } else {
            $failed++;
        }
    }
    if (!empty($folderFiles['name'][0]) && !empty($folderPaths)) {
        $foldCount = count($folderFiles['name']);
        for ($i = 0; $i < $foldCount; $i++) {
            if ($folderFiles['error'][$i] !== UPLOAD_ERR_OK) continue;
            $relPath = $folderPaths[$i] ?? basename($folderFiles['name'][$i]);
            $relPath = str_replace('\\', '/', $relPath);
            $parts = explode('/', $relPath);
            $fileName = array_pop($parts);
            if (empty($fileName)) continue;
            $subDir = rtrim($fullPath, '/');
            foreach ($parts as $part) {
                if ($part === '.' || $part === '..') continue;
                $subDir .= '/' . $part;
                if (!is_dir($subDir)) @mkdir($subDir, 0755, true);
            }
            $dest = $subDir . '/' . $fileName;
            if (move_uploaded_file($folderFiles['tmp_name'][$i], $dest)) {
                $uploaded++;
            } else {
                $failed++;
            }
        }
    } elseif (!empty($folderFiles['name'][0])) {
        $foldCount = count($folderFiles['name']);
        for ($i = 0; $i < $foldCount; $i++) {
            $name = $folderFiles['name'][$i];
            if (empty($name) || $folderFiles['error'][$i] !== UPLOAD_ERR_OK) continue;
            $relPath = str_replace('\\', '/', $name);
            $parts = explode('/', $relPath);
            $fileName = array_pop($parts);
            if (empty($fileName)) continue;
            $subDir = rtrim($fullPath, '/');
            foreach ($parts as $part) {
                if ($part === '.' || $part === '..') continue;
                $subDir .= '/' . $part;
                if (!is_dir($subDir)) @mkdir($subDir, 0755, true);
            }
            $dest = $subDir . '/' . $fileName;
            if (move_uploaded_file($folderFiles['tmp_name'][$i], $dest)) {
                $uploaded++;
            } else {
                $failed++;
            }
        }
    }
    $msg = "$uploaded file(s) uploaded" . ($failed > 0 ? " ($failed failed)" : "") . " successfully";
    $msgType = $uploaded > 0 ? 'success' : 'error';
}

if ($action === 'delete' && isset($_GET['file'])) {
    $target = rtrim($root, '/') . '/' . ltrim($_GET['file'], '/');
    if (file_exists($target)) {
        deleteRecursive($target);
        $msg = "Deleted successfully";
        $msgType = 'success';
    }
    header("Location: ?path=" . urlencode($current));
    exit;
}

if ($action === 'rename' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = rtrim($root, '/') . '/' . ltrim($_POST['oldname'] ?? '', '/');
    $newname = basename($_POST['newname'] ?? '');
    $newpath = dirname($old) . '/' . $newname;
    if (!empty($newname) && file_exists($old) && !file_exists($newpath)) {
        rename($old, $newpath);
        $msg = "Renamed successfully";
        $msgType = 'success';
    } else {
        $msg = "Failed to rename";
        $msgType = 'error';
    }
}

if ($action === 'chmod' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = rtrim($root, '/') . '/' . ltrim($_POST['filepath'] ?? '', '/');
    $perms = $_POST['permissions'] ?? '0644';
    if (file_exists($target) && chmod($target, octdec($perms))) {
        $msg = "Permissions updated to $perms";
        $msgType = 'success';
    } else {
        $msg = "Failed to update permissions";
        $msgType = 'error';
    }
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $filepath = $_POST['filepath'] ?? '';
    header('Content-Type: application/json; charset=utf-8');
    if (empty($filepath)) {
        echo json_encode(['success' => false, 'message' => 'No file path specified']);
        exit;
    }
    $target = rtrim($root, '/') . '/' . ltrim($filepath, '/');
    $realRoot = realpath($root);
    $realTarget = realpath(dirname($target));
    if ($realTarget === false || strpos($realTarget, $realRoot) !== 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid file path']);
        exit;
    }
    $content = $_POST['content'] ?? '';
    $postMax = ini_get('post_max_size');
    $postMaxBytes = returnBytes($postMax);
    if ($postMaxBytes > 0 && $_SERVER['CONTENT_LENGTH'] > $postMaxBytes) {
        echo json_encode(['success' => false, 'message' => 'File too large for server limit (' . $postMax . '). Increase post_max_size in php.ini']);
        exit;
    }
    if (file_put_contents($target, $content) !== false) {
        echo json_encode(['success' => true, 'message' => 'File saved (' . strlen($content) . ' bytes)']);
    } else {
        $err = error_get_last();
        $detail = $err ? $err['message'] : 'Unknown error';
        echo json_encode(['success' => false, 'message' => 'Write failed: ' . $detail]);
    }
    exit;
}

if ($action === 'list_folders' && isset($_GET['dir'])) {
    header('Content-Type: application/json; charset=utf-8');
    $dir = rtrim($root, '/') . '/' . ltrim($_GET['dir'], '/');
    $dir = rtrim($dir, '/');
    if (!is_dir($dir)) {
        echo json_encode(['path' => '/', 'folders' => []]);
        exit;
    }
    $items = [];
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $itemPath = $dir . '/' . $item;
        if (is_dir($itemPath)) {
            $relPath = ltrim(str_replace(rtrim($root, '/'), '', $itemPath), '/');
            $items[] = ['name' => $item, 'path' => '/' . $relPath];
        }
    }
    usort($items, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
    $parentPath = dirname($dir);
    $parentRel = ltrim(str_replace(rtrim($root, '/'), '', $parentPath), '/');
    if ($parentRel === $dir || empty($parentRel)) $parentRel = '/';
    else $parentRel = '/' . $parentRel;
    echo json_encode(['path' => '/' . ltrim(str_replace(rtrim($root, '/'), '', $dir), '/'), 'parent' => $parentRel, 'folders' => $items]);
    exit;
}

if ($action === 'read_file' && isset($_GET['file'])) {
    $target = rtrim($root, '/') . '/' . ltrim($_GET['file'], '/');
    if (file_exists($target) && is_file($target)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo file_get_contents($target);
        exit;
    }
    header('HTTP/1.0 404 Not Found');
    exit;
}

if ($action === 'preview' && isset($_GET['file'])) {
    $target = rtrim($root, '/') . '/' . ltrim($_GET['file'], '/');
    if (file_exists($target) && is_file($target)) {
        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
            'svg'=>'image/svg+xml','bmp'=>'image/bmp','ico'=>'image/x-icon','webp'=>'image/webp','avif'=>'image/avif',
            'mp4'=>'video/mp4','webm'=>'video/webm','avi'=>'video/x-msvideo','mov'=>'video/quicktime','mkv'=>'video/x-matroska','flv'=>'video/x-flv',
            'mp3'=>'audio/mpeg','wav'=>'audio/wav','ogg'=>'audio/ogg','flac'=>'audio/flac','aac'=>'audio/aac',
            'pdf'=>'application/pdf',
            'txt'=>'text/plain','md'=>'text/plain','log'=>'text/plain','csv'=>'text/csv',
            'json'=>'application/json','xml'=>'application/xml',
            'php'=>'text/plain','html'=>'text/html','htm'=>'text/html',
            'css'=>'text/css','js'=>'application/javascript','py'=>'text/x-python',
            'java'=>'text/x-java','rb'=>'text/x-ruby','sql'=>'text/x-sql',
            'sh'=>'text/x-shellscript','yml'=>'text/yaml','yaml'=>'text/yaml',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($target));
        header('Content-Disposition: inline; filename="' . basename($target) . '"');
        readfile($target);
        exit;
    }
    header('HTTP/1.0 404 Not Found');
    exit;
}

if ($action === 'create_zip' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = array_filter(explode("\n", trim($_POST['files'] ?? '')));
    $zipName = basename($_POST['zipname'] ?? 'archive.zip');
    if (!preg_match('/\.zip$/i', $zipName)) $zipName .= '.zip';
    $zipPath = rtrim($fullPath, '/') . '/' . $zipName;
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $added = 0;
            foreach ($files as $f) {
                $target = rtrim($root, '/') . '/' . ltrim(trim($f), '/');
                if (file_exists($target)) {
                    if (is_dir($target)) {
                        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS));
                        foreach ($it as $file) {
                            $arcPath = ltrim(str_replace(rtrim($fullPath, '/'), '', $file->getRealPath()), '/');
                            $zip->addFile($file->getRealPath(), $arcPath);
                            $added++;
                        }
                    } else {
                        $arcPath = ltrim(str_replace(rtrim($fullPath, '/'), '', $target), '/');
                        $zip->addFile($target, $arcPath);
                        $added++;
                    }
                }
            }
            $zip->close();
            $msg = "Created '$zipName' with $added file(s)";
            $msgType = 'success';
        } else {
            $msg = "Failed to create zip archive";
            $msgType = 'error';
        }
    } else {
        $msg = "ZipArchive not available";
        $msgType = 'error';
    }
}

if ($action === 'download' && isset($_GET['file'])) {
    $target = rtrim($root, '/') . '/' . ltrim($_GET['file'], '/');
    if (file_exists($target) && is_file($target)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($target) . '"');
        header('Content-Length: ' . filesize($target));
        readfile($target);
        exit;
    }
}

if ($action === 'copy' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $src = rtrim($root, '/') . '/' . ltrim($_POST['source'] ?? '', '/');
    $destDir = !empty($_POST['dest']) ? rtrim($root, '/') . '/' . ltrim($_POST['dest'], '/') : rtrim($fullPath, '/');
    $dst = rtrim($destDir, '/') . '/' . basename($src);
    if (file_exists($src) && !file_exists($dst)) {
        if (is_dir($src)) {
            copyDirRecursive($src, $dst);
        } else {
            copy($src, $dst);
        }
        $msg = "Copied to " . ltrim(str_replace(rtrim($root, '/'), '', $destDir), '/') . " successfully";
        $msgType = 'success';
    } else {
        $msg = file_exists($dst) ? "Destination already exists" : "Failed to copy";
        $msgType = 'error';
    }
}

if ($action === 'move' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $src = rtrim($root, '/') . '/' . ltrim($_POST['source'] ?? '', '/');
    $destDir = !empty($_POST['dest']) ? rtrim($root, '/') . '/' . ltrim($_POST['dest'], '/') : rtrim($fullPath, '/');
    $dst = rtrim($destDir, '/') . '/' . basename($src);
    if (file_exists($src) && !file_exists($dst)) {
        rename($src, $dst);
        $msg = "Moved successfully";
        $msgType = 'success';
    } else {
        $msg = "Failed to move";
        $msgType = 'error';
    }
}

if ($action === 'extract' && isset($_GET['file'])) {
    $target = rtrim($root, '/') . '/' . ltrim($_GET['file'], '/');
    if (file_exists($target) && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($target) === true) {
            $zip->extractTo(rtrim($fullPath, '/'));
            $zip->close();
            $msg = "Extracted successfully";
            $msgType = 'success';
        } else {
            $msg = "Failed to extract archive";
            $msgType = 'error';
        }
    }
}

if ($action === 'bulk_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = array_filter(explode("\n", trim($_POST['files'] ?? '')));
    $deleted = 0;
    foreach ($files as $f) {
        $target = rtrim($root, '/') . '/' . ltrim(trim($f), '/');
        if (file_exists($target)) {
            deleteRecursive($target);
            $deleted++;
        }
    }
    $msg = "Deleted $deleted item(s) successfully";
    $msgType = 'success';
    header("Location: ?path=" . urlencode($current));
    exit;
}

if ($action === 'bulk_chmod' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = array_filter(explode("\n", trim($_POST['files'] ?? '')));
    $perms = $_POST['permissions'] ?? '0644';
    $changed = 0;
    foreach ($files as $f) {
        $target = rtrim($root, '/') . '/' . ltrim(trim($f), '/');
        if (file_exists($target) && chmod($target, octdec($perms))) {
            $changed++;
        }
    }
    $msg = "Permissions updated for $changed item(s) to $perms";
    $msgType = 'success';
}

if ($action === 'bulk_copy' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = array_filter(explode("\n", trim($_POST['files'] ?? '')));
    $destDir = !empty($_POST['dest']) ? rtrim($root, '/') . '/' . ltrim($_POST['dest'], '/') : rtrim($fullPath, '/');
    $copied = 0;
    foreach ($files as $f) {
        $src = rtrim($root, '/') . '/' . ltrim(trim($f), '/');
        $dst = rtrim($destDir, '/') . '/' . basename($src);
        if (file_exists($src) && !file_exists($dst)) {
            if (is_dir($src)) {
                copyDirRecursive($src, $dst);
            } else {
                copy($src, $dst);
            }
            $copied++;
        }
    }
    $msg = "Copied $copied item(s) successfully";
    $msgType = 'success';
}

if ($action === 'bulk_move' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = array_filter(explode("\n", trim($_POST['files'] ?? '')));
    $destDir = !empty($_POST['dest']) ? rtrim($root, '/') . '/' . ltrim($_POST['dest'], '/') : rtrim($fullPath, '/');
    $moved = 0;
    foreach ($files as $f) {
        $src = rtrim($root, '/') . '/' . ltrim(trim($f), '/');
        $dst = rtrim($destDir, '/') . '/' . basename($src);
        if (file_exists($src) && !file_exists($dst)) {
            rename($src, $dst);
            $moved++;
        }
    }
    $msg = "Moved $moved item(s) successfully";
    $msgType = 'success';
}

if ($action === 'bulk_extract' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = array_filter(explode("\n", trim($_POST['files'] ?? '')));
    $extracted = 0;
    if (class_exists('ZipArchive')) {
        foreach ($files as $f) {
            $target = rtrim($root, '/') . '/' . ltrim(trim($f), '/');
            if (file_exists($target)) {
                $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
                if ($ext === 'zip') {
                    $zip = new ZipArchive();
                    if ($zip->open($target) === true) {
                        $zip->extractTo(rtrim($fullPath, '/'));
                        $zip->close();
                        $extracted++;
                    }
                }
            }
        }
    }
    $msg = "Extracted $extracted archive(s) successfully";
    $msgType = 'success';
}

function formatSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B','KB','MB','GB','TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function returnBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

function getFileIcon($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $icons = [
        'php'=>'🐘','html'=>'🌐','htm'=>'🌐','css'=>'🎨','js'=>'⚡','json'=>'📋','xml'=>'📄',
        'txt'=>'📝','md'=>'📝','log'=>'📝','csv'=>'📊','xls'=>'📊','xlsx'=>'📊','doc'=>'📄','docx'=>'📄','pdf'=>'📕',
        'jpg'=>'🖼️','jpeg'=>'🖼️','png'=>'🖼️','gif'=>'🖼️','svg'=>'🖼️','bmp'=>'🖼️','ico'=>'🖼️','webp'=>'🖼️',
        'mp3'=>'🎵','wav'=>'🎵','mp4'=>'🎬','avi'=>'🎬','mov'=>'🎬','mkv'=>'🎬','flv'=>'🎬',
        'zip'=>'📦','rar'=>'📦','7z'=>'📦','tar'=>'📦','gz'=>'📦',
        'py'=>'🐍','java'=>'☕','rb'=>'💎','go'=>'🔵','rs'=>'🦀','c'=>'🔧','cpp'=>'🔧','h'=>'🔧',
        'sql'=>'🗃️','db'=>'🗃️','sqlite'=>'🗃️',
        'sh'=>'⚙️','bash'=>'⚙️','yml'=>'⚙️','yaml'=>'⚙️','ini'=>'⚙️','conf'=>'⚙️',
        'exe'=>'💿','dll'=>'💿','so'=>'💿',
        'ttf'=>'🔤','otf'=>'🔤','woff'=>'🔤','woff2'=>'🔤',
    ];
    return $icons[$ext] ?? '📄';
}

function isEditable($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $editable = ['php','html','htm','css','js','json','xml','txt','md','log','csv','sql','py','java','rb','go','rs','c','cpp','h','sh','yml','yaml','ini','conf','htaccess','env','jsx','tsx','ts','vue','svelte','scss','less','sass','styl','twig','blade.php','ejs','hbs','phtml','inc','php4','php5','php7','php8','asp','aspx','jsp','cgi','pl','pm','lua','r','sas','ps1','psm1','bat','cmd','reg','inf','cfg','config','editorconfig','gitignore','dockerignore','env.example','Makefile','Dockerfile','Vagrantfile','Gemfile','Rakefile','Procfile','CMakeLists.txt','LICENSE','README','CONTRIBUTING','CHANGELOG'];
    return in_array($ext, $editable);
}

function isArchive($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, ['zip','rar','7z','tar','gz']);
}

if (!is_dir($fullPath)) {
    $current = '/';
    $fullPath = $root;
}

$items = [];
foreach (scandir($fullPath) as $item) {
    if ($item === '.' || $item === '..') continue;
    $itemPath = rtrim($fullPath, '/') . '/' . $item;
    $relPath = rtrim($current, '/') . '/' . $item;
    $isDir = is_dir($itemPath);
    $size = $isDir ? '-' : formatSize(filesize($itemPath));
    $mtime = date('Y-m-d H:i', filemtime($itemPath));
    $perms = substr(sprintf('%o', fileperms($itemPath)), -4);
    $items[] = [
        'name' => $item,
        'path' => $relPath,
        'isDir' => $isDir,
        'size' => $size,
        'mtime' => $mtime,
        'perms' => $perms,
        'ext' => $isDir ? 'folder' : strtolower(pathinfo($item, PATHINFO_EXTENSION)),
        'isArch' => !$isDir && isArchive($item),
        'isEditable' => !$isDir && isEditable($item),
    ];
}

usort($items, function($a, $b) {
    if ($a['isDir'] && !$b['isDir']) return -1;
    if (!$a['isDir'] && $b['isDir']) return 1;
    return strcasecmp($a['name'], $b['name']);
});

$parts = array_filter(explode('/', $current));
$breadcrumbs = [['name' => 'Home', 'path' => '/']];
$build = '';
foreach ($parts as $part) {
    $build .= '/' . $part;
    $breadcrumbs[] = ['name' => $part, 'path' => $build];
}

function getDirSize($path) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile()) $size += $file->getSize();
    }
    return $size;
}

$totalSize = formatSize(getDirSize($fullPath));
$totalFiles = count(array_filter($items, function($i) { return !$i['isDir']; }));
$totalFolders = count(array_filter($items, function($i) { return $i['isDir']; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>⚡ File Manager</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ext-searchbox.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ext-language_tools.js"></script>
<style>
:root {
    --bg-primary: #0d1117;
    --bg-secondary: #161b22;
    --bg-tertiary: #21262d;
    --border: #30363d;
    --text-primary: #e6edf3;
    --text-secondary: #8b949e;
    --accent: #58a6ff;
    --accent-hover: #79c0ff;
    --success: #3fb950;
    --danger: #f85149;
    --warning: #d29922;
    --gradient-start: #667eea;
    --gradient-end: #764ba2;
    --sidebar-width: 280px;
    --header-height: 64px;
}

*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,sans-serif;background:var(--bg-primary);color:var(--text-primary);min-height:100vh;overflow-x:hidden}

.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-width);height:100vh;background:var(--bg-secondary);border-right:1px solid var(--border);z-index:100;transition:transform .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column}
.sidebar-header{padding:20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.sidebar-header .logo{width:40px;height:40px;background:linear-gradient(135deg,var(--gradient-start),var(--gradient-end));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
.sidebar-header h2{font-size:16px;font-weight:600;background:linear-gradient(90deg,var(--gradient-start),var(--gradient-end));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar-nav{flex:1;overflow-y:auto;padding:12px}
.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:10px 16px;color:var(--text-secondary);text-decoration:none;border-radius:8px;transition:all .2s;font-size:14px;margin-bottom:2px}
.sidebar-nav a:hover,.sidebar-nav a.active{background:var(--bg-tertiary);color:var(--text-primary)}
.sidebar-nav a span.icon{font-size:18px;width:24px;text-align:center}
.sidebar-stats{padding:16px 20px;border-top:1px solid var(--border);font-size:12px;color:var(--text-secondary)}
.sidebar-stats div{margin-bottom:4px;display:flex;justify-content:space-between}

.main-content{margin-left:var(--sidebar-width);min-height:100vh;transition:margin-left .3s}

.header{height:var(--header-height);background:var(--bg-secondary);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 24px;gap:16px;position:sticky;top:0;z-index:50}
.menu-toggle{display:none;background:none;border:none;color:var(--text-primary);font-size:24px;cursor:pointer;padding:4px}

.breadcrumbs{display:flex;align-items:center;gap:4px;flex:1;overflow-x:auto;white-space:nowrap;padding:4px 0}
.breadcrumbs a{color:var(--text-secondary);text-decoration:none;font-size:14px;padding:4px 8px;border-radius:6px;transition:all .2s}
.breadcrumbs a:hover{color:var(--accent);background:rgba(88,166,255,.1)}
.breadcrumbs .sep{color:var(--text-secondary);opacity:.5;font-size:12px}

.header-actions{display:flex;gap:8px;align-items:center}

.btn{padding:8px 16px;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .2s;text-decoration:none;white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,var(--gradient-start),var(--gradient-end));color:#fff}
.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-secondary{background:var(--bg-tertiary);color:var(--text-primary);border:1px solid var(--border)}
.btn-secondary:hover{background:var(--border)}
.btn-danger{background:rgba(248,81,73,.15);color:var(--danger);border:1px solid rgba(248,81,73,.3)}
.btn-danger:hover{background:rgba(248,81,73,.25)}
.btn-success{background:rgba(63,185,80,.15);color:var(--success);border:1px solid rgba(63,185,80,.3)}
.btn-success:hover{background:rgba(63,185,80,.25)}
.btn-warning{background:rgba(210,153,34,.15);color:var(--warning);border:1px solid rgba(210,153,34,.3)}
.btn-warning:hover{background:rgba(210,153,34,.25)}
.btn-sm{padding:6px 10px;font-size:12px}
.btn-icon{padding:6px 8px;min-width:32px;justify-content:center}

.content{padding:24px}

.stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;transition:all .2s}
.stat-card:hover{border-color:var(--accent);transform:translateY(-2px)}
.stat-card .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px}
.stat-card .stat-icon.blue{background:rgba(88,166,255,.15)}
.stat-card .stat-icon.green{background:rgba(63,185,80,.15)}
.stat-card .stat-icon.purple{background:rgba(118,75,162,.15)}
.stat-card .stat-icon.orange{background:rgba(210,153,34,.15)}
.stat-card .stat-info h3{font-size:24px;font-weight:700}
.stat-card .stat-info p{font-size:13px;color:var(--text-secondary)}

.toolbar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center}
.search-box{flex:1;min-width:200px;position:relative}
.search-box input{width:100%;padding:10px 16px 10px 40px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;color:var(--text-primary);font-size:14px;outline:none;transition:border-color .2s}
.search-box input:focus{border-color:var(--accent)}
.search-box::before{content:'🔍';position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:14px}

.file-table-wrap{background:var(--bg-secondary);border:1px solid var(--border);border-radius:12px;overflow:hidden}
.file-table{width:100%;border-collapse:collapse}
.file-table th{background:var(--bg-tertiary);padding:12px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);font-weight:600;border-bottom:1px solid var(--border);cursor:pointer;user-select:none;white-space:nowrap}
.file-table th:hover{color:var(--text-primary)}
.file-table td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:14px;vertical-align:middle}
.file-table tr:last-child td{border-bottom:none}
.file-table tr:hover{background:rgba(88,166,255,.04)}
.file-table tr.selected{background:rgba(88,166,255,.1) !important}

.file-name{display:flex;align-items:center;gap:12px}
.file-name .icon{font-size:24px;flex-shrink:0}
.file-name .name{color:var(--text-primary);text-decoration:none;font-weight:500;transition:color .2s}
.file-name .name:hover{color:var(--accent)}
.file-name .name.dir{color:var(--accent)}
.file-meta{color:var(--text-secondary);font-size:13px}

.file-actions{display:flex;gap:4px;flex-wrap:wrap}
.file-actions button,.file-actions a{padding:4px 8px;background:none;border:1px solid transparent;border-radius:6px;color:var(--text-secondary);cursor:pointer;font-size:14px;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px}
.file-actions button:hover,.file-actions a:hover{background:var(--bg-tertiary);border-color:var(--border);color:var(--text-primary)}
.file-actions .del:hover{background:rgba(248,81,73,.15);color:var(--danger);border-color:rgba(248,81,73,.3)}

.empty-state{text-align:center;padding:60px 20px;color:var(--text-secondary)}
.empty-state .icon{font-size:48px;margin-bottom:16px}
.empty-state h3{font-size:18px;margin-bottom:8px;color:var(--text-primary)}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center;padding:20px}
.modal-overlay.active{display:flex}
.modal{background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.5)}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border)}
.modal-header h3{font-size:18px}
.modal-close{background:none;border:none;color:var(--text-secondary);font-size:24px;cursor:pointer;padding:4px;line-height:1}
.modal-close:hover{color:var(--text-primary)}
.modal-body{padding:24px}
.form-group{margin-bottom:16px}
.form-group label{display:block;margin-bottom:6px;font-size:13px;color:var(--text-secondary);font-weight:500}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 14px;background:var(--bg-tertiary);border:1px solid var(--border);border-radius:8px;color:var(--text-primary);font-size:14px;font-family:inherit;outline:none;transition:border-color .2s}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--accent)}
.form-group textarea{min-height:200px;resize:vertical;font-family:'SF Mono',Monaco,'Cascadia Code',monospace;font-size:13px;line-height:1.6}
.form-group input[type="file"]{background:var(--bg-tertiary);border:2px dashed var(--border);padding:20px;text-align:center;cursor:pointer}
.form-group input[type="file"]:hover{border-color:var(--accent)}
.modal-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--border)}

.toast{position:fixed;bottom:24px;right:24px;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:500;z-index:2000;transform:translateY(100px);opacity:0;transition:all .3s cubic-bezier(.4,0,.2,1);max-width:400px;box-shadow:0 10px 40px rgba(0,0,0,.3)}
.toast.show{transform:translateY(0);opacity:1}
.toast.success{background:var(--success);color:#fff}
.toast.error{background:var(--danger);color:#fff}

.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}
.sidebar-overlay.open{display:block}

.upload-zone{border:2px dashed var(--border);border-radius:12px;padding:32px;text-align:center;transition:all .3s;cursor:pointer;margin-bottom:16px}
.upload-zone:hover,.upload-zone.dragover{border-color:var(--accent);background:rgba(88,166,255,.05)}
.upload-zone .icon{font-size:36px;margin-bottom:8px}
.upload-zone p{color:var(--text-secondary);font-size:14px}
.upload-zone .browse{color:var(--accent);font-weight:600}

.checkbox-wrap{display:flex;align-items:center;gap:8px}
.checkbox-wrap input[type="checkbox"]{width:16px;height:16px;accent-color:var(--accent);cursor:pointer}

.context-menu{position:fixed;background:var(--bg-secondary);border:1px solid var(--border);border-radius:10px;padding:6px;z-index:500;min-width:180px;box-shadow:0 10px 40px rgba(0,0,0,.4);display:none}
.context-menu.show{display:block}
.context-menu button{width:100%;display:flex;align-items:center;gap:10px;padding:8px 12px;background:none;border:none;color:var(--text-primary);font-size:13px;cursor:pointer;border-radius:6px;text-align:left}
.context-menu button:hover{background:var(--bg-tertiary)}
.context-menu button.danger{color:var(--danger)}
.context-menu .divider{height:1px;background:var(--border);margin:4px 0}

.editor-wrap{display:none;position:fixed;inset:0;z-index:2000;background:#1e1e1e;flex-direction:column}
.editor-wrap.active{display:flex}
.editor-header{display:flex;align-items:center;padding:0 16px;height:48px;background:#252526;border-bottom:1px solid #3c3c3c;gap:8px;flex-shrink:0}
.editor-header .filename{flex:1;font-weight:600;font-size:13px;color:#ccc;font-family:'SF Mono',Monaco,'Cascadia Code',monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.editor-header .editor-info{font-size:11px;color:#858585;white-space:nowrap;display:flex;align-items:center;gap:12px}
.editor-toolbar{display:flex;align-items:center;padding:0 12px;height:36px;background:#2d2d2d;border-bottom:1px solid #3c3c3c;gap:6px;flex-shrink:0;overflow-x:auto}
.editor-toolbar .sep{width:1px;height:20px;background:#3c3c3c;flex-shrink:0}
.editor-toolbar select,.editor-toolbar button{padding:4px 10px;background:#3c3c3c;border:1px solid #505050;border-radius:4px;color:#ccc;font-size:12px;cursor:pointer;white-space:nowrap}
.editor-toolbar select:hover,.editor-toolbar button:hover{background:#505050}
.editor-toolbar select{appearance:auto}
.editor-toolbar button.active{background:var(--accent);border-color:var(--accent);color:#fff}
#aceEditor{flex:1;width:100%;min-height:0;overflow:hidden;position:relative}

.bulk-actions-bar{position:fixed;bottom:0;left:var(--sidebar-width);right:0;background:var(--bg-secondary);border-top:1px solid var(--border);padding:12px 24px;display:none;align-items:center;gap:12px;z-index:200;box-shadow:0 -4px 20px rgba(0,0,0,.3);animation:slideUp .2s ease}
.bulk-actions-bar.show{display:flex}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.bulk-actions-bar .sel-count{font-size:14px;font-weight:600;color:var(--accent);white-space:nowrap;display:flex;align-items:center;gap:6px}
.bulk-actions-bar .bulk-btns{display:flex;gap:6px;flex-wrap:wrap;flex:1;justify-content:flex-end}

.select-mode-banner{display:none;background:rgba(88,166,255,.1);border-bottom:1px solid rgba(88,166,255,.2);padding:8px 24px;text-align:center;font-size:13px;color:var(--accent);align-items:center;justify-content:center;gap:12px}
.select-mode-banner.show{display:flex}
.select-mode-banner .exit-sel{cursor:pointer;text-decoration:underline;margin-left:8px}

.touch-select-hint{display:none;position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.85);color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;z-index:150;pointer-events:none;animation:fadeInOut 2.5s ease forwards}
@keyframes fadeInOut{0%{opacity:0;transform:translateX(-50%) translateY(20px)}15%{opacity:1;transform:translateX(-50%) translateY(0)}85%{opacity:1;transform:translateX(-50%) translateY(0)}100%{opacity:0;transform:translateX(-50%) translateY(-10px)}}

@media(max-width:768px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.open{transform:translateX(0)}
    .main-content{margin-left:0}
    .menu-toggle{display:block}
    .content{padding:16px}
    .stats-bar{grid-template-columns:repeat(2,1fr);gap:10px}
    .stat-card{padding:14px}
    .stat-card .stat-icon{width:40px;height:40px;font-size:20px}
    .stat-card .stat-info h3{font-size:18px}
    .header{padding:0 16px}
    .file-table th:nth-child(3),.file-table td:nth-child(3),
    .file-table th:nth-child(4),.file-table td:nth-child(4){display:none}
    .file-name .name{font-size:13px}
    .file-meta{font-size:12px}
    .toolbar{flex-direction:column}
    .search-box{min-width:100%}
    .header-actions .btn span.label{display:none}
    .bulk-actions-bar{left:0;padding:12px 16px}
    .bulk-actions-bar .bulk-btns{gap:4px}
    .bulk-actions-bar .bulk-btns .btn span.label{display:none}
    .bulk-actions-bar .bulk-btns .btn{padding:6px 10px}
    .select-mode-banner{padding:8px 16px}
    .file-table th.col-perm,.file-table td.col-perm{display:none}
    .editor-toolbar{padding:0 8px;gap:4px}
    .editor-toolbar select,.editor-toolbar button{padding:3px 8px;font-size:11px}
    .editor-header .editor-info span.hide-mobile{display:none}
}
@media(max-width:480px){
    .stats-bar{grid-template-columns:1fr}
    .breadcrumbs a{font-size:12px}
    .file-table th.col-size,.file-table td.col-size{display:none}
    .bulk-actions-bar{flex-wrap:wrap}
    .bulk-actions-bar .bulk-btns{justify-content:center}
}

::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
::-webkit-scrollbar-thumb:hover{background:var(--text-secondary)}

.animate-in{animation:fadeSlideIn .3s ease}
@keyframes fadeSlideIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

.shortcuts-panel{position:fixed;bottom:24px;right:24px;width:320px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:12px;z-index:300;display:none;box-shadow:0 10px 40px rgba(0,0,0,.5);overflow:hidden}
.shortcuts-panel.show{display:block}
.shortcuts-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--bg-tertiary);border-bottom:1px solid var(--border);font-size:14px;font-weight:600}
.shortcuts-header button{background:none;border:none;color:var(--text-secondary);font-size:18px;cursor:pointer;padding:2px 6px;border-radius:4px}
.shortcuts-header button:hover{background:var(--border);color:var(--text-primary)}
.shortcuts-body{padding:12px 16px;max-height:300px;overflow-y:auto}
.shortcut-group{margin-bottom:12px}
.shortcut-group h4{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-secondary);margin-bottom:8px}
.shortcut-item{display:flex;align-items:center;justify-content:space-between;padding:4px 0;font-size:13px}
.shortcut-item span{color:var(--text-secondary)}
kbd{background:var(--bg-tertiary);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-size:11px;font-family:monospace;color:var(--text-primary)}

.preview-img{max-width:100%;max-height:65vh;object-fit:contain;border-radius:4px}
.preview-video{max-width:100%;max-height:65vh;border-radius:4px}
.preview-audio{width:100%;max-width:400px}
.preview-pdf{width:100%;height:65vh;border:none}
.preview-text{width:100%;padding:20px;background:#0d1117;color:#e6edf3;font-family:'SF Mono',Monaco,'Cascadia Code',monospace;font-size:13px;line-height:1.6;border:none;resize:none;min-height:400px}
.preview-unsupported{text-align:center;padding:40px;color:var(--text-secondary)}
.preview-unsupported .icon{font-size:48px;margin-bottom:12px}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">⚡</div>
        <h2>File Manager</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="?path=/" class="<?= $current === '/' ? 'active' : '' ?>">
            <span class="icon">🏠</span> Root Directory
        </a>
        <a href="?path=<?= urlencode(rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '/')) ?>">
            <span class="icon">📁</span> Script Directory
        </a>
        <a href="?path=/var/log">
            <span class="icon">📋</span> Logs
        </a>
    </nav>
    <div class="sidebar-stats">
        <div><span>Total Size</span><span><?= $totalSize ?></span></div>
        <div><span>Files</span><span><?= $totalFiles ?></span></div>
        <div><span>Folders</span><span><?= $totalFolders ?></span></div>
        <div><span>Path</span><span style="word-break:break-all;max-width:120px;text-align:right"><?= htmlspecialchars($current) ?></span></div>
    </div>
</aside>

<div class="main-content">
    <header class="header">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="breadcrumbs">
            <?php foreach ($breadcrumbs as $i => $bc): ?>
                <?php if ($i > 0): ?><span class="sep">›</span><?php endif; ?>
                <a href="?path=<?= urlencode($bc['path']) ?>"><?= htmlspecialchars($bc['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openModal('uploadModal')">📤 <span class="label">Upload</span></button>
            <button class="btn btn-secondary" onclick="openModal('newFolderModal')">📁 <span class="label">New Folder</span></button>
            <button class="btn btn-secondary" onclick="openModal('newFileModal')">📄 <span class="label">New File</span></button>
            <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Logout?')">🚪</a>
        </div>
    </header>

    <div class="select-mode-banner" id="selectBanner">
        📱 Selection Mode Active — Tap files to select, long-press to deselect all
        <span class="exit-sel" onclick="exitSelectMode()">Exit Selection</span>
    </div>

    <div class="content animate-in">
        <?php if ($msg): ?>
        <div class="toast <?= $msgType ?>" id="toast"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon blue">📁</div>
                <div class="stat-info"><h3><?= $totalFolders ?></h3><p>Folders</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📄</div>
                <div class="stat-info"><h3><?= $totalFiles ?></h3><p>Files</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">💾</div>
                <div class="stat-info"><h3><?= $totalSize ?></h3><p>Total Size</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">📍</div>
                <div class="stat-info">
                    <h3 style="font-size:14px;word-break:break-all"><?= htmlspecialchars($current) ?></h3>
                    <p>Current Path</p>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search files and folders..." oninput="filterFiles()">
            </div>
            <button class="btn btn-secondary btn-sm" onclick="enterSelectMode()" id="selectModeBtn">☑️ Select</button>
        </div>

        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon">📂</div>
            <h3>This folder is empty</h3>
            <p>Upload files or create a new folder to get started</p>
        </div>
        <?php else: ?>
        <div class="file-table-wrap">
            <table class="file-table" id="fileTable">
                <thead>
                    <tr>
                        <th style="width:40px"><div class="checkbox-wrap"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></div></th>
                        <th onclick="sortTable(1)">Name ↕</th>
                        <th onclick="sortTable(2)" class="col-size">Size ↕</th>
                        <th onclick="sortTable(3)">Modified ↕</th>
                        <th class="col-perm" style="width:60px">Perms</th>
                        <th style="width:200px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>"
                        data-path="<?= htmlspecialchars($item['path']) ?>"
                        data-isdir="<?= $item['isDir'] ? '1' : '0' ?>"
                        data-editable="<?= $item['isEditable'] ? '1' : '0' ?>"
                        data-archive="<?= $item['isArch'] ? '1' : '0' ?>">
                        <td>
                            <div class="checkbox-wrap">
                                <input type="checkbox" class="file-check" value="<?= htmlspecialchars($item['path']) ?>" onchange="updateBulkBar()">
                            </div>
                        </td>
                        <td>
                            <div class="file-name">
                                <span class="icon"><?= $item['isDir'] ? '📁' : getFileIcon($item['name']) ?></span>
                                <?php if ($item['isDir']): ?>
                                    <a class="name dir" href="?path=<?= urlencode($item['path']) ?>"><?= htmlspecialchars($item['name']) ?></a>
                                <?php else: ?>
                                    <?php if ($item['isEditable']): ?>
                                        <a class="name" href="javascript:void(0)" onclick="openEditor('<?= htmlspecialchars($item['path']) ?>', '<?= htmlspecialchars(addslashes($item['name'])) ?>')" oncontextmenu="showContext(event, this.closest('tr'))"><?= htmlspecialchars($item['name']) ?></a>
                                    <?php else: ?>
                                        <a class="name" href="?action=download&file=<?= urlencode($item['path']) ?>" oncontextmenu="showContext(event, this.closest('tr'))"><?= htmlspecialchars($item['name']) ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="file-meta col-size"><?= $item['size'] ?></td>
                        <td class="file-meta"><?= $item['mtime'] ?></td>
                        <td class="file-meta col-perm" style="font-family:monospace"><?= $item['perms'] ?></td>
                        <td>
                            <div class="file-actions">
                                <?php if (!$item['isDir']): ?>
                                    <button onclick="openPreview('<?= htmlspecialchars(addslashes($item['path'])) ?>', '<?= htmlspecialchars(addslashes($item['name'])) ?>', '<?= $item['ext'] ?>')" title="Preview">👁️</button>
                                    <a href="?action=download&file=<?= urlencode($item['path']) ?>" title="Download">⬇️</a>
                                <?php endif; ?>
                                <button onclick="openRenameModal('<?= htmlspecialchars(addslashes($item['path'])) ?>', '<?= htmlspecialchars(addslashes($item['name'])) ?>')" title="Rename">✏️</button>
                                <button onclick="openChmodModal('<?= htmlspecialchars(addslashes($item['path'])) ?>', '<?= $item['perms'] ?>')" title="Permissions">🔐</button>
                                <?php if ($item['isArch']): ?>
                                    <a href="?action=extract&file=<?= urlencode($item['path']) ?>&path=<?= urlencode($current) ?>" title="Extract" onclick="return confirm('Extract this archive?')">📦</a>
                                <?php endif; ?>
                                <button class="del" onclick="confirmDelete('<?= htmlspecialchars(addslashes($item['path'])) ?>', <?= $item['isDir'] ? 'true' : 'false' ?>)" title="Delete">🗑️</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="bulk-actions-bar" id="bulkBar">
    <div class="sel-count">
        <span id="selCount">0</span> selected
    </div>
    <div class="bulk-btns">
        <button class="btn btn-secondary btn-sm" onclick="bulkAction('chmod')" title="Set permissions">🔐 <span class="label">Perms</span></button>
        <button class="btn btn-secondary btn-sm" onclick="bulkAction('copy')" title="Copy to current dir">📋 <span class="label">Copy</span></button>
        <button class="btn btn-secondary btn-sm" onclick="bulkAction('move')" title="Move to current dir">📦 <span class="label">Move</span></button>
        <button class="btn btn-warning btn-sm" onclick="bulkAction('extract')" title="Extract archives">🗜️ <span class="label">Extract</span></button>
        <button class="btn btn-success btn-sm" onclick="bulkAction('zip')" title="Create zip archive">📦 <span class="label">Zip</span></button>
        <button class="btn btn-danger btn-sm" onclick="bulkAction('delete')" title="Delete selected">🗑️ <span class="label">Delete</span></button>
        <button class="btn btn-secondary btn-sm" onclick="exitSelectMode()" title="Cancel selection">✕</button>
    </div>
</div>

<div class="modal-overlay" id="uploadModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📤 Upload Files</h3>
            <button class="modal-close" onclick="closeModal('uploadModal')">×</button>
        </div>
        <form method="POST" action="?action=upload&path=<?= urlencode($current) ?>" enctype="multipart/form-data" id="uploadForm">
            <div class="modal-body">
                <div class="upload-zone" id="dropZone">
                    <div class="icon">📁</div>
                    <p>Drag & drop files here or click a button below</p>
                    <p style="font-size:12px;margin-top:8px">Max: <?= $CONFIG['max_upload'] ?>MB per file</p>
                    <div style="display:flex;gap:10px;justify-content:center;margin-top:16px;flex-wrap:wrap">
                        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('fileInput').click()">📄 Select Files</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('folderInput').click()">📁 Select Folder</button>
                    </div>
                </div>
                <input type="file" name="files[]" id="fileInput" multiple style="display:none" onchange="handleFileSelect(this)">
                <input type="file" name="folder_files[]" id="folderInput" webkitdirectory style="display:none" onchange="handleFolderSelect(this)">
                <input type="hidden" name="folder_paths" id="folderPaths" value="">
                <div id="uploadList" style="margin-top:12px"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">📤 Upload</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="newFolderModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📁 New Folder</h3>
            <button class="modal-close" onclick="closeModal('newFolderModal')">×</button>
        </div>
        <form method="POST" action="?action=create_folder&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>Folder Name</label>
                    <input type="text" name="foldername" placeholder="Enter folder name..." required autofocus>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newFolderModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">📁 Create</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="newFileModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📄 New File</h3>
            <button class="modal-close" onclick="closeModal('newFileModal')">×</button>
        </div>
        <form method="POST" action="?action=create_file&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>File Name</label>
                    <input type="text" name="filename" placeholder="example.php" required>
                </div>
                <div class="form-group">
                    <label>Content (optional)</label>
                    <textarea name="content" placeholder="Enter file content..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newFileModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">📄 Create</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="renameModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Rename</h3>
            <button class="modal-close" onclick="closeModal('renameModal')">×</button>
        </div>
        <form method="POST" action="?action=rename&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <input type="hidden" name="oldname" id="renameOld">
                <div class="form-group">
                    <label>New Name</label>
                    <input type="text" name="newname" id="renameNew" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('renameModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">✏️ Rename</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="chmodModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🔐 Permissions</h3>
            <button class="modal-close" onclick="closeModal('chmodModal')">×</button>
        </div>
        <form method="POST" action="?action=chmod&path=<?= urlencode($current) ?>" id="chmodForm">
            <div class="modal-body">
                <input type="hidden" name="filepath" id="chmodFile">
                <div class="form-group">
                    <label>Permissions (octal)</label>
                    <input type="text" name="permissions" id="chmodPerms" pattern="[0-7]{4}" maxlength="4" required>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPerms('0755')">755</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPerms('0644')">644</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPerms('0777')">777</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPerms('0666')">666</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('chmodModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">🔐 Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="bulkChmodModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🔐 Bulk Permissions</h3>
            <button class="modal-close" onclick="closeModal('bulkChmodModal')">×</button>
        </div>
        <form method="POST" action="?action=bulk_chmod&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <input type="hidden" name="files" id="bulkChmodFiles">
                <p style="margin-bottom:16px;color:var(--text-secondary)">Set permissions for <strong id="bulkChmodCount" style="color:var(--accent)"></strong> item(s):</p>
                <div class="form-group">
                    <label>Permissions (octal)</label>
                    <input type="text" name="permissions" id="bulkChmodPerms" pattern="[0-7]{4}" maxlength="4" value="0644" required>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('bulkChmodPerms').value='0755'">755</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('bulkChmodPerms').value='0644'">644</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('bulkChmodPerms').value='0777'">777</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('bulkChmodPerms').value='0666'">666</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('bulkChmodModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">🔐 Apply to All</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🗑️ Confirm Delete</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">×</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteName"></strong>?</p>
            <p style="color:var(--danger);margin-top:8px;font-size:13px">⚠️ This action cannot be undone!</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
            <a href="#" id="deleteLink" class="btn btn-danger">🗑️ Delete</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="bulkDeleteModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🗑️ Bulk Delete</h3>
            <button class="modal-close" onclick="closeModal('bulkDeleteModal')">×</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="bulkDeleteCount" style="color:var(--danger)"></strong> item(s)?</p>
            <p style="color:var(--danger);margin-top:8px;font-size:13px">⚠️ This action cannot be undone!</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('bulkDeleteModal')">Cancel</button>
            <a href="#" id="bulkDeleteLink" class="btn btn-danger">🗑️ Delete All</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="bulkCopyModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📋 Bulk Copy</h3>
            <button class="modal-close" onclick="closeModal('bulkCopyModal')">×</button>
        </div>
        <form method="POST" action="?action=bulk_copy&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <input type="hidden" name="files" id="bulkCopyFiles">
                <p style="color:var(--text-secondary)">Copy <strong id="bulkCopyCount" style="color:var(--accent)"></strong> item(s) to current directory?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('bulkCopyModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">📋 Copy</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="bulkMoveModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📦 Bulk Move</h3>
            <button class="modal-close" onclick="closeModal('bulkMoveModal')">×</button>
        </div>
        <form method="POST" action="?action=bulk_move&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <input type="hidden" name="files" id="bulkMoveFiles">
                <p style="color:var(--text-secondary)">Move <strong id="bulkMoveCount" style="color:var(--accent)"></strong> item(s) to current directory?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('bulkMoveModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">📦 Move</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="bulkExtractModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🗜️ Bulk Extract</h3>
            <button class="modal-close" onclick="closeModal('bulkExtractModal')">×</button>
        </div>
        <form method="POST" action="?action=bulk_extract&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <input type="hidden" name="files" id="bulkExtractFiles">
                <p style="color:var(--text-secondary)">Extract <strong id="bulkExtractCount" style="color:var(--warning)"></strong> archive(s) to current directory?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('bulkExtractModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">🗜️ Extract</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="previewModal">
    <div class="modal" style="max-width:900px">
        <div class="modal-header">
            <h3 id="previewTitle">👁️ Preview</h3>
            <button class="modal-close" onclick="closePreview()">×</button>
        </div>
        <div class="modal-body" id="previewBody" style="padding:0;max-height:70vh;overflow:auto;display:flex;align-items:center;justify-content:center;background:#1a1a2e;min-height:200px">
        </div>
        <div class="modal-footer">
            <a href="#" id="previewDownload" class="btn btn-primary" download>⬇️ Download</a>
            <button class="btn btn-secondary" onclick="closePreview()">Close</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="zipModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📦 Create Zip Archive</h3>
            <button class="modal-close" onclick="closeModal('zipModal')">×</button>
        </div>
        <form method="POST" action="?action=create_zip&path=<?= urlencode($current) ?>">
            <div class="modal-body">
                <input type="hidden" name="files" id="zipFiles">
                <p style="margin-bottom:16px;color:var(--text-secondary)">Create zip from <strong id="zipCount" style="color:var(--accent)"></strong> item(s):</p>
                <div class="form-group">
                    <label>Archive Name</label>
                    <input type="text" name="zipname" value="archive.zip" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('zipModal')">Cancel</button>
                <button type="submit" class="btn btn-success">📦 Create Zip</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="destPickerModal">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h3 id="destPickerTitle">📁 Select Destination</h3>
            <button class="modal-close" onclick="closeModal('destPickerModal')">×</button>
        </div>
        <div class="modal-body" style="padding:0">
            <div class="dest-breadcrumb" id="destBreadcrumb" style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg-tertiary);display:flex;align-items:center;gap:4px;flex-wrap:wrap;font-size:13px;min-height:44px">
            </div>
            <div id="destFolderList" style="max-height:350px;overflow-y:auto;padding:8px">
                <div style="text-align:center;padding:20px;color:var(--text-secondary)">Loading...</div>
            </div>
            <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;align-items:center;gap:8px">
                <span style="font-size:13px;color:var(--text-secondary)">Selected:</span>
                <code id="destSelectedPath" style="flex:1;font-size:13px;color:var(--accent);background:var(--bg-tertiary);padding:6px 10px;border-radius:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">/</code>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('destPickerModal')">Cancel</button>
            <button class="btn btn-primary" id="destPickerConfirm" onclick="confirmDestPicker()">✓ Select Here</button>
        </div>
    </div>
</div>

<div class="shortcuts-panel" id="shortcutsPanel">
    <div class="shortcuts-header">
        <span>⌨️ Keyboard Shortcuts</span>
        <button onclick="toggleShortcuts()">×</button>
    </div>
    <div class="shortcuts-body">
        <div class="shortcut-group">
            <h4>General</h4>
            <div class="shortcut-item"><kbd>Ctrl+A</kbd><span>Select all files</span></div>
            <div class="shortcut-item"><kbd>Esc</kbd><span>Close modal / Exit selection</span></div>
            <div class="shortcut-item"><kbd>/</kbd><span>Focus search</span></div>
            <div class="shortcut-item"><kbd>?</kbd><span>Toggle shortcuts help</span></div>
        </div>
        <div class="shortcut-group">
            <h4>Editor</h4>
            <div class="shortcut-item"><kbd>Ctrl+S</kbd><span>Save file</span></div>
            <div class="shortcut-item"><kbd>Ctrl+F</kbd><span>Find & Replace</span></div>
            <div class="shortcut-item"><kbd>Ctrl+Z</kbd><span>Undo</span></div>
            <div class="shortcut-item"><kbd>Ctrl+Y</kbd><span>Redo</span></div>
        </div>
    </div>
</div>

<div class="context-menu" id="contextMenu">
    <button onclick="contextAction('rename')">✏️ Rename</button>
    <button onclick="contextAction('chmod')">🔐 Permissions</button>
    <button onclick="contextAction('copy')">📋 Copy</button>
    <button onclick="contextAction('move')">📦 Move</button>
    <div class="divider"></div>
    <button class="danger" onclick="contextAction('delete')">🗑️ Delete</button>
</div>

<div class="editor-wrap" id="editorWrap">
    <div class="editor-header">
        <button class="btn btn-secondary btn-sm" onclick="closeEditor()">← Back</button>
        <span class="filename" id="editorFilename"></span>
        <div class="editor-info">
            <span class="hide-mobile" id="editorPos">Ln 1, Col 1</span>
            <span class="hide-mobile" id="editorMode">Plain Text</span>
        </div>
        <button class="btn btn-primary btn-sm" onclick="saveEditor()">💾 Save</button>
    </div>
    <div class="editor-toolbar">
        <select id="editorThemeSelect" onchange="setEditorTheme(this.value)">
            <option value="ace/theme/monokai">🌙 Monokai</option>
            <option value="ace/theme/github_dark">⚫ GitHub Dark</option>
            <option value="ace/theme/twilight">🌆 Twilight</option>
            <option value="ace/theme/terminal">💚 Terminal</option>
            <option value="ace/theme/tomorrow_night">🌑 Tomorrow Night</option>
            <option value="ace/theme/dracula">🧛 Dracula</option>
            <option value="ace/theme/one_dark">🔵 One Dark</option>
            <option value="ace/theme/clouds_midnight">☁️ Clouds Midnight</option>
        </select>
        <select id="editorModeSelect" onchange="setEditorMode(this.value)">
            <option value="text">Plain Text</option>
            <option value="php">PHP</option>
            <option value="html">HTML</option>
            <option value="css">CSS</option>
            <option value="javascript">JavaScript</option>
            <option value="json">JSON</option>
            <option value="xml">XML</option>
            <option value="python">Python</option>
            <option value="java">Java</option>
            <option value="ruby">Ruby</option>
            <option value="golang">Go</option>
            <option value="rust">Rust</option>
            <option value="c_cpp">C / C++</option>
            <option value="csharp">C#</option>
            <option value="sql">SQL</option>
            <option value="sh">Shell / Bash</option>
            <option value="yaml">YAML</option>
            <option value="markdown">Markdown</option>
            <option value="typescript">TypeScript</option>
            <option value="jsx">JSX</option>
            <option value="scss">SCSS</option>
            <option value="sass">Sass</option>
            <option value="less">Less</option>
            <option value="ini">INI</option>
            <option value="dockerfile">Dockerfile</option>
            <option value="html_ruby">ERB</option>
            <option value="lua">Lua</option>
            <option value="perl">Perl</option>
            <option value="swift">Swift</option>
            <option value="kotlin">Kotlin</option>
        </select>
        <div class="sep"></div>
        <button onclick="editorFind()" title="Find & Replace (Ctrl+F)">🔍 Find</button>
        <button onclick="editorUndo()" title="Undo (Ctrl+Z)">↩️</button>
        <button onclick="editorRedo()" title="Redo (Ctrl+Y)">↪️</button>
        <div class="sep"></div>
        <button onclick="editorFontSize(1)" title="Increase font size">A+</button>
        <button onclick="editorFontSize(-1)" title="Decrease font size">A-</button>
        <button onclick="editorToggleWrap()" id="wrapBtn" title="Toggle word wrap">↩ Wrap</button>
        <button onclick="editorToggleMinimap()" id="minimapBtn" title="Toggle minimap">🗺️ Map</button>
    </div>
    <div id="aceEditor"></div>
</div>

<div class="touch-select-hint" id="touchHint">💡 Long-press any file to enter selection mode</div>

<script>
let contextFile = '';
let contextIsDir = false;
let contextEditable = false;
let selectMode = false;
let lastCheckedRow = null;
let longPressTimer = null;
const LONG_PRESS_DURATION = 500;
const CURRENT_PATH = <?= json_encode($current) ?>;

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function openRenameModal(path, name) {
    document.getElementById('renameOld').value = path;
    document.getElementById('renameNew').value = name;
    openModal('renameModal');
}

function openChmodModal(path, perms) {
    document.getElementById('chmodFile').value = path;
    document.getElementById('chmodPerms').value = perms;
    openModal('chmodModal');
}

function setPerms(p) {
    document.getElementById('chmodPerms').value = p;
}

function confirmDelete(path, isDir) {
    document.getElementById('deleteName').textContent = path.split('/').pop();
    document.getElementById('deleteLink').href = '?action=delete&file=' + encodeURIComponent(path) + '&path=' + encodeURIComponent(CURRENT_PATH);
    openModal('deleteModal');
}

function filterFiles() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#fileTable tbody tr').forEach(row => {
        row.style.display = row.dataset.name.includes(q) ? '' : 'none';
    });
}

function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.file-check').forEach(cb => {
        cb.checked = checked;
        cb.closest('tr').classList.toggle('selected', checked);
    });
    updateBulkBar();
}

let sortCol = 1, sortAsc = true;
function sortTable(col) {
    if (sortCol === col) sortAsc = !sortAsc;
    else { sortCol = col; sortAsc = true; }
    const tbody = document.querySelector('#fileTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
        let va = a.children[col].textContent.trim();
        let vb = b.children[col].textContent.trim();
        if (col === 2) { va = parseSize(va); vb = parseSize(vb); }
        if (va < vb) return sortAsc ? -1 : 1;
        if (va > vb) return sortAsc ? 1 : -1;
        return 0;
    });
    rows.forEach(r => tbody.appendChild(r));
}

function parseSize(s) {
    const units = { 'B': 1, 'KB': 1024, 'MB': 1048576, 'GB': 1073741824, 'TB': 1099511627776 };
    const m = s.match(/([\d.]+)\s*(\w+)/);
    if (!m) return 0;
    return parseFloat(m[1]) * (units[m[2]] || 1);
}

function showContext(e, tr) {
    e.preventDefault();
    e.stopPropagation();
    contextFile = tr.dataset.path;
    contextIsDir = tr.dataset.isdir === '1';
    contextEditable = tr.dataset.editable === '1';
    const menu = document.getElementById('contextMenu');
    menu.classList.add('show');
    const x = e.touches ? e.touches[0].pageX : e.pageX;
    const y = e.touches ? e.touches[0].pageY : e.pageY;
    menu.style.left = Math.min(x, window.innerWidth - 200) + 'px';
    menu.style.top = Math.min(y, window.innerHeight - 200) + 'px';
}

document.addEventListener('click', () => {
    document.getElementById('contextMenu').classList.remove('show');
});

function contextAction(action) {
    switch(action) {
        case 'rename':
            openRenameModal(contextFile, contextFile.split('/').pop());
            break;
        case 'chmod':
            openChmodModal(contextFile, '0644');
            break;
        case 'delete':
            confirmDelete(contextFile, contextIsDir);
            break;
        case 'copy':
            startCopyWithPicker(contextFile, contextFile.split('/').pop());
            break;
        case 'move':
            startMoveWithPicker(contextFile, contextFile.split('/').pop());
            break;
    }
}

function getSelectedPaths() {
    const paths = [];
    document.querySelectorAll('.file-check:checked').forEach(cb => {
        paths.push(cb.value);
    });
    return paths;
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.file-check:checked');
    const count = checked.length;
    document.getElementById('selCount').textContent = count;
    const bar = document.getElementById('bulkBar');

    document.querySelectorAll('#fileTable tbody tr').forEach(row => {
        const cb = row.querySelector('.file-check');
        row.classList.toggle('selected', cb && cb.checked);
    });

    if (count > 0) {
        bar.classList.add('show');
        document.getElementById('selectBanner').classList.add('show');
    } else if (!selectMode) {
        bar.classList.remove('show');
        document.getElementById('selectBanner').classList.remove('show');
    }

    document.getElementById('selectAll').checked = count > 0 && count === document.querySelectorAll('.file-check').length;
}

function enterSelectMode() {
    selectMode = true;
    document.getElementById('selectBanner').classList.add('show');
    document.getElementById('selectModeBtn').textContent = '☑️ Selecting...';
    document.getElementById('selectModeBtn').classList.add('btn-primary');
    document.getElementById('selectModeBtn').classList.remove('btn-secondary');
    showTouchHint();
}

function exitSelectMode() {
    selectMode = false;
    document.querySelectorAll('.file-check').forEach(cb => { cb.checked = false; });
    document.querySelectorAll('#fileTable tbody tr.selected').forEach(r => r.classList.remove('selected'));
    document.getElementById('selectBanner').classList.remove('show');
    document.getElementById('bulkBar').classList.remove('show');
    document.getElementById('selectAll').checked = false;
    const btn = document.getElementById('selectModeBtn');
    btn.textContent = '☑️ Select';
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-secondary');
}

function showTouchHint() {
    const isMobile = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (isMobile) {
        const hint = document.getElementById('touchHint');
        hint.style.display = 'block';
        setTimeout(() => { hint.style.display = 'none'; }, 2600);
    }
}

function getSelectedPathsNew() {
    const paths = [];
    document.querySelectorAll('.file-check:checked').forEach(cb => paths.push(cb.value));
    return paths;
}

function submitBulkForm(action, filesFieldId, extraFields) {
    const paths = getSelectedPathsNew();
    if (paths.length === 0) { alert('No items selected!'); return; }
    document.getElementById(filesFieldId).value = paths.join('\n');
    if (extraFields) extraFields();
    const countId = filesFieldId.replace('Files', 'Count').replace('Link', 'Count');
    const countEl = document.getElementById(countId);
    if (countEl) countEl.textContent = paths.length;
    openModal(filesFieldId.replace('Files', 'Modal').replace('Link', 'Modal'));
}

function bulkAction(action) {
    const paths = getSelectedPathsNew();
    if (paths.length === 0) { alert('No items selected!'); return; }
    const joined = paths.join('\n');
    switch(action) {
        case 'delete':
            document.getElementById('bulkDeleteFiles') && (document.getElementById('bulkDeleteFiles').value = joined);
            document.getElementById('bulkDeleteCount').textContent = paths.length;
            document.getElementById('bulkDeleteLink').href = '#';
            document.getElementById('bulkDeleteLink').onclick = function() {
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = '?action=bulk_delete&path=' + encodeURIComponent(CURRENT_PATH);
                f.innerHTML = '<textarea name="files">' + joined.replace(/</g,'&lt;') + '</textarea>';
                document.body.appendChild(f);
                f.submit();
            };
            openModal('bulkDeleteModal');
            break;
        case 'chmod':
            document.getElementById('bulkChmodFiles').value = joined;
            document.getElementById('bulkChmodCount').textContent = paths.length;
            openModal('bulkChmodModal');
            break;
        case 'copy':
            startBulkCopyWithPicker();
            break;
        case 'move':
            startBulkMoveWithPicker();
            break;
        case 'extract':
            document.getElementById('bulkExtractFiles').value = joined;
            document.getElementById('bulkExtractCount').textContent = paths.length;
            openModal('bulkExtractModal');
            break;
        case 'zip':
            document.getElementById('zipFiles').value = joined;
            document.getElementById('zipCount').textContent = paths.length;
            openModal('zipModal');
            break;
    }
}

let touchStartY = 0;
let touchStartX = 0;

document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#fileTable tbody');
    if (!tableBody) return;

    tableBody.addEventListener('click', function(e) {
        const row = e.target.closest('tr');
        if (!row) return;
        const cb = row.querySelector('.file-check');
        if (!cb) return;

        if (e.target.closest('.file-actions') || e.target.closest('a.name')) {
            if (selectMode && e.target.closest('a.name')) {
                e.preventDefault();
                cb.checked = !cb.checked;
                row.classList.toggle('selected', cb.checked);
                updateBulkBar();
            }
            return;
        }

        if (selectMode) {
            cb.checked = !cb.checked;
            row.classList.toggle('selected', cb.checked);
            updateBulkBar();
        }
    });

    tableBody.addEventListener('touchstart', function(e) {
        const row = e.target.closest('tr');
        if (!row || !row.querySelector('.file-check')) return;
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        longPressTimer = setTimeout(function() {
            if (!selectMode) {
                enterSelectMode();
            }
            const cb = row.querySelector('.file-check');
            if (cb) {
                cb.checked = !cb.checked;
                row.classList.toggle('selected', cb.checked);
                updateBulkBar();
                if (navigator.vibrate) navigator.vibrate(30);
            }
        }, LONG_PRESS_DURATION);
    }, { passive: true });

    tableBody.addEventListener('touchmove', function(e) {
        const dx = Math.abs(e.touches[0].clientX - touchStartX);
        const dy = Math.abs(e.touches[0].clientY - touchStartY);
        if (dx > 10 || dy > 10) {
            clearTimeout(longPressTimer);
        }
    }, { passive: true });

    tableBody.addEventListener('touchend', function() {
        clearTimeout(longPressTimer);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditor();
            document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
            if (selectMode) exitSelectMode();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (document.getElementById('editorWrap').classList.contains('active')) {
                saveEditor();
            }
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !document.getElementById('editorWrap').classList.contains('active')) {
            const active = document.activeElement;
            if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;
            e.preventDefault();
            document.querySelectorAll('.file-check').forEach(cb => { cb.checked = true; });
            document.querySelectorAll('#fileTable tbody tr').forEach(r => r.classList.add('selected'));
            updateBulkBar();
        }
    });
});

function openEditor(path, name) {
    document.getElementById('editorFilename').textContent = name;
    document.getElementById('editorFilename').dataset.path = path;
    const wrap = document.getElementById('editorWrap');
    wrap.classList.add('active');
    fetch('?action=read_file&file=' + encodeURIComponent(path))
        .then(r => r.text())
        .then(content => {
            initAceEditor(content, name);
        });
}

let aceEditor = null;
let aceFontSize = 14;

function getLangFromExt(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const map = {
        'php':'php','html':'html','htm':'html','css':'css','js':'javascript','json':'json',
        'xml':'xml','py':'python','java':'java','rb':'ruby','go':'golang','rs':'rust',
        'c':'c_cpp','cpp':'c_cpp','h':'c_cpp','cs':'csharp','sql':'sql','sh':'sh',
        'bash':'sh','yml':'yaml','yaml':'yaml','md':'markdown','ts':'typescript',
        'tsx':'jsx','jsx':'jsx','scss':'scss','sass':'sass','less':'less','ini':'ini',
        'dockerfile':'dockerfile','lua':'lua','pl':'perl','pm':'perl','swift':'swift',
        'kt':'kotlin','kts':'kotlin','txt':'text','log':'text','csv':'text',
        'env':'ini','htaccess':'ini','conf':'ini','cfg':'ini','config':'ini',
        'vue':'html','svelte':'html','ejs':'html','hbs':'html','twig':'html',
        'phtml':'php','inc':'php','blade.php':'html'
    };
    return map[ext] || 'text';
}

function initAceEditor(content, filename) {
    const container = document.getElementById('aceEditor');
    if (aceEditor) {
        aceEditor.destroy();
    }
    aceEditor = ace.edit('aceEditor');
    aceEditor.setTheme('ace/theme/monokai');
    aceEditor.session.setMode('ace/mode/' + getLangFromExt(filename));
    aceEditor.setValue(content, -1);
    aceEditor.setFontSize(aceFontSize);
    aceEditor.setOptions({
        enableBasicAutocompletion: true,
        enableSnippets: true,
        enableLiveAutocompletion: false,
        showPrintMargin: false,
        showGutter: true,
        highlightActiveLine: true,
        cursorStyle: 'ace',
        fontSize: 14,
        tabSize: 4,
        useSoftTabs: true,
        wrap: false,
        scrollPastEnd: 0.5,
        animatedScroll: true,
        displayIndentGuides: true,
        fadeFoldWidgets: false,
        showFoldWidgets: true,
        scrollSpeed: 2,
        dragDelay: 0,
        tooltipFollowsMouse: true
    });
    aceEditor.commands.addCommand({
        name: 'saveFile',
        bindKey: { win: 'Ctrl-S', mac: 'Cmd-S' },
        exec: function() { saveEditor(); }
    });
    aceEditor.commands.addCommand({
        name: 'findReplace',
        bindKey: { win: 'Ctrl-F', mac: 'Cmd-F' },
        exec: function() { editorFind(); }
    });
    aceEditor.session.on('change', function() {
        updateEditorStatus();
    });
    aceEditor.selection.on('changeCursor', function() {
        updateEditorStatus();
    });
    const lang = getLangFromExt(filename);
    const modeSelect = document.getElementById('editorModeSelect');
    for (let i = 0; i < modeSelect.options.length; i++) {
        if (modeSelect.options[i].value === lang) {
            modeSelect.selectedIndex = i;
            break;
        }
    }
    const modeName = modeSelect.options[modeSelect.selectedIndex].text;
    document.getElementById('editorMode').textContent = modeName;
    aceEditor.resize();
    aceEditor.focus();
}

function updateEditorStatus() {
    if (!aceEditor) return;
    const pos = aceEditor.getCursorPosition();
    document.getElementById('editorPos').textContent = 'Ln ' + (pos.row + 1) + ', Col ' + (pos.column + 1);
    const mode = aceEditor.session.getMode().$id;
    const modeName = mode ? mode.replace('ace/mode/', '') : 'text';
    const modeSelect = document.getElementById('editorModeSelect');
    for (let i = 0; i < modeSelect.options.length; i++) {
        if (modeSelect.options[i].value === modeName) {
            document.getElementById('editorMode').textContent = modeSelect.options[i].text;
            break;
        }
    }
}

function setEditorTheme(theme) {
    if (aceEditor) aceEditor.setTheme(theme);
}

function setEditorMode(mode) {
    if (aceEditor) {
        aceEditor.session.setMode('ace/mode/' + mode);
        updateEditorStatus();
    }
}

function editorFind() {
    if (aceEditor) {
        aceEditor.execCommand('find');
    }
}

function editorUndo() {
    if (aceEditor) aceEditor.undo();
}

function editorRedo() {
    if (aceEditor) aceEditor.redo();
}

function editorFontSize(dir) {
    aceFontSize += dir * 2;
    if (aceFontSize < 10) aceFontSize = 10;
    if (aceFontSize > 32) aceFontSize = 32;
    if (aceEditor) aceEditor.setFontSize(aceFontSize);
}

let wrapEnabled = false;
function editorToggleWrap() {
    wrapEnabled = !wrapEnabled;
    if (aceEditor) aceEditor.session.setUseWrapMode(wrapEnabled);
    const btn = document.getElementById('wrapBtn');
    btn.classList.toggle('active', wrapEnabled);
}

let minimapEnabled = true;
function editorToggleMinimap() {
    minimapEnabled = !minimapEnabled;
    if (aceEditor) aceEditor.setOption('showGutter', minimapEnabled);
    const btn = document.getElementById('minimapBtn');
    btn.classList.toggle('active', !minimapEnabled);
}

let isSaving = false;
function saveEditor() {
    if (!aceEditor || isSaving) return;
    isSaving = true;
    const path = document.getElementById('editorFilename').dataset.path;
    const content = aceEditor.getValue();
    const btn = document.querySelector('.editor-header .btn-primary');
    const origText = btn.textContent;
    btn.textContent = '⏳ Saving...';
    btn.disabled = true;
    const formData = new URLSearchParams();
    formData.append('filepath', path);
    formData.append('content', content);
    fetch('?action=save&path=' + encodeURIComponent(CURRENT_PATH), {
        method: 'POST',
        body: formData,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => {
        if (r.status === 403) {
            throw new Error('403 Forbidden — ModSecurity or server permissions blocking the request. Upload the .htaccess file provided.');
        }
        if (r.status === 404) {
            throw new Error('404 — Script not found on server');
        }
        if (r.status >= 500) {
            throw new Error('Server error ' + r.status);
        }
        return r.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            showSaveToast(data.success, data.message);
        } catch(e) {
            showSaveToast(false, 'Server returned non-JSON: ' + text.substring(0, 100));
        }
        btn.textContent = origText;
        btn.disabled = false;
        isSaving = false;
    })
    .catch(err => {
        let msg = err.message;
        if (msg.includes('403') || msg.includes('Forbidden')) {
            msg = '403 Forbidden! Upload .htaccess to cPanel or disable ModSecurity in cPanel → Security → ModSecurity';
        }
        showSaveToast(false, msg);
        btn.textContent = origText;
        btn.disabled = false;
        isSaving = false;
    });
}

function showSaveToast(success, message) {
    const existing = document.querySelector('.save-toast');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'save-toast';
    toast.style.cssText = 'position:fixed;top:12px;right:12px;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:500;z-index:9999;transition:all .3s;box-shadow:0 4px 20px rgba(0,0,0,.4);font-family:-apple-system,BlinkMacSystemFont,sans-serif;';
    if (success) {
        toast.style.background = '#238636';
        toast.style.color = '#fff';
        toast.textContent = '✅ ' + (message || 'File saved successfully');
    } else {
        toast.style.background = '#da3633';
        toast.style.color = '#fff';
        toast.textContent = '❌ ' + (message || 'Failed to save file');
    }
    document.body.appendChild(toast);
    requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

function closeEditor() {
    document.getElementById('editorWrap').classList.remove('active');
}

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
if (dropZone) {
    ['dragenter','dragover'].forEach(e => {
        dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.add('dragover'); });
    });
    ['dragleave','drop'].forEach(e => {
        dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.remove('dragover'); });
    });
    dropZone.addEventListener('drop', e => {
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(fileInput);
    });
}

function handleFileSelect(input) {
    document.getElementById('folderPaths').value = '';
    const list = document.getElementById('uploadList');
    list.innerHTML = '';
    let totalSize = 0;
    const items = Array.from(input.files);
    items.forEach(f => {
        totalSize += f.size;
        const div = document.createElement('div');
        div.style.cssText = 'padding:8px 12px;background:var(--bg-tertiary);border-radius:8px;margin-bottom:4px;font-size:13px;display:flex;justify-content:space-between;align-items:center;';
        div.innerHTML = '<span>📄 ' + f.name + '</span><span style="color:var(--text-secondary)">' + formatBytes(f.size) + '</span>';
        list.appendChild(div);
    });
    if (items.length > 1) {
        const total = document.createElement('div');
        total.style.cssText = 'padding:8px 12px;background:rgba(88,166,255,.1);border:1px solid rgba(88,166,255,.2);border-radius:8px;margin-top:8px;font-size:13px;font-weight:600;color:var(--accent);';
        total.textContent = '📦 ' + items.length + ' file(s) — Total: ' + formatBytes(totalSize);
        list.appendChild(total);
    }
}

function handleFolderSelect(input) {
    const list = document.getElementById('uploadList');
    list.innerHTML = '';
    let totalSize = 0;
    const items = Array.from(input.files).filter(f => f.size > 0);
    const pathsData = [];
    const folderTree = {};
    items.forEach(f => {
        totalSize += f.size;
        const path = f.webkitRelativePath || f.name;
        pathsData.push(path);
        const parts = path.split('/');
        const folderPath = parts.length > 1 ? parts.slice(0, -1).join('/') : '';
        if (!folderTree[folderPath]) folderTree[folderPath] = [];
        folderTree[folderPath].push({ name: parts[parts.length - 1], size: f.size });
    });
    document.getElementById('folderPaths').value = JSON.stringify(pathsData);
    const folderCount = Object.keys(folderTree).length;
    const folderName = items.length > 0 ? (items[0].webkitRelativePath || '').split('/')[0] : '';
    if (folderName) {
        const header = document.createElement('div');
        header.style.cssText = 'padding:10px 12px;background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.2);border-radius:8px;margin-bottom:8px;font-size:13px;font-weight:600;color:var(--success);';
        header.textContent = '📁 ' + folderName + '/ — ' + items.length + ' file(s) in ' + folderCount + ' folder(s)';
        list.appendChild(header);
    }
    Object.entries(folderTree).forEach(([folder, files]) => {
        if (folder) {
            const folderEl = document.createElement('div');
            folderEl.style.cssText = 'padding:6px 12px;font-size:12px;color:var(--text-secondary);font-weight:600;margin-top:8px;';
            folderEl.textContent = '📂 ' + folder + '/';
            list.appendChild(folderEl);
        }
        files.forEach(f => {
            const indent = folder ? '    ' : '';
            const div = document.createElement('div');
            div.style.cssText = 'padding:4px 12px 4px ' + (folder ? '28px' : '12px') + ';font-size:12px;display:flex;justify-content:space-between;align-items:center;color:var(--text-secondary);';
            div.innerHTML = '<span>' + indent + '📄 ' + f.name + '</span><span>' + formatBytes(f.size) + '</span>';
            list.appendChild(div);
        });
    });
    if (items.length > 0) {
        const total = document.createElement('div');
        total.style.cssText = 'padding:8px 12px;background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.2);border-radius:8px;margin-top:10px;font-size:13px;font-weight:600;color:var(--success);';
        total.textContent = '📦 Total: ' + formatBytes(totalSize) + ' (' + items.length + ' files)';
        list.appendChild(total);
    }
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024, s = ['B','KB','MB','GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + s[i];
}

document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); });
});

<?php if ($msg): ?>
setTimeout(() => { const t = document.getElementById('toast'); if (t) t.classList.remove('show'); }, 3000);
<?php endif; ?>

const toast = document.getElementById('toast');
if (toast) requestAnimationFrame(() => toast.classList.add('show'));

function openPreview(path, name, ext) {
    document.getElementById('previewTitle').textContent = '👁️ ' + name;
    document.getElementById('previewDownload').href = '?action=download&file=' + encodeURIComponent(path);
    const body = document.getElementById('previewBody');
    body.innerHTML = '';
    const previewUrl = '?action=preview&file=' + encodeURIComponent(path);
    const imgExts = ['jpg','jpeg','png','gif','svg','bmp','ico','webp','avif'];
    const videoExts = ['mp4','webm','avi','mov','mkv','flv'];
    const audioExts = ['mp3','wav','ogg','flac','aac'];
    const textExts = ['txt','md','log','csv','json','xml','php','html','htm','css','js','py','java','rb','go','rs','c','cpp','h','sh','yml','yaml','ini','conf','sql','ts','tsx','jsx','vue','svelte','scss','less','sass','twig','ejs','hbs','phtml','inc','env','htaccess','dockerfile','makefile','gitignore','editorconfig','cfg','config','pl','pm','lua','r','bat','cmd','reg','inf','kt','swift','dart','ex','exs','hs','clj','scala','groovy','toml'];

    if (ext === 'pdf') {
        body.innerHTML = '<iframe class="preview-pdf" src="' + previewUrl + '"></iframe>';
    } else if (imgExts.includes(ext)) {
        body.innerHTML = '<img class="preview-img" src="' + previewUrl + '" alt="' + name + '" loading="lazy">';
    } else if (videoExts.includes(ext)) {
        body.innerHTML = '<video class="preview-video" controls autoplay><source src="' + previewUrl + '">Your browser does not support video.</video>';
    } else if (audioExts.includes(ext)) {
        body.innerHTML = '<div style="text-align:center;padding:40px;width:100%"><div style="font-size:48px;margin-bottom:16px">🎵</div><p style="color:var(--text-secondary);margin-bottom:16px">' + name + '</p><audio class="preview-audio" controls autoplay><source src="' + previewUrl + '">Your browser does not support audio.</audio></div>';
    } else if (textExts.includes(ext)) {
        fetch(previewUrl)
            .then(r => r.text())
            .then(content => {
                const ta = document.createElement('textarea');
                ta.className = 'preview-text';
                ta.value = content;
                ta.readOnly = true;
                body.appendChild(ta);
            });
    } else {
        body.innerHTML = '<div class="preview-unsupported"><div class="icon">📄</div><h3>Preview not available</h3><p>File type <strong>.' + ext + '</strong> cannot be previewed</p></div>';
    }
    openModal('previewModal');
}

function closePreview() {
    const body = document.getElementById('previewBody');
    body.innerHTML = '';
    const video = body.querySelector('video');
    if (video) video.pause();
    closeModal('previewModal');
}

function toggleShortcuts() {
    document.getElementById('shortcutsPanel').classList.toggle('show');
}

document.addEventListener('keydown', function(e) {
    if (document.getElementById('editorWrap').classList.contains('active')) return;
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
    if (e.key === '/') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    if (e.key === '?') {
        toggleShortcuts();
    }
});

let destPickerMode = '';
let destPickerFiles = [];
let destPickerCurrentPath = '/';
let destPickerCallback = null;

function openDestPicker(title, mode, files, callback) {
    destPickerMode = mode;
    destPickerFiles = files;
    destPickerCallback = callback;
    destPickerCurrentPath = CURRENT_PATH;
    document.getElementById('destPickerTitle').textContent = title;
    document.getElementById('destPickerConfirm').textContent = mode === 'copy' ? '📋 Copy Here' : '📦 Move Here';
    loadDestFolders(CURRENT_PATH);
    openModal('destPickerModal');
}

function loadDestFolders(path) {
    destPickerCurrentPath = path;
    document.getElementById('destSelectedPath').textContent = path;
    const list = document.getElementById('destFolderList');
    list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-secondary)">Loading...</div>';
    fetch('?action=list_folders&dir=' + encodeURIComponent(path))
        .then(r => r.json())
        .then(data => {
            renderDestBreadcrumb(data.path);
            list.innerHTML = '';
            const upBtn = document.createElement('div');
            upBtn.className = 'dest-folder-item';
            upBtn.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;cursor:pointer;font-size:14px;color:var(--text-secondary);transition:background .15s';
            upBtn.innerHTML = '<span style="font-size:18px">⬆️</span><span>.. Go Up</span>';
            upBtn.onmouseenter = () => upBtn.style.background = 'var(--bg-tertiary)';
            upBtn.onmouseleave = () => upBtn.style.background = 'none';
            upBtn.onclick = () => loadDestFolders(data.parent);
            list.appendChild(upBtn);

            if (data.folders.length === 0) {
                const empty = document.createElement('div');
                empty.style.cssText = 'text-align:center;padding:30px;color:var(--text-secondary);font-size:13px';
                empty.textContent = 'No subfolders here';
                list.appendChild(empty);
            }

            data.folders.forEach(folder => {
                const item = document.createElement('div');
                item.className = 'dest-folder-item';
                item.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;cursor:pointer;font-size:14px;transition:background .15s';
                item.innerHTML = '<span style="font-size:18px">📁</span><span>' + folder.name + '</span>';
                item.onmouseenter = () => item.style.background = 'var(--bg-tertiary)';
                item.onmouseleave = () => item.style.background = 'none';
                item.onclick = () => loadDestFolders(folder.path);
                list.appendChild(item);
            });

            const selectHere = document.createElement('div');
            selectHere.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;cursor:pointer;font-size:14px;background:rgba(88,166,255,.1);border:1px dashed rgba(88,166,255,.3);margin-top:8px;transition:all .15s;color:var(--accent)';
            selectHere.innerHTML = '<span style="font-size:18px">📍</span><span>Select this folder as destination</span>';
            selectHere.onmouseenter = () => { selectHere.style.background = 'rgba(88,166,255,.2)'; selectHere.style.borderColor = 'var(--accent)'; };
            selectHere.onmouseleave = () => { selectHere.style.background = 'rgba(88,166,255,.1)'; selectHere.style.borderColor = 'rgba(88,166,255,.3)'; };
            selectHere.onclick = () => {
                document.querySelectorAll('.dest-folder-item').forEach(i => i.style.background = 'none');
                selectHere.style.background = 'rgba(88,166,255,.3)';
                selectHere.style.borderStyle = 'solid';
                document.getElementById('destSelectedPath').textContent = destPickerCurrentPath;
            };
            list.appendChild(selectHere);
        })
        .catch(() => {
            list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--danger)">Failed to load folders</div>';
        });
}

function renderDestBreadcrumb(path) {
    const bc = document.getElementById('destBreadcrumb');
    bc.innerHTML = '';
    const parts = path.split('/').filter(p => p !== '');
    const rootLink = document.createElement('span');
    rootLink.style.cssText = 'cursor:pointer;color:var(--accent)';
    rootLink.textContent = '🏠 /';
    rootLink.onclick = () => loadDestFolders('/');
    bc.appendChild(rootLink);
    let build = '';
    parts.forEach(part => {
        build += '/' + part;
        const sep = document.createElement('span');
        sep.textContent = ' › ';
        sep.style.color = 'var(--text-secondary)';
        bc.appendChild(sep);
        const link = document.createElement('span');
        link.style.cssText = 'cursor:pointer;color:var(--accent)';
        link.textContent = part;
        const p = build;
        link.onclick = () => loadDestFolders(p);
        bc.appendChild(link);
    });
}

function confirmDestPicker() {
    const dest = destPickerCurrentPath;
    if (destPickerCallback) destPickerCallback(dest);
    closeModal('destPickerModal');
}

function startCopyWithPicker(source, name) {
    openDestPicker('📋 Copy: ' + name, 'copy', [source], function(dest) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '?action=copy&path=' + encodeURIComponent(CURRENT_PATH);
        f.innerHTML = '<input name="source" value="' + source + '"><input name="dest" value="' + dest + '">';
        document.body.appendChild(f);
        f.submit();
    });
}

function startMoveWithPicker(source, name) {
    openDestPicker('📦 Move: ' + name, 'move', [source], function(dest) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '?action=move&path=' + encodeURIComponent(CURRENT_PATH);
        f.innerHTML = '<input name="source" value="' + source + '"><input name="dest" value="' + dest + '">';
        document.body.appendChild(f);
        f.submit();
    });
}

function startBulkCopyWithPicker() {
    const paths = getSelectedPathsNew();
    if (paths.length === 0) return;
    openDestPicker('📋 Copy ' + paths.length + ' item(s)', 'copy', paths, function(dest) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '?action=bulk_copy&path=' + encodeURIComponent(CURRENT_PATH);
        f.innerHTML = '<textarea name="files">' + paths.join('\n').replace(/</g,'&lt;') + '</textarea><input name="dest" value="' + dest + '">';
        document.body.appendChild(f);
        f.submit();
    });
}

function startBulkMoveWithPicker() {
    const paths = getSelectedPathsNew();
    if (paths.length === 0) return;
    openDestPicker('📦 Move ' + paths.length + ' item(s)', 'move', paths, function(dest) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '?action=bulk_move&path=' + encodeURIComponent(CURRENT_PATH);
        f.innerHTML = '<textarea name="files">' + paths.join('\n').replace(/</g,'&lt;') + '</textarea><input name="dest" value="' + dest + '">';
        document.body.appendChild(f);
        f.submit();
    });
}
</script>
</body>
</html>