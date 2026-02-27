<?php
// admin.php - Prosty panel zarządzania inwestycjami

session_start();

// Konfiguracja
define('ADMIN_PASSWORD', 'R0der2026!'); // Hasło dostępu do panelu
define('INVESTMENTS_FILE', 'investments.json');

// Wylogowanie
if (isset($_GET['logout'])) {
    $_SESSION['admin_logged'] = false;
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Logowanie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Nieprawidłowe hasło!';
    }
}

// Wczytaj dane z JSON
function loadInvestments() {
    if (!file_exists(INVESTMENTS_FILE)) {
        return ['inwestycje' => []];
    }
    $json = file_get_contents(INVESTMENTS_FILE);
    return json_decode($json, true);
}

// Zapisz dane do JSON
function saveInvestments($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(INVESTMENTS_FILE, $json);
}

// Generuj ID z nazwy
function generateId($name) {
    $id = strtolower(trim($name));
    $id = preg_replace('/[^a-z0-9\-]/', '-', $id);
    $id = preg_replace('/-+/', '-', $id);
    return trim($id, '-');
}

// Obsługa formularzy
if ($_SESSION['admin_logged'] ?? false) {
    $investments = loadInvestments();
    
    // Dodawanie/edycja inwestycji
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_investment'])) {
        $newInvestment = [
            'id' => $_POST['id'] ?: generateId($_POST['nazwa']),
            'nazwa' => $_POST['nazwa'],
            'status' => $_POST['status'],
            'opis' => $_POST['opis'],
            'danePodstawowe' => [
                'cena' => $_POST['cena'],
                'cena_za_m2' => $_POST['cena_za_m2'],
                'powierzchnia' => $_POST['powierzchnia'],
                'pokoje' => intval($_POST['pokoje']),
                'rodzajZabudowy' => $_POST['rodzajZabudowy'],
                'stanWykonczenia' => $_POST['stanWykonczenia'],
                'rynek' => $_POST['rynek'],
                'ogrzewanie' => $_POST['ogrzewanie'],
                'dodatkowe' => array_filter(array_map('trim', explode(',', $_POST['dodatkowe'])))
            ],
            'galeriaZdjec' => array_filter(array_map('trim', explode("\n", $_POST['galeriaZdjec']))),
            'galeriaRzutow' => array_filter(array_map('trim', explode("\n", $_POST['galeriaRzutow']))),
            'prospektPdf' => $_POST['prospektPdf'],
            'mapa' => [
                'embedUrl' => $_POST['mapaUrl'],
                'adres' => $_POST['mapaAdres']
            ]
        ];
        
        // Szukamy czy edytujemy czy dodajemy
        $found = false;
        foreach ($investments['inwestycje'] as &$inv) {
            if ($inv['id'] === $_POST['original_id']) {
                $inv = $newInvestment;
                $found = true;
                break;
            }
        }
        
        if (!$found && empty($_POST['original_id'])) {
            $investments['inwestycje'][] = $newInvestment;
        }
        
        saveInvestments($investments);
        header('Location: admin.php?msg=saved');
        exit;
    }
    
    // Usuwanie inwestycji
    if (isset($_GET['delete'])) {
        $investments['inwestycje'] = array_values(array_filter(
            $investments['inwestycje'],
            fn($inv) => $inv['id'] !== $_GET['delete']
        ));
        saveInvestments($investments);
        header('Location: admin.php?msg=deleted');
        exit;
    }
    
    // Pobierz inwestycję do edycji
    $editInvestment = null;
    if (isset($_GET['edit'])) {
        foreach ($investments['inwestycje'] as $inv) {
            if ($inv['id'] === $_GET['edit']) {
                $editInvestment = $inv;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RODER - Panel zarządzania inwestycjami</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: #1A2F50; }
        .navbar-brand { color: white !important; }
        .card { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .btn-primary { background: #1A2F50; border: none; }
        .btn-primary:hover { background: #2C3E6B; }
        .investment-status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-aktualna { background: #d4edda; color: #155724; }
        .status-wkrótce { background: #fff3cd; color: #856404; }
        .status-archiwum { background: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <span class="navbar-brand">RODER - Panel zarządzania inwestycjami</span>
            <?php if ($_SESSION['admin_logged'] ?? false): ?>
                <a href="?logout=1" class="btn btn-outline-light btn-sm">Wyloguj</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                    if ($_GET['msg'] === 'saved') echo 'Inwestycja została zapisana!';
                    if ($_GET['msg'] === 'deleted') echo 'Inwestycja została usunięta!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!($_SESSION['admin_logged'] ?? false)): ?>
            <!-- FORMULARZ LOGOWANIA -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Logowanie do panelu</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Hasło dostępu</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary">Zaloguj</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif (isset($_GET['edit']) || isset($_GET['new'])): ?>
            <!-- FORMULARZ DODAWANIA/EDYCJI INWESTYCJI -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= $editInvestment ? 'Edycja' : 'Nowa' ?> inwestycja</h5>
                    <a href="admin.php" class="btn btn-sm btn-outline-secondary">Powrót do listy</a>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="original_id" value="<?= $editInvestment['id'] ?? '' ?>">
                        <input type="hidden" name="id" value="<?= $editInvestment['id'] ?? '' ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nazwa inwestycji *</label>
                                <input type="text" name="nazwa" class="form-control" required 
                                       value="<?= htmlspecialchars($editInvestment['nazwa'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="aktualna" <?= ($editInvestment['status'] ?? '') === 'aktualna' ? 'selected' : '' ?>>Aktualna</option>
                                    <option value="wkrótce" <?= ($editInvestment['status'] ?? '') === 'wkrótce' ? 'selected' : '' ?>>Wkrótce</option>
                                    <option value="archiwum" <?= ($editInvestment['status'] ?? '') === 'archiwum' ? 'selected' : '' ?>>Archiwum</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Opis inwestycji</label>
                            <textarea name="opis" class="form-control" rows="5"><?= htmlspecialchars($editInvestment['opis'] ?? '') ?></textarea>
                        </div>

                        <h6 class="mt-4 mb-3">Dane podstawowe</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cena</label>
                                <input type="text" name="cena" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['cena'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cena za m²</label>
                                <input type="text" name="cena_za_m2" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['cena_za_m2'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Powierzchnia</label>
                                <input type="text" name="powierzchnia" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['powierzchnia'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Liczba pokoi</label>
                                <input type="number" name="pokoje" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['pokoje'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Rodzaj zabudowy</label>
                                <input type="text" name="rodzajZabudowy" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['rodzajZabudowy'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stan wykończenia</label>
                                <input type="text" name="stanWykonczenia" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['stanWykonczenia'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Rynek</label>
                                <input type="text" name="rynek" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['rynek'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ogrzewanie</label>
                                <input type="text" name="ogrzewanie" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['danePodstawowe']['ogrzewanie'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Dodatkowe (po przecinku)</label>
                                <input type="text" name="dodatkowe" class="form-control" 
                                       value="<?= htmlspecialchars(implode(', ', $editInvestment['danePodstawowe']['dodatkowe'] ?? [])) ?>">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Galerie i pliki</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Galeria zdjęć (każda ścieżka w nowej linii)</label>
                                <textarea name="galeriaZdjec" class="form-control" rows="4"><?= htmlspecialchars(implode("\n", $editInvestment['galeriaZdjec'] ?? [])) ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Galeria rzutów (każda ścieżka w nowej linii)</label>
                                <textarea name="galeriaRzutow" class="form-control" rows="4"><?= htmlspecialchars(implode("\n", $editInvestment['galeriaRzutow'] ?? [])) ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ścieżka do prospektu PDF</label>
                                <input type="text" name="prospektPdf" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['prospektPdf'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Mapa</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL osadzonej mapy (Embed)</label>
                                <input type="text" name="mapaUrl" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['mapa']['embedUrl'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Adres inwestycji</label>
                                <input type="text" name="mapaAdres" class="form-control" 
                                       value="<?= htmlspecialchars($editInvestment['mapa']['adres'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="save_investment" class="btn btn-primary">Zapisz inwestycję</button>
                            <a href="admin.php" class="btn btn-outline-secondary ms-2">Anuluj</a>
                        </div>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- LISTA INWESTYCJI -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Lista inwestycji</h4>
                <a href="?new=1" class="btn btn-primary">+ Nowa inwestycja</a>
            </div>

            <?php $investments = loadInvestments(); ?>
            <?php if (empty($investments['inwestycje'])): ?>
                <div class="alert alert-info">Brak inwestycji. Dodaj pierwszą!</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($investments['inwestycje'] as $inv): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title"><?= htmlspecialchars($inv['nazwa']) ?></h5>
                                        <span class="investment-status-badge status-<?= $inv['status'] ?>">
                                            <?= $inv['status'] ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text text-muted small mt-2">
                                        <?= htmlspecialchars(substr($inv['opis'], 0, 150)) ?>...
                                    </p>
                                    
                                    <div class="mt-3">
                                        <strong>Cena:</strong> <?= htmlspecialchars($inv['danePodstawowe']['cena']) ?><br>
                                        <strong>Powierzchnia:</strong> <?= htmlspecialchars($inv['danePodstawowe']['powierzchnia']) ?><br>
                                        <strong>Liczba zdjęć:</strong> <?= count($inv['galeriaZdjec']) ?><br>
                                        <strong>PDF:</strong> <?= $inv['prospektPdf'] ? '✓' : '✗' ?>
                                    </div>
                                    
                                    <div class="mt-3 d-flex gap-2">
                                        <a href="?edit=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary">Edytuj</a>
                                        <a href="?delete=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Czy na pewno usunąć?')">Usuń</a>
                                        <a href="#" class="btn btn-sm btn-outline-secondary" target="_blank">Podgląd</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>