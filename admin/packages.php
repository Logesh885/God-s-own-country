<?php
// Admin packages management with multiple image upload and slug support
session_start();
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(empty($_SESSION[$config['admin_session_name']])){ header('Location: login.php'); exit; }

$uploadDir = __DIR__ . '/../uploads/packages/';
if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Handle delete image
if(isset($_GET['delete_image'])){
  $did = intval($_GET['delete_image']);
  $stmt = $pdo->prepare('SELECT * FROM package_images WHERE id = ?'); $stmt->execute([$did]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row){ @unlink(__DIR__ . '/../' . $row['path']); $stmt = $pdo->prepare('DELETE FROM package_images WHERE id = ?'); $stmt->execute([$did]); }
  header('Location: packages.php?edit_id=' . intval($_GET['pid'] ?? 0)); exit;
}

// Add or Update
$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title = trim($_POST['title'] ?? '');
  $slug = trim($_POST['slug'] ?? '') ?: preg_replace('/[^a-z0-9\-]/','',strtolower(str_replace(' ','-',trim($title))));
  $short_desc = trim($_POST['short_desc'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price = floatval($_POST['price'] ?? 0);
  $id = intval($_POST['id'] ?? 0);
  if($id > 0){
    $stmt = $pdo->prepare('UPDATE packages SET title = ?, slug = ?, short_desc = ?, description = ?, price = ? WHERE id = ?');
    $stmt->execute([$title,$slug,$short_desc,$description,$price,$id]);
    $pkgId = $id;
    $msg = 'Package updated.';
  }else{
    $stmt = $pdo->prepare('INSERT INTO packages (title, slug, short_desc, description, price, created_at) VALUES (?,?,?,?,?,NOW())');
    $stmt->execute([$title,$slug,$short_desc,$description,$price]);
    $pkgId = $pdo->lastInsertId();
    $msg = 'Package added.';
  }

  // handle image uploads (multiple)
  if(!empty($_FILES['images']) && is_array($_FILES['images']['name'])){
    for($i=0;$i<count($_FILES['images']['name']);$i++){
      if(empty($_FILES['images']['name'][$i])) continue;
      $tmp = $_FILES['images']['tmp_name'][$i];
      $name = $_FILES['images']['name'][$i];
      $type = $_FILES['images']['type'][$i];
      $size = $_FILES['images']['size'][$i];
      // basic checks
      $allowed = ['image/jpeg','image/png','image/webp'];
      if($size > 5 * 1024 * 1024) continue;
      if(!in_array($type, $allowed)) continue;
      $ext = pathinfo($name, PATHINFO_EXTENSION);
      $newName = uniqid('p_') . '.' . $ext;
      $dest = $uploadDir . $newName;
      if(move_uploaded_file($tmp, $dest)){
        $path = 'uploads/packages/' . $newName;
        // insert
        $stmt = $pdo->prepare('INSERT INTO package_images (package_id, path, is_primary, created_at) VALUES (?,?,?,NOW())');
        $isPrimary = (!empty($_POST['primary_image']) && $_POST['primary_image'] === $name) ? 1 : 0;
        // If no primary exists, mark first as primary
        if($isPrimary){
          // unset others
          $pdo->prepare('UPDATE package_images SET is_primary = 0 WHERE package_id = ?')->execute([$pkgId]);
        }
        $stmt->execute([$pkgId, $path, $isPrimary]);
      }
    }
  }

  // handle primary selection from checkbox list
  if(!empty($_POST['set_primary'])){
    $prim = intval($_POST['set_primary']);
    $pdo->prepare('UPDATE package_images SET is_primary = 0 WHERE package_id = ?')->execute([$pkgId]);
    $pdo->prepare('UPDATE package_images SET is_primary = 1 WHERE id = ?')->execute([$prim]);
  }

}

// Delete package
if(isset($_GET['delete_id'])){
  $del = intval($_GET['delete_id']);
  if($del>0){
    // delete images files
    $stmt = $pdo->prepare('SELECT path FROM package_images WHERE package_id = ?'); $stmt->execute([$del]); $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($imgs as $im) @unlink(__DIR__ . '/../' . $im['path']);
    $pdo->prepare('DELETE FROM package_images WHERE package_id = ?')->execute([$del]);
    $pdo->prepare('DELETE FROM packages WHERE id = ?')->execute([$del]);
  }
  header('Location: packages.php'); exit;
}

// Edit load
$editPackage = null;
if(isset($_GET['edit_id'])){
  $eid = intval($_GET['edit_id']);
  if($eid>0){
    $stmt = $pdo->prepare('SELECT * FROM packages WHERE id = ? LIMIT 1'); $stmt->execute([$eid]);
    $editPackage = $stmt->fetch(PDO::FETCH_ASSOC);
    $imgsStmt = $pdo->prepare('SELECT * FROM package_images WHERE package_id = ? ORDER BY is_primary DESC, id ASC'); $imgsStmt->execute([$eid]);
    $editImages = $imgsStmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

$pkgs = $pdo->query('SELECT * FROM packages ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Manage Packages</title></head><body>
  <h2>Packages</h2>
  <?php if(!empty($msg)) echo "<p style='color:green'>".htmlspecialchars($msg)."</p>"; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?=htmlspecialchars($editPackage['id'] ?? '')?>">
    <label>Title<input name="title" value="<?=htmlspecialchars($editPackage['title'] ?? '')?>" required></label><br>
    <label>Slug<input name="slug" value="<?=htmlspecialchars($editPackage['slug'] ?? '')?>" placeholder="leave empty to auto-generate"></label><br>
    <label>Short Desc<input name="short_desc" value="<?=htmlspecialchars($editPackage['short_desc'] ?? '')?>"></label><br>
    <label>Description<textarea name="description"><?=htmlspecialchars($editPackage['description'] ?? '')?></textarea></label><br>
    <label>Price<input name="price" value="<?=htmlspecialchars($editPackage['price'] ?? '')?>"></label><br>
    <label>Upload Images<input type="file" name="images[]" multiple accept="image/*"></label><br>
    <button><?= $editPackage ? 'Update' : 'Add' ?></button>
    <?php if($editPackage): ?> <a href="packages.php">Cancel</a><?php endif; ?>
  </form>

  <?php if(!empty($editImages)): ?>
    <h3>Images for <?=htmlspecialchars($editPackage['title'])?></h3>
    <form method="post">
      <input type="hidden" name="id" value="<?=htmlspecialchars($editPackage['id'])?>">
      <ul>
        <?php foreach($editImages as $im): ?>
          <li>
            <img src="/<?=htmlspecialchars($im['path'])?>" style="height:60px"> 
            <label><input type="radio" name="set_primary" value="<?=htmlspecialchars($im['id'])?>" <?= $im['is_primary'] ? 'checked' : '' ?>> Primary</label>
            <a href="packages.php?delete_image=<?=htmlspecialchars($im['id'])?>&pid=<?=htmlspecialchars($editPackage['id'])?>" onclick="return confirm('Delete image?')">delete</a>
          </li>
        <?php endforeach; ?>
      </ul>
      <button>Save Image Settings</button>
    </form>
  <?php endif; ?>

  <h3>Existing Packages</h3>
  <ul>
    <?php foreach($pkgs as $p): ?>
      <li>
        <strong><?=htmlspecialchars($p['title'])?></strong> — ₹<?=htmlspecialchars($p['price'])?> 
        [<a href="packages.php?edit_id=<?=htmlspecialchars($p['id'])?>">edit</a>]
        [<a href="packages.php?delete_id=<?=htmlspecialchars($p['id'])?>" onclick="return confirm('Delete this package?')">delete</a>]
        [<a href="../package.php?slug=<?=urlencode($p['slug'])?>" target="_blank">view</a>]
      </li>
    <?php endforeach; ?>
  </ul>
  <p><a href="dashboard.php">Back</a></p>
</body></html>
