<?php
// package detail page: package.php?slug={slug}
require __DIR__ . '/config.php';
$config = require __DIR__ . '/config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$slug = $_GET['slug'] ?? '';
if(!$slug){ http_response_code(404); echo 'Not found'; exit; }
$stmt = $pdo->prepare('SELECT * FROM packages WHERE slug = ? LIMIT 1'); $stmt->execute([$slug]); $pkg = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$pkg){ http_response_code(404); echo 'Not found'; exit; }
$imgs = $pdo->prepare('SELECT * FROM package_images WHERE package_id = ? ORDER BY is_primary DESC, id ASC'); $imgs->execute([$pkg['id']]); $images = $imgs->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=htmlspecialchars($pkg['title'])?> - Misty Munnar Tours</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <header class="site-header glass"><div class="container"><h1 class="logo">Misty Munnar Tours</h1></div></header>
  <main class="container">
    <section class="section">
      <h2><?=htmlspecialchars($pkg['title'])?></h2>
      <div style="display:flex;gap:1rem;flex-wrap:wrap">
        <div style="flex:1;min-width:300px">
          <?php if(!empty($images)): ?>
            <img src="/<?=htmlspecialchars($images[0]['path'])?>" alt="<?=htmlspecialchars($pkg['title'])?>" style="width:100%;border-radius:8px">
            <div style="display:flex;gap:8px;margin-top:8px">
              <?php foreach($images as $im): ?><img src="/<?=htmlspecialchars($im['path'])?>" style="height:80px;border-radius:6px"><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div style="flex:1;min-width:280px">
          <p><?=nl2br(htmlspecialchars($pkg['short_desc']))?></p>
          <p><?=nl2br(htmlspecialchars($pkg['description']))?></p>
          <p><strong>Price: ₹<?=htmlspecialchars($pkg['price'])?></strong></p>

          <div style="display:flex;gap:0.5rem">
            <a class="btn" href="#contact">Enquire</a>
            <a class="btn btn-alt" href="#booking">Book Now</a>
          </div>
        </div>
      </div>
    </section>

    <section id="booking" class="section">
      <h3>Book this package</h3>
      <form id="booking-form" class="glass form" method="post" action="/api/booking.php">
        <input type="hidden" name="package_id" value="<?=htmlspecialchars($pkg['id'])?>">
        <label>Name<input name="name" id="bf-name" required></label>
        <label>Email<input name="email" id="bf-email" type="email"></label>
        <label>Phone<input name="phone" id="bf-phone" required></label>
        <label>Travel Date<input type="date" name="travel_date" id="bf-date" required></label>
        <label>People<input type="number" name="people" id="bf-people" min="1" required></label>
        <label>Additional Requirements<textarea name="requirements" id="bf-req"></textarea></label>
        <button class="btn" type="submit">Submit Booking</button>
        <button type="button" id="booking-wa-btn" class="btn btn-alt">Book on WhatsApp</button>
      </form>
    </section>

  </main>
  <footer class="site-footer glass"><div class="container"><p>&copy; Misty Munnar Tours</p></div></footer>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
  <script src="assets/js/script.js"></script>
  <script> AOS.init();</script>
</body>
</html>
