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
        .grid-item {
            background-color: white;
            border-radius: 12px; /* Slightly more rounded */
            padding: 20px; /* Ensure consistent padding */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05); /* Subtle shadow */
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; /* Smooth transition */
            margin-bottom: 2rem; /* Add some space below items */
        }
        .grid-item:hover {
            transform: translateY(-5px); /* Slightly increased lift */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); /* Enhanced shadow on hover */
        }
        .grid-item h5 {
            color: #495057; /* Darker heading color */
            font-weight: 600; /* Slightly bolder headings */
            margin-bottom: 1.5rem; /* More space below heading */
            position: relative;
            padding-bottom: 0.5rem;
        }
        .grid-item h5:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 30px;
            height: 2px;
            background-color: #0d6efd; /* Bootstrap primary blue or your theme color */
        }
        .crypto-table {
            margin-top: 1rem;
            border-collapse: separate; /* Needed for border-radius on table */
            border-spacing: 0;
            overflow: hidden; /* Clip content to border-radius */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .crypto-table thead th {
            background-color: #f8f9fa; /* Lighter header */
            color: #343a40;
            border-bottom-width: 1px;
            font-weight: 600;
            white-space: nowrap;
        }
        .crypto-table tbody tr {
            transition: background-color 0.15s ease-in-out;
        }
        .crypto-table tbody tr:hover {
            background-color: #e9ecef; /* Subtle hover for rows */
        }
        .chart-container {
            position: relative;
            height: 250px; /* Adjust height as needed */
            width: 100%;
        }
        .crypto-modal-details {
            /* Styles for modal content if needed */
        }
        .crypto-modal-details h3 {
            /* Styles for modal title */
        }
        /* ... other styles ... */
        .loading-placeholder td {
            color: #adb5bd; /* Lighter grey for loading text */
            font-style: italic;
        }
        .account-last-login strong {
             color: #6c757d; /* Softer color for label */
        }
        #balanceAmount,
        #hiddenBalance {
            color: #212529; /* Ensure dark text for balance */
        }
        .weekly-gain {
             font-size: 0.9rem;
        }
        /* Ensure buttons in table have consistent size */
        .crypto-table .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="sticky-top navbar navbar-expand-lg py-4 border-bottom" style="background-color: rgba(255, 255, 255, 0.8);">
        <div class="container">
            <a class="navbar-brand brand-logo" href="/cryptotrade/">CryptoTrade</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="/cryptotrade/dashboard">Acceuil du Dashboard</a>
                    </li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Cryptomonnaies</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#holdings-section">Portefeuille</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Alertes</a></li>
                </ul>
                <div class="d-flex gap-2"> <div class="nav-item dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle font-weight-bold" type="button" id="navbarDropdown" data-bs-toggle="dropdown" aria-expanded="false">Compte <i class="fa-solid fa-user"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileSettingsModal"><i class="fa-solid fa-id-card me-2"></i>Profil et Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/cryptotrade/logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Déconnexion</a></li>
                        </ul>
                </div></div>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <div class="container-fluid p-4">
        <div class="row gx-5 gy-4">
            <div class="col-lg-6">
                <div class="row g-4">
                    <div class="col-12 grid-item">
                        <h5 class="mb-3">Performance du Compte</h5>
                        <div class="row"><div class="col-md-3 d-flex flex-column justify-content-center gap-3 mb-md-0 mb-4">
                                <a href="#" class="action-button text-decoration-none" style="padding: 10px 15px; font-size: 13px; max-width: 180px; text-align: center; margin: 0 auto;"><i class="fa-solid fa-chart-line me-2"></i>Télécharger Analyses</a>
                                <a href="#" class="action-button text-decoration-none" style="padding: 10px 15px; font-size: 13px; max-width: 180px; text-align: center; margin: 0 auto;"><i class="fa-solid fa-file-invoice me-2"></i>Télécharger Relevés</a>
                        </div><div class="col-md-9"><div class="chart-container"><canvas id="cryptoChart"></canvas></div></div></div>
                    </div>
                </div>
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
                <div class="row g-4" id="holdings-section"> <div class="col-12 grid-item">
                        <h5>Mes Actifs</h5>
                        <div class="table-responsive"><table class="table crypto-table"><thead><tr>
                                        <th scope="col">Crypto</th><th scope="col">Quantité</th><th scope="col">Prix Actuel (CAD)</th>
                                        <th scope="col">Valeur Totale (CAD)</th><th scope="col">Variation 24h</th>
                        </tr></thead><tbody id="holdingsTableBody"><tr class="loading-placeholder"><td colspan="5">Chargement des actifs... <i class="fas fa-spinner fa-spin"></i></td></tr></tbody></table></div>
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="row g-4">
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
                    <div class="col-12 grid-item" style="overflow-y: auto;">
                        <h5>Cryptomonnaies</h5>
                         <div class="table-responsive"><table class="table crypto-table"><thead><tr>
                                        <th scope="col" style="width: 50px;"></th><th scope="col">#</th><th scope="col">Crypto</th><th scope="col">Price (USD)</th>
                                        <th scope="col">24h Change</th><th scope="col">Market Cap</th><th scope="col">Graphique</th>
                                        <th scope="col">Action</th>
                        </tr></thead><tbody id="cryptoListTableBody"><tr class="loading-placeholder"><td colspan="8">Chargement des cryptomonnaies... <i class="fas fa-spinner fa-spin"></i></td></tr></tbody></table></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirmer la Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Veuillez confirmer les détails de votre transaction :</p>
                    <ul id="confirmationDetails" class="list-unstyled">
                        <li><strong>Action :</strong> <span id="confirmAction"></span></li>
                        <li><strong>Crypto :</strong> <span id="confirmCryptoName"></span> (<span id="confirmCryptoSymbol"></span>)</li>
                        <li><strong>Quantité :</strong> <span id="confirmQuantity"></span></li>
                        <li><strong>Prix Unitaire Estimé (USD) :</strong> <span id="confirmPriceUsd"></span></li>
                        <li><strong>Valeur Totale Estimée (CAD) :</strong> <span id="confirmAmountCad"></span></li>
                    </ul>
                    <div id="confirmationError" class="alert alert-danger d-none mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirmTransactionBtn">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Confirmation Modal -->

    <!-- Crypto Chart Modal -->
    <div class="modal fade" id="cryptoChartModal" tabindex="-1" aria-labelledby="cryptoChartModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="cryptoChartModalLabel">Graphique : Chargement...</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="text-center" id="modalChartLoading">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Chargement du graphique...</p>
            </div>
            <div style="height: 400px;" id="modalChartContainer" class="d-none">
                 <canvas id="modalCryptoChartCanvas"></canvas>
            </div>
            <div id="modalChartError" class="alert alert-danger d-none">
                Impossible de charger le graphique.
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- End Crypto Chart Modal -->

    <!-- Profile & Settings Modal -->
    <div class="modal fade" id="profileSettingsModal" tabindex="-1" aria-labelledby="profileSettingsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="profileSettingsModalLabel">Profil et Paramètres</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Contenu de la modale (Profil et Paramètres) sera ajouté ici -->
            <p>Contenu du profil et des paramètres...</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="button" class="btn btn-primary">Sauvegarder les changements</button>
          </div>
        </div>
      </div>
    </div>
    <!-- End Profile & Settings Modal -->

    <!-- JavaScript - Use root-relative path -->
    <script src="/cryptotrade/js/dashboard_ajax.js" defer></script>

</body>
</html>