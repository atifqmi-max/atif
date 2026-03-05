<?php
session_start();
define('ACCOUNTS_DIR', __DIR__ . '/accounts/');
define('COOLDOWN', 600); // 10 minutes
define('ADMIN_PASS', 'MySecret123'); // Change this!

// --- API endpoints ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // GET STOCK
    if ($_GET['action'] === 'get_stock') {
        $files = glob(ACCOUNTS_DIR . '*.txt');
        $stock = [];
        foreach ($files as $f) {
            $cat = basename($f, '.txt');
            $lines = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $stock[$cat] = count($lines);
        }
        echo json_encode($stock);
        exit;
    }

    // GENERATE ACCOUNT
    if ($_GET['action'] === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cat = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['category'] ?? '');
        $file = ACCOUNTS_DIR . $cat . '.txt';
        if (!file_exists($file)) {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
            exit;
        }

        // Cooldown check
        if (isset($_SESSION['cooldown'][$cat]) && time() - $_SESSION['cooldown'][$cat] < COOLDOWN) {
            $remaining = COOLDOWN - (time() - $_SESSION['cooldown'][$cat]);
            $mins = ceil($remaining / 60);
            echo json_encode(['success' => false, 'message' => "Please wait $mins min(s)"]);
            exit;
        }

        // Lock file and get first account
        $fp = fopen($file, 'r+');
        if (flock($fp, LOCK_EX)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (empty($lines)) {
                flock($fp, LOCK_UN); fclose($fp);
                echo json_encode(['success' => false, 'message' => 'Out of stock']);
                exit;
            }
            $account = array_shift($lines);
            $newContent = implode("\n", $lines) . (empty($lines) ? '' : "\n");
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $newContent);
            flock($fp, LOCK_UN);
            fclose($fp);

            $_SESSION['cooldown'][$cat] = time();
            echo json_encode(['success' => true, 'account' => $account, 'new_stock' => count($lines)]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'File locked']);
        exit;
    }

    // ADMIN RESTOCK (via POST)
    if ($_GET['action'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $cat = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['category'] ?? '');
        $accounts = $_POST['accounts'] ?? '';
        $lines = array_filter(array_map('trim', explode("\n", $accounts)));
        $valid = array_filter($lines, fn($l) => preg_match('/^.+@.+\..+:.+$/', $l));
        if (empty($valid)) {
            echo json_encode(['success' => false, 'message' => 'No valid accounts']);
            exit;
        }
        file_put_contents(ACCOUNTS_DIR . $cat . '.txt', implode("\n", $valid) . "\n", FILE_APPEND | LOCK_EX);
        echo json_encode(['success' => true, 'message' => 'Accounts added']);
        exit;
    }

    // ADMIN LOGIN
    if ($_GET['action'] === 'login' && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: ?action=admin_panel');
        exit;
    }

    // ADMIN LOGOUT
    if ($_GET['action'] === 'logout') {
        unset($_SESSION['admin']);
        header('Location: /');
        exit;
    }

    // ADMIN PANEL (HTML)
    if ($_GET['action'] === 'admin_panel') {
        if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
            header('Location: ?action=admin_login');
            exit;
        }
        // Show restock form
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Admin Restock</title><style>body{background:#111;color:#eee;font-family:sans;padding:2rem;}</style></head>
        <body>
        <h1>Restock Accounts</h1>
        <form method="post" action="?action=admin">
            Category: <input name="category" required><br>
            Accounts (email:pass one per line):<br>
            <textarea name="accounts" rows="10" cols="50" required></textarea><br>
            <button type="submit">Add</button>
        </form>
        <p><a href="?action=logout">Logout</a></p>
        <script>
        document.querySelector('form').onsubmit = async (e) => {
            e.preventDefault();
            let form = e.target;
            let res = await fetch(form.action, { method:'POST', body: new FormData(form) });
            let data = await res.json();
            alert(data.message);
        };
        </script>
        </body>
        </html>
        <?php
        exit;
    }

    // ADMIN LOGIN FORM
    if ($_GET['action'] === 'admin_login') {
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Admin Login</title><style>body{background:#111;color:#eee;}</style></head>
        <body>
        <form method="post" action="?action=login">
            Password: <input type="password" name="password">
            <button>Login</button>
        </form>
        </body>
        </html>
        <?php
        exit;
    }
}

