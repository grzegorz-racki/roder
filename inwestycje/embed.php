<?php
// inwestycje/embed.php - iframe z listą inwestycji
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: transparent;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .investment-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .investment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(26,47,80,0.1);
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .badge-aktualna { background: #28a745; color: white; }
        .badge-wkrótce { background: #ffc107; color: #000; }
        .badge-archiwum { background: #6c757d; color: white; }
        
        .investment-placeholder {
            height: 250px;
            border: 2px dashed #1A2F50;
            border-radius: 20px;
            background: rgba(255,255,255,0.5);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .filter-btn {
            border-radius: 30px;
            padding: 5px 20px;
            margin: 0 5px 10px;
            font-size: 0.9rem;
        }
        
        .filter-btn.active {
            background: #1A2F50;
            color: white;
            border-color: #1A2F50;
        }
        
        .investment-table td {
            padding: 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .investment-table td:first-child {
            font-weight: 600;
            width: 40%;
        }
        
        /* Animacje AOS wewnątrz iframe */
        [data-aos] {
            pointer-events: none;
        }
        [data-aos].aos-animate {
            pointer-events: auto;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid px-0">
        <?php
        // Wczytaj dane
        $json = file_get_contents(__DIR__ . '/investments.json');
        $data = json_decode($json, true);
        $inwestycje = $data['inwestycje'] ?? [];
        ?>
        
        <?php if (empty($inwestycje)): ?>
            <div class="text-center py-5" data-aos="fade-up">
                <div class="investment-placeholder mx-auto" style="max-width: 500px;">
                    <p class="text-muted mb-0">Nowe inwestycje już wkrótce</p>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Filtry -->
            <div class="text-center mb-4" data-aos="fade-up">
                <button class="btn btn-outline-primary filter-btn active" data-filter="all">Wszystkie</button>
                <button class="btn btn-outline-primary filter-btn" data-filter="aktualna">Aktualne</button>
                <button class="btn btn-outline-primary filter-btn" data-filter="wkrótce">Wkrótce</button>
                <button class="btn btn-outline-primary filter-btn" data-filter="archiwum">Archiwum</button>
            </div>
            
            <!-- Lista inwestycji -->
            <div class="row g-4 investments-container">
                <?php foreach ($inwestycje as $index => $inv): ?>
                    <div class="col-lg-6 investment-item" data-status="<?= $inv['status'] ?>" data-aos="fade-up" data-aos-delay="<?= 50 * $index ?>">
                        <div class="investment-card h-100">
                            <div class="row g-0">
                                <div class="col-md-5">
                                    <?php if (!empty($inv['galeriaZdjec'][0])): ?>
                                        <img src="<?= htmlspecialchars($inv['galeriaZdjec'][0]) ?>" 
                                             class="img-fluid h-100 w-100" 
                                             alt="<?= htmlspecialchars($inv['nazwa']) ?>"
                                             style="object-fit: cover; min-height: 200px; max-height: 250px;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center h-100" style="min-height: 200px;">
                                            <i class="fas fa-building fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-7">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title fw-bold mb-0"><?= htmlspecialchars($inv['nazwa']) ?></h6>
                                            <span class="badge-status badge-<?= $inv['status'] ?>">
                                                <?= $inv['status'] ?>
                                            </span>
                                        </div>
                                        
                                        <p class="small text-muted mb-2">
                                            <?= htmlspecialchars(substr($inv['opis'], 0, 80)) ?>...
                                        </p>
                                        
                                        <table class="investment-table w-100 small">
                                            <tr>
                                                <td>Cena:</td>
                                                <td><?= htmlspecialchars($inv['danePodstawowe']['cena']) ?></td>
                                            </tr>
                                            <tr>
                                                <td>Powierzchnia:</td>
                                                <td><?= htmlspecialchars($inv['danePodstawowe']['powierzchnia']) ?></td>
                                            </tr>
                                            <tr>
                                                <td>Pokoje:</td>
                                                <td><?= $inv['danePodstawowe']['pokoje'] ?></td>
                                            </tr>
                                            <tr>
                                                <td>Adres:</td>
                                                <td class="text-truncate" style="max-width: 150px;">
                                                    <?= htmlspecialchars($inv['mapa']['adres'] ?? '') ?>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <div class="mt-2 d-flex gap-2">
                                            <a href="inwestycja.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-primary" target="_parent">
                                                Szczegóły
                                            </a>
                                            <?php if (!empty($inv['prospektPdf'])): ?>
                                                <a href="<?= htmlspecialchars($inv['prospektPdf']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        // Inicjalizacja AOS
        AOS.init({
            duration: 600,
            once: true,
            mirror: false
        });
        
        // Filtrowanie
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.investment-item').forEach(item => {
                    item.style.display = filter === 'all' || item.dataset.status === filter ? 'block' : 'none';
                });
                
                // Powiadom parent o zmianie wysokości
                setTimeout(sendHeightToParent, 100);
            });
        });
        
        // Przekazywanie wysokości do parent
        function sendHeightToParent() {
            const height = document.body.scrollHeight;
            window.parent.postMessage({
                type: 'resize-iframe',
                height: height
            }, '*');
        }
        
        // Obserwacja zmian wysokości
        const observer = new ResizeObserver(sendHeightToParent);
        observer.observe(document.body);
        
        // Wyślij wysokość po załadowaniu
        window.addEventListener('load', sendHeightToParent);
        window.addEventListener('resize', sendHeightToParent);
    </script>
</body>
</html>