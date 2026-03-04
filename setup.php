<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Berlin');

/* =======================
   KONFIG
======================= */

$htpasswd_file = '/etc/apache2/.htpasswd';
$htpasswd_user = 'blarks';
$cookie_name = 'zugang';
$cookie_valid_days = 30;

$files = [
    'whitelist' => __DIR__ . '/config/whitelist.txt',
    'blacklist' => __DIR__ . '/config/filters.txt',
];

$type = $_GET['type'] ?? null;
$message = null;

/* =======================
   FUNKTION: htpasswd prüfen (bcrypt / apr1 / crypt)
======================= */
function htpasswd_verify(string $password, string $stored): bool {
    $stored = trim($stored);
    if ($stored === '') return false;

    // bcrypt ($2y$...)
    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$2b$')) {
        return password_verify($password, $stored);
    }

    // Apache MD5 ($apr1$...) und klassisches crypt
    return hash_equals(crypt($password, $stored), $stored);
}

function get_htpasswd_hash(string $file, string $user): ?string {
    if (!is_readable($file)) return null;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, $user . ':')) {
            return substr($line, strlen($user) + 1);
        }
    }
    return null;
}

/* =======================
   LOGOUT
======================= */
if (isset($_GET['logout'])) {
    setcookie($cookie_name, '', time() - 3600, '/', '', true, true);
    header('Location: setup.php');
    exit;
}

/* =======================
   AUTH CHECK (Cookie)
======================= */
$authed = false;
if (!empty($_COOKIE[$cookie_name])) {
    $hash = get_htpasswd_hash($htpasswd_file, $htpasswd_user);
    if ($hash) {
        $parts = explode('.', $_COOKIE[$cookie_name], 2);
        if (count($parts) === 2) {
            [$token, $sig] = $parts;
            $expected = hash_hmac('sha256', $token, $hash);
            $authed = hash_equals($expected, $sig);
        }
    }
}

/* =======================
   LOGIN SUBMIT
======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $pw = (string)($_POST['password'] ?? '');
    $hash = get_htpasswd_hash($htpasswd_file, $htpasswd_user);

    if (!$hash) {
        $message = "❌ htpasswd nicht lesbar oder User nicht gefunden.";
    } elseif (htpasswd_verify($pw, $hash)) {
        $token = base64_encode(random_bytes(24));
        $sig = hash_hmac('sha256', $token, $hash);
        $value = $token . '.' . $sig;

        setcookie(
            $cookie_name,
            $value,
            [
                'expires'  => time() + ($cookie_valid_days * 86400),
                'path'     => '/',
                'secure'   => true,     // HTTPS nötig!
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );

        header('Location: setup.php');
        exit;
    } else {
        header("Location: /_/denied/index.php");
    exit;
    }
}

/* =======================
   LOGIN PAGE
======================= */
if (!$authed) {
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>⚙️ Admin Login</title>
<style>
body { font-family: Arial, sans-serif; background:#111; color:#eee; margin:40px; }
h1 { color:#00ccff; margin-bottom:10px; }
input { padding:10px; background:#222; border:1px solid #333; color:#eee; width:320px; max-width:100%; }
button { padding:10px 20px; background:#00ccff; border:none; font-weight:bold; cursor:pointer; }
button:hover { background:#00aacc; }
.msg { margin:15px 0; color:#ff5555; }
small { color:#777; display:block; margin-top:10px; }
</style>
</head>
<body>
<h1>⚙️ Admin</h1>
<?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="POST">
    <input type="password" name="password" placeholder="Passwort" autofocus>
    <button type="submit" name="login">Login</button>
</form>
<small>(Login bleibt <?= (int)$cookie_valid_days ?> Tage gültig)</small>
</body>
</html>
<?php
exit;
}

/* =======================
   SAVE
======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'], $_POST['type'])) {
    $type = (string)$_POST['type'];
    if (isset($files[$type])) {
        file_put_contents($files[$type], rtrim((string)$_POST['content']) . "\n");
        $message = "✅ Gespeichert.";
    }
}

/* =======================
   LOAD
======================= */
$content = '';
if ($type && isset($files[$type]) && file_exists($files[$type])) {
    $content = htmlspecialchars((string)file_get_contents($files[$type]));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>⚙️ Admin – Pressefräse</title>
<style>
body { font-family: Arial, sans-serif; background:#111; color:#eee; margin:40px; line-height:1.5; }
h1 { color:#00ccff; }
a { color:#00ccff; text-decoration:none; }
a:hover { text-decoration:underline; }

.btn-row { margin:20px 0; display:flex; gap:10px; flex-wrap:wrap; }
.btn { background:#00ccff; color:#111; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; font-weight:bold; text-decoration:none; }
.btn:hover { background:#00aacc; }
.btn-secondary { background:#333; color:#eee; border:1px solid #555; }
.btn-secondary:hover { background:#444; }

textarea { width:100%; height:400px; background:#1a1a1a; color:#eee; border:1px solid #333; padding:15px; font-family:monospace; font-size:0.95rem; }

.message { margin:15px 0; padding:10px; border-radius:4px; background:#1a2a1a; border:1px solid #00cc66; color:#00ff88; }
</style>
</head>
<body>

<h1>⚙️ Admin – Listenverwaltung</h1>

<div class="btn-row">
    <a href="setup.php?type=whitelist" class="btn btn-secondary">✍️ Edit Whitelist</a>
    <a href="setup.php?type=blacklist" class="btn btn-secondary">⛔ Edit Blacklist</a>
    <a href="index.php" class="btn btn-secondary">← Zurück</a>
    <a href="setup.php?logout=1" class="btn btn-secondary">🚪 Logout</a>
</div>

<?php if ($message): ?>
<div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($type && isset($files[$type])): ?>
<form method="POST">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <textarea name="content"><?= $content ?></textarea>
    <br><br>
    <button type="submit" name="save" class="btn">💾 Speichern</button>
</form>
<?php endif; ?>

</body>
</html>