<?php
// admin/wa_bookings.php
session_start();
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(empty($_SESSION[$config['admin_session_name']])){ header('Location: login.php'); exit; }

// Filters
$q = trim($_GET['q'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page-1)*$perPage;
$params = [];
$where = [];
if($q !== ''){
  $where[] = '(package_name LIKE ? OR phone LIKE ? OR name LIKE ?)';
  $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if($from !== ''){ $where[] = 'created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if($to !== ''){ $where[] = 'created_at <= ?'; $params[] = $to . ' 23:59:59'; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Export CSV
if(isset($_GET['export']) && $_GET['export']=='1'){
  $stmt = $pdo->prepare("SELECT * FROM wa_bookings $where_sql ORDER BY created_at DESC");
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=wa_bookings_export_'.date('Ymd_His').'.csv');
  $out = fopen('php://output','w');
  fputcsv($out, ['id','name','phone','travel_date','people','package_id','package_name','requirements','source','user_agent','ip','created_at']);
  foreach($rows as $r){
    fputcsv($out, [$r['id'],$r['name'],$r['phone'],$r['travel_date'],$r['people'],$r['package_id'],$r['package_name'],$r['requirements'],$r['source'],$r['user_agent'],$r['ip'],$r['created_at']]);
  }
  fclose($out);
  exit;
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM wa_bookings $where_sql");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Fetch page
$stmt = $pdo->prepare("SELECT * FROM wa_bookings $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$execParams = array_merge($params, [$perPage, $offset]);
// PDO requires ints for LIMIT/OFFSET when using emulation off; bind separately
foreach($params as $k=>$v){}
$stmt->bindValue(1+$paramsCount:=count($params), $perPage, PDO::PARAM_INT);
$stmt->bindValue(2+$paramsCount, $offset, PDO::PARAM_INT);
if($paramsCount>0){
  for($i=0;$i<$paramsCount;$i++){
    $stmt->bindValue($i+1, $params[$i]);
  }
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pages = max(1, ceil($total / $perPage));

function h($s){ return htmlspecialchars($s); }
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>WhatsApp Bookings</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:1rem}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f4f4f4}</style>
</head><body>
  <h2>WhatsApp Bookings (Analytics)</h2>
  <form method="get" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:1rem">
    <input name="q" placeholder="search name, phone, package" value="<?=h($q)?>">
    <label>From <input type="date" name="from" value="<?=h($from)?>"></label>
    <label>To <input type="date" name="to" value="<?=h($to)?>"></label>
    <button>Filter</button>
    <a href="?<?=htmlspecialchars(http_build_query(array_merge($_GET,['export'=>1])))?>">Export CSV</a>
  </form>

  <p>Showing <?=count($rows)?> of <?=$total?> entries. Page <?=$page?> / <?=$pages?></p>
  <table>
    <thead><tr><th>ID</th><th>When</th><th>Package</th><th>Travel Date</th><th>People</th><th>Name</th><th>Phone</th><th>Requirements</th><th>Source</th><th>IP</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=h($r['id'])?></td>
          <td><?=h($r['created_at'])?></td>
          <td><?=h($r['package_name'])?> (<?=h($r['package_id'])?>)</td>
          <td><?=h($r['travel_date'])?></td>
          <td><?=h($r['people'])?></td>
          <td><?=h($r['name'])?></td>
          <td><?=h($r['phone'])?></td>
          <td><?=h($r['requirements'])?></td>
          <td><?=h($r['source'])?></td>
          <td><?=h($r['ip'])?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="margin-top:1rem">
    <?php if($page>1): ?><a href="?<?=htmlspecialchars(http_build_query(array_merge($_GET,['page'=>$page-1])))?>">&larr; Prev</a><?php endif; ?>
    <?php if($page<$pages): ?> <a href="?<?=htmlspecialchars(http_build_query(array_merge($_GET,['page'=>$page+1])))?>">Next &rarr;</a><?php endif; ?>
  </div>

  <p><a href="dashboard.php">Back</a></p>
</body></html>
