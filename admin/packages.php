<?php
// packages management (list, add, edit, delete)
session_start();
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(empty($_SESSION[$config['admin_session_name']])){ header('Location: login.php'); exit; }

// Add or Update
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title = trim($_POST['title'] ?? '');
  $short_desc = trim($_POST['short_desc'] ?? '');
  $price = floatval($_POST['price'] ?? 0);
  $id = intval($_POST['id'] ?? 0);
  if($id > 0){
    $stmt = $pdo->prepare('UPDATE packages SET title = ?, short_desc = ?, price = ? WHERE id = ?');
    $stmt->execute([$title,$short_desc,$price,$id]);
    $msg = 'Package updated.';
  }else{
    $stmt = $pdo->prepare('INSERT INTO packages (title, short_desc, price, created_at) VALUES (?,?,?,NOW())');
    $stmt->execute([$title,$short_desc,$price]);
    $msg = 'Package added.';
  }
}

// Delete
if(isset($_GET['delete_id'])){
  $del = intval($_GET['delete_id']);
  if($del>0){
    $stmt = $pdo->prepare('DELETE FROM packages WHERE id = ?'); $stmt->execute([$del]);
    header('Location: packages.php'); exit;
  }
}

// Edit load
$editPackage = null;
if(isset($_GET['edit_id'])){
  $eid = intval($_GET['edit_id']);
  if($eid>0){
    $stmt = $pdo->prepare('SELECT * FROM packages WHERE id = ? LIMIT 1'); $stmt->execute([$eid]);
    $editPackage = $stmt->fetch(PDO::FETCH_ASSOC);
  }
}

$pkgs = $pdo->query('SELECT * FROM packages ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Manage Packages</title></head><body>
  <h2>Packages</h2>
  <?php if(!empty($msg)) echo "<p style='color:green'>".htmlspecialchars($msg)."</p>"; ?>

  <form method="post">
    <input type="hidden" name="id" value="<?=htmlspecialchars($editPackage['id'] ?? '')?>">
    <label>Title<input name="title" value="<?=htmlspecialchars($editPackage['title'] ?? '')?>" required></label>
    <label>Short Desc<input name="short_desc" value="<?=htmlspecialchars($editPackage['short_desc'] ?? '')?>"></label>
    <label>Price<input name="price" value="<?=htmlspecialchars($editPackage['price'] ?? '')?>"></label>
    <button><?= $editPackage ? 'Update' : 'Add' ?></button>
    <?php if($editPackage): ?> <a href="packages.php">Cancel</a><?php endif; ?>
  </form>

  <h3>Existing Packages</h3>
  <ul>
    <?php foreach($pkgs as $p): ?>
      <li>
        <strong><?=htmlspecialchars($p['title'])?></strong> — ₹<?=htmlspecialchars($p['price'])?> 
        [<a href="packages.php?edit_id=<?=htmlspecialchars($p['id'])?>">edit</a>]
        [<a href="packages.php?delete_id=<?=htmlspecialchars($p['id'])?>" onclick="return confirm('Delete this package?')">delete</a>]
      </li>
    <?php endforeach; ?>
  </ul>
  <p><a href="dashboard.php">Back</a></p>
</body></html>
