<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoTrade Dashboard</title>
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/f09d5e5c92.js" crossorigin="anonymous"></script>
    <!-- SwiperJS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.3.0/chart.umd.min.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS - Use root-relative path -->
    <link rel="stylesheet" href="/cryptotrade/css/style.css">
    <style>
        /* Styles remain the same */
        .grid-item { background-color: white; border-radius: 10px; /* ... */ }
        .grid-item:hover { transform: translateY(-3px); /* ... */ }
        .grid-item h5 { /* ... */ }
        .grid-item h5:after { /* ... */ }
        .crypto-table { /* ... */ }
        .crypto-table thead th { /* ... */ }
        .crypto-table tbody tr { /* ... */ }
        .crypto-table tbody tr:hover { /* ... */ }
        .chart-container { /* ... */ }
        .crypto-modal-details { /* ... */ }
        .crypto-modal-details h3 { /* ... */ }
        /* ... other styles ... */
        .loading-placeholder td { color: #ccc; font-style: italic; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="sticky-top navbar navbar-expand-lg py-4 border-bottom" style="background-color: rgba(255, 255, 255, 0.8);">
        <div class="container">
            {/* Link to root route */}
            <a class="navbar-brand brand-logo" href="/cryptotrade/">CryptoTrade</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        {/* Link to route */}
                        <a class="nav-link active" aria-current="page" href="/cryptotrade/dashboard">Acceuil du Dashboard</a>
                    </li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Cryptomonnaies</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#holdings-section">Portefeuille</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Alertes</a></li>
                </ul>
                <div class="d-flex gap-2"> <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle font-weight-bold" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Compte <i class="fa-solid fa-user"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-id-card me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear me-2"></i>Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                             {/* Link to route */}
                            <li><a class="dropdown-item" href="/cryptotrade/logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Déconnexion</a></li>
                        </ul>
                </div></div>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <div class="container-fluid p-4">
        {/* Grid structure remains the same, IDs added for JS targeting */}
        <div class="row gx-5 gy-4">
            {/* Left Column */}
            <div class="col-lg-6">
                <div class="row g-4">
                    {/* Account performance */}
                    <div class="col-12 grid-item">
                        <h5 class="mb-3">Performance du Compte</h5>
                        <div class="row"><div class="col-md-3 d-flex flex-column justify-content-center gap-3 mb-md-0 mb-4">
                                <a href="#" class="action-button text-decoration-none" style="padding: 10px 15px; font-size: 13px; max-width: 180px; text-align: center; margin: 0 auto;"><i class="fa-solid fa-chart-line me-2"></i>Télécharger Analyses</a>
                                <a href="#" class="action-button text-decoration-none" style="padding: 10px 15px; font-size: 13px; max-width: 180px; text-align: center; margin: 0 auto;"><i class="fa-solid fa-file-invoice me-2"></i>Télécharger Relevés</a>
                        </div><div class="col-md-9"><div class="chart-container"><canvas id="cryptoChart"></canvas></div></div></div>
                    </div>
                </div>
                {/* Account details */}
                <div class="row g-4"> <div class="col-12 grid-item">
                        <h5>Détails du Compte</h5>
                        <div class="row align-items-center"><div class="col-md-6">
                                <p class="mb-1"><strong>Type de Compte:</strong> <span class="account-type">Chargement...</span></p>
                                <p class="mb-1"><strong>Statut:</strong> <span class="badge account-status">Chargement...</span></p>
                                <p class="mb-1 account-last-login"><strong>Dernière connexion:</strong> Chargement...</p>
                        </div><div class="col-md-6 text-md-end">
                                <p class="text-muted mb-1 d-flex align-items-center justify-content-md-end">Solde Actuel (CAD)<button id="toggleBalance" class="btn btn-sm ms-2 p-0 border-0 shadow-none"><i class="fa-solid fa-eye"></i></button></p>
                                <h2 id="balanceAmount" class="display-5 fw-bold text-dark mb-0" style="font-family: sans-serif;">Chargement...</h2>
                                <h2 id="hiddenBalance" class="display-5 fw-bold text-dark mb-0 d-none" style="font-family: sans-serif;">******$ CAD</h2>
                                <p class="text-success mt-2 weekly-gain"><i class="fa-solid fa-spinner fa-spin me-1"></i> Chargement...</p>
                        </div></div>
                </div></div>
                {/* Holdings */}
                <div class="row g-4" id="holdings-section"> <div class="col-12 grid-item">
                        <h5>Mes Actifs</h5>
                        <div class="table-responsive"><table class="table crypto-table"><thead><tr>
                                        <th scope="col">Crypto</th><th scope="col">Quantité</th><th scope="col">Prix Actuel (CAD)</th>
                                        <th scope="col">Valeur Totale (CAD)</th><th scope="col">Variation 24h</th>
                        </tr></thead><tbody id="holdingsTableBody"><tr class="loading-placeholder"><td colspan="5">Chargement des actifs... <i class="fas fa-spinner fa-spin"></i></td></tr></tbody></table></div>
                </div></div>
            </div>
            {/* Right Column */}
            <div class="col-lg-6">
                <div class="row g-4">
                    {/* Buy/Sell */}
                    <div class="col-12 grid-item">
                        <h5>Achat/Vente de Cryptomonnaies</h5>
                        <div id="cryptoTradeForm" class="p-2"><div class="mb-3">
                                <h6 id="selectedCryptoName">Sélectionnez une cryptomonnaie...</h6><p id="selectedCryptoPrice" class="text-muted mb-1">Prix actuel: --</p>
                        </div><div class="row g-3"><div class="col-md-6">
                                    <label for="cryptoAmount" class="form-label">Montant (USD)</label><div class="input-group mb-3"><span class="input-group-text">$</span><input type="number" class="form-control" id="cryptoAmount" placeholder="0.00" step="0.01"></div>
                        </div><div class="col-md-6">
                                    <label for="cryptoQuantity" class="form-label">Quantité</label><input type="number" class="form-control" id="cryptoQuantity" placeholder="0.00000000" step="any">
                        </div></div><div class="d-flex justify-content-between mt-3">
                                <button id="buyButton" class="btn btn-success" disabled><i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Acheter</button>
                                <button id="sellButton" class="btn btn-danger" disabled><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Vendre</button>
                        </div><div class="alert alert-info mt-3 d-none" id="tradeInfo"><i class="fa-solid fa-info-circle me-2"></i><span id="tradeInfoText"></span></div></div>
                    </div>
                    {/* Crypto List */}
                    <div class="col-12 grid-item" style="overflow-y: auto;">
                        <h5>Cryptomonnaies</h5>
                         <div class="table-responsive"><table class="table crypto-table"><thead><tr>
                                        <th scope="col" style="width: 50px;"></th><th scope="col">#</th><th scope="col">Crypto</th><th scope="col">Price (USD)</th>
                                        <th scope="col">24h Change</th><th scope="col">Market Cap</th><th scope="col">Graphique</th>
                        </tr></thead><tbody id="cryptoListTableBody"><tr class="loading-placeholder"><td colspan="7">Chargement des cryptomonnaies... <i class="fas fa-spinner fa-spin"></i></td></tr></tbody></table></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals (Structure remains the same) -->
    <div class="modal fade" id="confirmationModal">...</div>
    <div class="modal fade" id="cryptoChartModal">...</div>

    <!-- JavaScript - Use root-relative path -->
    <script src="/cryptotrade/js/dashboard_ajax.js" defer></script>

</body>
</html>