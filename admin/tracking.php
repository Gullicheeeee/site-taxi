<?php
$pageTitle = 'Statistiques';
require_once 'includes/header.php';

$db = getDB();

// Période sélectionnée
$period = $_GET['period'] ?? '7';
$periodDays = (int)$period;

// Stats générales
$totalVisits = $db->query("SELECT COUNT(*) FROM visits")->fetchColumn();
$todayVisits = $db->query("SELECT COUNT(*) FROM visits WHERE DATE(visited_at) = DATE('now')")->fetchColumn();
$weekVisits = $db->query("SELECT COUNT(*) FROM visits WHERE visited_at >= datetime('now', '-7 days')")->fetchColumn();
$monthVisits = $db->query("SELECT COUNT(*) FROM visits WHERE visited_at >= datetime('now', '-30 days')")->fetchColumn();

// Visites par jour
$visitsByDay = $db->query("
    SELECT DATE(visited_at) as date, COUNT(*) as count
    FROM visits
    WHERE visited_at >= datetime('now', '-{$periodDays} days')
    GROUP BY DATE(visited_at)
    ORDER BY date ASC
")->fetchAll();

// Pages les plus visitées
$topPages = $db->query("
    SELECT page, COUNT(*) as count
    FROM visits
    WHERE visited_at >= datetime('now', '-{$periodDays} days')
    GROUP BY page
    ORDER BY count DESC
    LIMIT 10
")->fetchAll();

// Sources de trafic
$referers = $db->query("
    SELECT
        CASE
            WHEN referer IS NULL OR referer = '' THEN 'Direct'
            WHEN referer LIKE '%google%' THEN 'Google'
            WHEN referer LIKE '%facebook%' THEN 'Facebook'
            WHEN referer LIKE '%instagram%' THEN 'Instagram'
            WHEN referer LIKE '%linkedin%' THEN 'LinkedIn'
            ELSE 'Autre'
        END as source,
        COUNT(*) as count
    FROM visits
    WHERE visited_at >= datetime('now', '-{$periodDays} days')
    GROUP BY source
    ORDER BY count DESC
")->fetchAll();

// Dernières visites
$recentVisits = $db->query("
    SELECT * FROM visits
    ORDER BY visited_at DESC
    LIMIT 20
")->fetchAll();
?>

<div class="page-header d-flex justify-between align-center">
    <div>
        <h2 class="page-title">Statistiques du site</h2>
        <p class="page-subtitle">Suivez les visites et le comportement de vos visiteurs</p>
    </div>
    <div>
        <select onchange="window.location='?period='+this.value" class="form-control" style="width: auto;">
            <option value="7" <?= $period == '7' ? 'selected' : '' ?>>7 derniers jours</option>
            <option value="30" <?= $period == '30' ? 'selected' : '' ?>>30 derniers jours</option>
            <option value="90" <?= $period == '90' ? 'selected' : '' ?>>90 derniers jours</option>
        </select>
    </div>
</div>

<!-- Stats rapides -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?= number_format($totalVisits) ?></div>
        <div class="stat-label">Visites totales</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?= number_format($todayVisits) ?></div>
        <div class="stat-label">Aujourd'hui</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-value"><?= number_format($weekVisits) ?></div>
        <div class="stat-label">Cette semaine</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🗓️</div>
        <div class="stat-value"><?= number_format($monthVisits) ?></div>
        <div class="stat-label">Ce mois</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Graphique des visites -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Évolution des visites</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="visits-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Sources de trafic -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sources de trafic</h3>
        </div>
        <div class="card-body">
            <?php if (empty($referers)): ?>
            <p style="text-align: center; color: var(--gray-500);">Aucune donnée</p>
            <?php else: ?>
            <?php
            $totalReferers = array_sum(array_column($referers, 'count'));
            foreach ($referers as $ref):
                $percent = $totalReferers > 0 ? round($ref['count'] / $totalReferers * 100) : 0;
            ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span><?= e($ref['source']) ?></span>
                    <span style="color: var(--gray-500);"><?= $ref['count'] ?> (<?= $percent ?>%)</span>
                </div>
                <div style="background: var(--gray-200); border-radius: 4px; height: 8px;">
                    <div style="background: var(--primary); border-radius: 4px; height: 100%; width: <?= $percent ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Pages populaires -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pages les plus visitées</h3>
        </div>
        <div class="card-body">
            <?php if (empty($topPages)): ?>
            <p style="text-align: center; color: var(--gray-500);">Aucune donnée</p>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th style="text-align: right;">Visites</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPages as $p): ?>
                    <tr>
                        <td><?= e($p['page'] ?: 'Accueil') ?></td>
                        <td style="text-align: right;"><?= number_format($p['count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dernières visites -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Dernières visites</h3>
        </div>
        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <?php if (empty($recentVisits)): ?>
            <p style="text-align: center; color: var(--gray-500);">Aucune visite enregistrée</p>
            <?php else: ?>
            <?php foreach ($recentVisits as $visit): ?>
            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between;">
                    <strong><?= e($visit['page'] ?: 'Accueil') ?></strong>
                    <small style="color: var(--gray-500);"><?= date('d/m H:i', strtotime($visit['visited_at'])) ?></small>
                </div>
                <small style="color: var(--gray-500);">
                    IP: <?= e(substr($visit['ip'], 0, -3)) ?>***
                </small>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Configuration tracking -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Configuration du Tracking</h3>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 1rem;">
            Pour activer le tracking des visites, ajoutez ce code avant la balise <code>&lt;/body&gt;</code> de chaque page :
        </p>
        <pre style="background: var(--gray-800); color: #fff; padding: 1rem; border-radius: 8px; overflow-x: auto;"><code>&lt;script&gt;
fetch('admin/track.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        page: window.location.pathname,
        referer: document.referrer
    })
});
&lt;/script&gt;</code></pre>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Données pour le graphique
const visitData = <?= json_encode($visitsByDay) ?>;
const labels = visitData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'});
});
const data = visitData.map(d => d.count);

// Créer le graphique
new Chart(document.getElementById('visits-chart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Visites',
            data: data,
            borderColor: '#1a365d',
            backgroundColor: 'rgba(26, 54, 93, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
