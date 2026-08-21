<?php
// admin gallery: upload, list, delete
session_start();
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(empty($_SESSION[$config['admin_session_name']])){ header('Location: login.php'); exit; }

$uploadDir = __DIR__ . '/../uploads/gallery/';
if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['image']['name'])){
  $file = $_FILES['image'];
  // basic validation
  $allowed = ['image/jpeg','image/png','image/webp'];
  if(!in_array($file['type'], $allowed)){
    $error = 'Only JPG/PNG/WEBP allowed';
  }elseif($file['size'] > 5 * 1024 * 1024){
    $error = 'File too large';
  }else{
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('g_') . '.' . $ext;
    $dest = $uploadDir . $newName;
    if(move_uploaded_file($file['tmp_name'], $dest)){
      $path = 'uploads/gallery/' . $newName;
      $stmt = $pdo->prepare('INSERT INTO gallery (path, alt, created_at) VALUES (?,?,NOW())');
      $stmt->execute([$path, $_POST['alt'] ?? '']);
      $msg = 'Uploaded';
    }else{
      $error = 'Upload failed';
    }
  }
}

if(isset($_GET['delete_id'])){
  $did = intval($_GET['delete_id']);
  $stmt = $pdo->prepare('SELECT * FROM gallery WHERE id = ?'); $stmt->execute([$did]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row){
    @unlink(__DIR__ . '/../' . $row['path']);
    $stmt = $pdo->prepare('DELETE FROM gallery WHERE id = ?'); $stmt->execute([$did]);
    header('Location: gallery.php'); exit;
  }
}

$imgs = $pdo->query('SELECT * FROM gallery ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Gallery</title></head><body>
  <h2>Gallery</h2>
  <?php if(!empty($msg)) echo "<p style='color:green'>".htmlspecialchars($msg)."</p>"; if(!empty($error)) echo "<p style='color:red'>".htmlspecialchars($error)."</p>"; ?>

  <form method="post" enctype="multipart/form-data">
    <label>Image<input type="file" name="image" accept="image/*" required></label>
    <label>Alt Text<input name="alt"></label>
    <button>Upload</button>
  </form>

  <h3>Existing Images</h3>
  <ul>
    <?php foreach($imgs as $img): ?>
      <li>
        <img src="/<?=htmlspecialchars($img['path'])?>" alt="<?=htmlspecialchars($img['alt'])?>" style="height:60px;vertical-align:middle"> 
        <?=htmlspecialchars($img['alt'])?>
        [<a href="gallery.php?delete_id=<?=htmlspecialchars($img['id'])?>" onclick="return confirm('Delete image?')">delete</a>]
      </li>
    <?php endforeach; ?>
  </ul>
  <p><a href="dashboard.php">Back</a></p>
</body></html>