// --- If no action, show main generator page ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Free Gen · Ultra</title>
    <style>
        /* compact CSS */
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(145deg,#0b0c10,#1a1e2b);color:#e0e0e0;min-height:100vh;display:flex;flex-direction:column;align-items:center;}
        header{text-align:center;padding:2rem;}
        h1{font-size:2.5rem;background:linear-gradient(135deg,#a78bfa,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .categories{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;max-width:1200px;padding:1rem;}
        .category-card{background:rgba(20,25,40,0.8);border:1px solid #31364e;border-radius:28px;padding:1.5rem;text-align:center;cursor:pointer;transition:0.2s;}
        .category-card:hover{border-color:#a78bfa;}
        .stock-badge{background:#2e3440;padding:0.3rem 1rem;border-radius:40px;display:inline-block;}
        #generator-panel{background:rgba(16,20,30,0.9);border-radius:42px;padding:2rem;max-width:500px;margin:2rem auto;}
        .btn{background:linear-gradient(145deg,#6d28d9,#a21caf);border:none;color:white;padding:1rem;width:100%;border-radius:60px;font-size:1.2rem;cursor:pointer;}
        .account-box{display:flex;justify-content:space-between;background:#1a1f33;padding:1rem;border-radius:50px;}
        .copy-btn{background:#2c3e70;border:none;color:white;padding:0.5rem 1.5rem;border-radius:40px;cursor:pointer;}
        footer{padding:2rem;color:#6b7280;}
    </style>
</head>
<body>
    <header>
        <h1>⚡ Pro Free Gen <span style="font-size:0.8rem;background:#2a2f45;padding:0.2rem 1rem;border-radius:40px;">Ultra Pro Max</span></h1>
        <p>Select category & generate</p>
    </header>
    <main>
        <div class="categories" id="category-list"></div>
        <div id="generator-panel" style="display:none;">
            <h2 id="selected-category"></h2>
            <div id="stock-info"></div>
            <button id="generate-btn" class="btn">🚀 Generate Now</button>
            <div id="result-area"></div>
        </div>
    </main>
    <footer>© 2025 Pro Free Gen · 10 min cooldown</footer>

    <script>
        // JavaScript
        let currentCategory = null;
        let stockCount = 0;
        const catList = document.getElementById('category-list');
        const panel = document.getElementById('generator-panel');
        const selectedCat = document.getElementById('selected-category');
        const stockInfo = document.getElementById('stock-info');
        const genBtn = document.getElementById('generate-btn');
        const resultArea = document.getElementById('result-area');

        async function loadStock() {
            const res = await fetch('?action=get_stock');
            const data = await res.json();
            renderCategories(data);
        }

        function renderCategories(stock) {
            catList.innerHTML = '';
            for (let cat in stock) {
                let card = document.createElement('div');
                card.className = 'category-card';
                card.innerHTML = `<h3>${cat}</h3><div class="stock-badge"><span>${stock[cat]}</span> available</div>`;
                card.onclick = () => selectCategory(cat, stock[cat]);
                catList.appendChild(card);
            }
        }

        function selectCategory(cat, stock) {
            currentCategory = cat;
            stockCount = stock;
            selectedCat.textContent = cat;
            stockInfo.textContent = `📦 Stock: ${stock} account${stock!==1?'s':''}`;
            panel.style.display = 'block';
            resultArea.innerHTML = '';
        }

        genBtn.onclick = async () => {
            if (!currentCategory) return;
            genBtn.disabled = true; genBtn.textContent = '⏳ Generating...';
            let fd = new FormData();
            fd.append('category', currentCategory);
            try {
                let res = await fetch('?action=generate', { method:'POST', body:fd });
                let result = await res.json();
                if (result.success) {
                    resultArea.innerHTML = `
                        <div class="account-box">
                            <span>${result.account}</span>
                            <button class="copy-btn" data-acc="${result.account}">📋 Copy</button>
                        </div>
                    `;
                    document.querySelector('.copy-btn').onclick = (e) => {
                        navigator.clipboard.writeText(e.target.dataset.acc);
                        e.target.textContent = '✅ Copied!';
                        setTimeout(() => e.target.textContent='📋 Copy', 1500);
                    };
                    stockCount = result.new_stock;
                    stockInfo.textContent = `📦 Stock: ${stockCount} account${stockCount!==1?'s':''}`;
                } else {
                    resultArea.innerHTML = `<div style="color:#f87171;">⚠️ ${result.message}</div>`;
                }
            } catch(e) {
                resultArea.innerHTML = `<div style="color:#f87171;">Error</div>`;
            }
            genBtn.disabled = false; genBtn.textContent = '🚀 Generate Now';
        };

        loadStock();
        // Auto-refresh stock
        setInterval(async () => {
            if (!currentCategory) return;
            let res = await fetch('?action=get_stock');
            let data = await res.json();
            if (data[currentCategory] !== undefined) {
                stockCount = data[currentCategory];
                stockInfo.textContent = `📦 Stock: ${stockCount} account${stockCount!==1?'s':''}`;
            }
        }, 30000);
    </script>

    <!-- hidden admin link -->
    <div style="position:fixed; bottom:10px; right:10px; opacity:0.3;">
        <a href="?action=admin_login">⚙️</a>
    </div>
</body>
</html>
