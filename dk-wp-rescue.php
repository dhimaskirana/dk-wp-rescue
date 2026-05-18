<?php
/**
 * DK WordPress Rescue Kit
 * Feature: Debug Toggle, Core Reinstall, Plugin & Theme Manager
 * Access: example.com/dk-wp-rescue.php?pwd=wprescue4321
 */

$secret = 'wprescue4321'; 
if (!isset($_GET['pwd']) || $_GET['pwd'] !== $secret) {
    die("Access denied. DK WordPress Rescue Kit requires authentication.");
}

define('WP_CONTENT_DIR', __DIR__ . '/wp-content');
$config_path = __DIR__ . '/wp-config.php';

// --- API LOGIC (REDACTED FOR BREVITY - SAME AS V4 LOGIC) ---
if (isset($_GET['task'])) {
    header('Content-Type: application/json');
    $slug = $_GET['slug'] ?? '';
    $type = $_GET['type'] ?? '';
    $tmp_file = __DIR__ . "/temp_process.zip";

    try {
        switch ($_GET['task']) {
            case 'toggle_debug':
                if (!file_exists($config_path)) throw new Exception("wp-config.php tidak ditemukan.");
                $content = file_get_contents($config_path);
                $status = $_GET['status'] === 'true' ? 'true' : 'false';
                if (preg_match("/define\(\s*'WP_DEBUG'\s*,\s*(true|false)\s*\);/i", $content)) {
                    $content = preg_replace("/define\(\s*'WP_DEBUG'\s*,\s*(true|false)\s*\);/i", "define('WP_DEBUG', $status);", $content);
                } else {
                    $content = str_replace("/* That's all, stop editing!", "define('WP_DEBUG', $status);\n/* That's all, stop editing!", $content);
                }
                file_put_contents($config_path, $content);
                echo json_encode(['status' => 'success']);
                break;

            case 'download':
                $url = ($type === 'core') ? "https://wordpress.org/latest.zip" : 
                       (($type === 'plugin') ? "https://downloads.wordpress.org/plugin/{$slug}.latest-stable.zip" : "https://downloads.wordpress.org/theme/{$slug}.latest-stable.zip");
                $options = array("http" => array("header" => "User-Agent: Mozilla/5.0\r\n"));
                $file_data = file_get_contents($url, false, stream_context_create($options));
                if (!$file_data) throw new Exception("Gagal download paket.");
                file_put_contents($tmp_file, $file_data);
                echo json_encode(['status' => 'success']);
                break;

            case 'extract':
                $zip = new ZipArchive;
                if ($zip->open($tmp_file) === TRUE) {
                    if ($type === 'core') {
                        $temp_extract = __DIR__ . '/wp_temp_core';
                        if (!is_dir($temp_extract)) mkdir($temp_extract);
                        $zip->extractTo($temp_extract);
                        $zip->close();
                        $src = $temp_extract . '/wordpress';
                        foreach (['wp-admin', 'wp-includes'] as $f) self_copy("$src/$f", __DIR__ . "/$f");
                        foreach (glob("$src/*.php") as $f) copy($f, __DIR__ . '/' . basename($f));
                        self_delete($temp_extract);
                    } else {
                        $target = ($type === 'plugin') ? WP_CONTENT_DIR . '/plugins' : WP_CONTENT_DIR . '/themes';
                        $zip->extractTo($target);
                        $zip->close();
                    }
                    echo json_encode(['status' => 'success']);
                } else { throw new Exception("Gagal ekstrak."); }
                break;

            case 'cleanup':
                if (file_exists($tmp_file)) unlink($tmp_file);
                echo json_encode(['status' => 'success']);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

function self_copy($src, $dst) {
    if (is_dir($src)) {
        if (!is_dir($dst)) mkdir($dst);
        foreach (scandir($src) as $file) { if ($file != "." && $file != "..") self_copy("$src/$file", "$dst/$file"); }
    } elseif (file_exists($src)) { copy($src, $dst); }
}

function self_delete($dir) {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir), array('.','..')) as $file) { (is_dir("$dir/$file")) ? self_delete("$dir/$file") : unlink("$dir/$file"); }
    return rmdir($dir);
}

$current_debug = false;
if (file_exists($config_path)) {
    $current_debug = preg_match("/define\(\s*'WP_DEBUG'\s*,\s*true\s*\);/i", file_get_contents($config_path));
}
$plugins = array_filter(glob(WP_CONTENT_DIR . '/plugins/*'), 'is_dir');
$themes = array_filter(glob(WP_CONTENT_DIR . '/themes/*'), 'is_dir');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DK WordPress Rescue Kit</title>
    <style>
        :root { --primary: #2563eb; --danger: #ef4444; --success: #22c55e; --bg: #f8fafc; --card: #ffffff; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #1e293b; margin: 0; padding: 40px 20px; }
        .container { max-width: 1000px; margin: auto; }
        
        /* Header & Nav */
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .brand h1 { margin: 0; font-size: 24px; color: #0f172a; }
        .brand p { margin: 5px 0 0; color: #64748b; font-size: 14px; }

        /* Switch Toggle */
        .switch-container { display: flex; align-items: center; gap: 10px; background: var(--card); padding: 10px 20px; border-radius: 50px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(20px); }

        /* Cards */
        .card { background: var(--card); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 25px; margin-bottom: 25px; }
        h2 { font-size: 18px; margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* Tabs-like separation */
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #64748b; text-transform: uppercase; padding: 10px; border-bottom: 2px solid var(--bg); }
        td { padding: 12px 10px; border-bottom: 1px solid var(--bg); vertical-align: top; }
        
        .item-info { font-weight: 600; font-size: 14px; display: block; margin-bottom: 4px; font-family: monospace; }
        .status-msg { font-size: 12px; color: #94a3b8; }
        
        /* Buttons */
        .btn { border: none; padding: 8px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-fix { background: #eff6ff; color: var(--primary); }
        .btn-fix:hover { background: var(--primary); color: white; }
        .btn-danger { background: #fef2f2; color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; }

        /* Progress */
        .progress-box { width: 100%; height: 4px; background: #f1f5f9; border-radius: 2px; margin-top: 8px; overflow: hidden; }
        .progress-bar { height: 100%; width: 0%; background: var(--success); transition: width 0.3s; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="brand">
            <h1>DK WordPress Rescue Kit</h1>
            <p>Emergency Toolkit for WordPress Website Repair</p>
        </div>
        <div class="switch-container">
            <span style="font-size: 13px; font-weight: 600;">ENABLE/DISABLE WP_DEBUG</span>
            <label class="switch">
                <input type="checkbox" id="debugToggle" onchange="toggleDebug(this)" <?php echo $current_debug ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
        </div>
    </header>

    <div class="card" style="border-left: 4px solid var(--danger);">
        <h2>🚀 Core System Integrity</h2>
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <p style="margin:0; font-size: 14px; color: #64748b;">Use this if wp-admin or wp-includes is having issues/infected with malware.</p>
                <div id="log-core" class="status-msg" style="margin-top:10px;">Status: Ready</div>
                <div class="progress-box"><div id="bar-core" class="progress-bar"></div></div>
            </div>
            <button onclick="runAction('core', 'core')" class="btn btn-danger">REINSTALL CORE</button>
        </div>
    </div>

    <div class="grid">
        <!-- PLUGINS COLUMN -->
        <div class="card">
            <h2>🔌 Installed Plugins</h2>
            <table>
                <thead><tr><th>Plugin Slug</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($plugins as $p): $s = basename($p); ?>
                    <tr>
                        <td>
                            <span class="item-info"><?php echo $s; ?></span>
                            <div id="log-<?php echo $s; ?>" class="status-msg">Ready</div>
                            <div class="progress-box"><div id="bar-<?php echo $s; ?>" class="progress-bar"></div></div>
                        </td>
                        <td><button onclick="runAction('<?php echo $s; ?>', 'plugin')" class="btn btn-fix">Update</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- THEMES COLUMN -->
        <div class="card">
            <h2>🎨 Installed Themes</h2>
            <table>
                <thead><tr><th>Theme Slug</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($themes as $t): $s = basename($t); ?>
                    <tr>
                        <td>
                            <span class="item-info"><?php echo $s; ?></span>
                            <div id="log-<?php echo $s; ?>" class="status-msg">Ready</div>
                            <div class="progress-box"><div id="bar-<?php echo $s; ?>" class="progress-bar"></div></div>
                        </td>
                        <td><button onclick="runAction('<?php echo $s; ?>', 'theme')" class="btn btn-fix">Update</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p style="text-align:center; color: #94a3b8; font-size: 12px; margin-top: 40px;">
        ⚠️ Delete the <strong>dk-wp-rescue.php</strong> file immediately after use for security purposes.
    </p>
</div>

<script>
const pwd = '<?php echo $secret; ?>';

async function toggleDebug(el) {
    const status = el.checked;
    const res = await fetch(`?pwd=${pwd}&task=toggle_debug&status=${status}`);
    if(res.ok) {
        alert('WP_DEBUG changed successfully!');
        location.reload();
    }
}

async function runAction(slug, type) {
    if(!confirm(`Start reinstall process for ${slug}?`)) return;
    
    const log = document.getElementById('log-' + slug);
    const bar = document.getElementById('bar-' + slug);
    
    try {
        log.innerText = "Status: Downloading..."; bar.style.width = "30%";
        await callAPI(slug, type, 'download');
        
        log.innerText = "Status: Overwriting files..."; bar.style.width = "70%";
        await callAPI(slug, type, 'extract');
        
        alert('Status: Success!');
        log.innerText = "Status: Success!"; bar.style.width = "100%";
        bar.style.background = "var(--success)";
    } catch (e) {
        log.innerText = "Status: Error - " + e.message;
        log.style.color = "var(--danger)";
        bar.style.background = "var(--danger)";
    }
}

async function callAPI(slug, type, task) {
    const res = await fetch(`?pwd=${pwd}&task=${task}&slug=${slug}&type=${type}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || "Server Error");
    return data;
}
</script>

</body>
</html>