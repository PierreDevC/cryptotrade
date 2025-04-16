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

        /* NEW: Bounce Effect Animation */
        @keyframes bounce {
          0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
          40% {transform: translateY(-20px);}
          60% {transform: translateY(-10px);}
        }
        .bounce-effect {
          animation: bounce 1s ease;
        }

        /* NEW: Dark Red Button Style */
        .btn-danger-dark {
          color: #fff;
          background-color: #a71d2a; /* Darker shade of red */
          border-color: #a71d2a;
        }
        .btn-danger-dark:hover {
          color: #fff;
          background-color: #8f1923; /* Slightly darker on hover */
          border-color: #82171f;
        }
        .btn-danger-dark:focus {
          box-shadow: 0 0 0 0.25rem rgba(196, 55, 69, 0.5); /* Adjust focus shadow color */
        }
        /* Ensure consistent border-radius (matches Bootstrap default) - Adjust if needed */
        /* .grid-item .btn, .grid-item .action-button { border-radius: var(--bs-btn-border-radius, 0.375rem); } */

        /* NEW: Apply consistent large border-radius to all buttons */
        .grid-item .btn,
        .grid-item .action-button,
        .modal-content .btn,
        .modal-content .action-button {
          border-radius: 40px !important; /* Use !important to override potential conflicts */
        }

        /* NEW: Limit height and enable scrolling for transaction history table */
        #profile-history-pane .table-responsive {
          max-height: 400px; /* Adjust this value as needed */
          overflow-y: auto;
        }

        /* --- Dark Mode Adjustments --- */
        [data-bs-theme="dark"] {
          /* Adjust grid item background and text */
          .grid-item {
            background-color: var(--bs-dark-bg-subtle, #1a1d20); /* Slightly lighter than body */
            color: var(--bs-secondary-color); /* Lighter text */
            border: 1px solid var(--bs-border-color-translucent);
          }

          /* Adjust grid item headings */
          .grid-item h5 {
            color: var(--bs-light-color); /* Lighter heading color */
          }
          .grid-item h5:after {
             background-color: var(--bs-primary); /* Keep primary color for accent */
          }

          /* Adjust table header colors */
          .crypto-table thead th {
             background-color: var(--bs-tertiary-bg); /* Darker header background */
             color: var(--bs-emphasis-color); /* Clearer header text */
             border-color: var(--bs-border-color-translucent);
          }

          /* Adjust main balance/investment text color */
          #balanceAmount,
          #investmentAmount {
              /* Ensure balance/investment text is bright */
              color: var(--bs-light-text-emphasis, #f8f9fa) !important;
          }

          /* Adjust custom button styles for dark mode */
          .action-button {
             /* Keep the green, but ensure text contrast */
             color: #fff; /* White text */
          }
          .btn-danger-dark {
             /* Keep the dark red, ensure text contrast */
             color: #fff;
          }

           /* Adjust Navbar Brand */
          .navbar .navbar-brand.brand-logo {
             color: var(--bs-emphasis-color); /* Make brand slightly more prominent */
          }

          /* Adjust Navbar Links */
          .navbar .nav-link {
              color: var(--bs-secondary-color); /* Make nav links clearer */
          }
          .navbar .nav-link.active {
               color: var(--bs-light-text-emphasis); /* Make active link brighter */
          }

          /* Adjust Navbar Background */
          .sticky-top.navbar {
              background-color: var(--bs-body-bg) !important; /* Use body background for dark navbar */
              /* You might need to adjust border color too if it exists */
              border-bottom-color: var(--bs-border-color-translucent) !important;
          }

          /* --- Modal Adjustments --- */
          .modal-content {
              background-color: var(--bs-dark-bg-subtle, #1a1d20);
              color: var(--bs-secondary-color);
              border-color: var(--bs-border-color-translucent);
          }
          .modal-header,
          .modal-footer {
              border-color: var(--bs-border-color-translucent);
          }
          .modal-header .btn-close {
              filter: invert(1) grayscale(100%) brightness(200%); /* Make close button visible */
          }
          .modal-content .nav-tabs .nav-link {
             color: var(--bs-secondary-color);
             border-color: var(--bs-border-color-translucent);
          }
          .modal-content .nav-tabs .nav-link.active {
             color: var(--bs-light-text-emphasis);
             background-color: var(--bs-dark-bg-subtle);
             border-color: var(--bs-primary);
             border-bottom-color: transparent; /* Match Bootstrap */
          }
          .modal-content .table thead th {
              background-color: var(--bs-tertiary-bg);
              color: var(--bs-emphasis-color);
              border-color: var(--bs-border-color-translucent);
          }
          .modal-content .form-label {
             color: var(--bs-light-text-emphasis);
          }
        }
        /* --- End Dark Mode Adjustments --- */
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#crypto-list-card" id="nav-link-cryptos">Cryptomonnaies</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#holdings-card" id="nav-link-holdings">Mes actifs</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#" id="nav-link-transactions" data-bs-toggle="modal" data-bs-target="#profileSettingsModal">Transactions</a></li>
                </ul>

                <!-- Dark Mode Toggle Switch -->
                <div class="form-check form-switch me-3 d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch" style="cursor: pointer;">
                    <label class="form-check-label ms-2" for="darkModeSwitch"><i id="darkModeIcon" class="fa-solid fa-sun"></i></label>
                </div>

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
                        <div class="row"><div class="col-12"><div class="chart-container"><canvas id="cryptoChart"></canvas></div></div></div>
                    </div>
                </div>
                <div class="row g-4"> <div class="col-12 grid-item">
                        <h5>Détails du Compte</h5>
                        <div class="row align-items-center"><div class="col-md-6">
                                <p class="mb-1"><strong>Type de Compte:</strong> <span class="account-type">Chargement...</span></p>
                                <p class="mb-1"><strong>Statut:</strong> <span class="badge account-status">Chargement...</span></p>
                                <p class="mb-1 account-last-login"><strong>Dernière connexion:</strong> <span id="lastLoginValue">Chargement...</span></p>
                        </div><div class="col-md-6 text-md-end">
                                <p class="text-muted mb-1 d-flex align-items-center justify-content-md-end">Solde Actuel (CAD)<button id="toggleBalance" class="btn btn-sm ms-2 p-0 border-0 shadow-none"><i class="fa-solid fa-eye"></i></button></p>
                                <h2 id="balanceAmount" class="display-5 fw-bold text-dark mb-0" style="font-family: sans-serif;">Chargement...</h2>
                                <h2 id="hiddenBalance" class="display-5 fw-bold text-dark mb-0 d-none" style="font-family: sans-serif;">******$ CAD</h2>
                                <p class="text-muted mb-1 mt-2 d-flex align-items-center justify-content-md-end">Investissements Actifs (CAD)</p>
                                <h4 id="investmentAmount" class="fw-bold text-dark mb-0" style="font-family: sans-serif;">Chargement...</h4>
                                <h4 id="hiddenInvestment" class="fw-bold text-dark mb-0 d-none" style="font-family: sans-serif;">******$ CAD</h4>
                                <p class="text-success mt-2 weekly-gain"><i class="fa-solid fa-spinner fa-spin me-1"></i> Chargement...</p>
                        </div></div>
                        <!-- NEW Buttons -->
                        <div class="d-flex justify-content-start gap-2 mt-4"> <!-- Added container for buttons -->
                            <button id="viewTransactionsBtn" class="action-button" data-bs-toggle="modal" data-bs-target="#profileSettingsModal" style="padding: 8px 12px; font-size: 0.9rem;">
                                <i class="fa-solid fa-list-check me-1"></i> Consulter Transactions
                            </button>
                            <button id="accountSettingsBtn" class="action-button text-decoration-none" data-bs-toggle="modal" data-bs-target="#profileSettingsModal" style="padding: 8px 12px; font-size: 0.9rem;">
                                <i class="fa-solid fa-gear me-1"></i> Paramètres du Compte
                            </button>
                        </div>
                </div></div>
                <div class="row g-4" id="holdings-section"> <div class="col-12 grid-item" id="holdings-card">
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
                                    <label for="cryptoAmount" class="form-label">Montant (CAD)</label><div class="input-group mb-3"><span class="input-group-text">$</span><input type="number" class="form-control" id="cryptoAmount" placeholder="0.00" step="0.01"><span class="input-group-text">CAD</span></div>
                        </div><div class="col-md-6">
                                    <label for="cryptoQuantity" class="form-label">Quantité</label><input type="number" class="form-control" id="cryptoQuantity" placeholder="0.00000000" step="any">
                        </div></div><div class="d-flex justify-content-start gap-2 mt-3">
                                <button id="buyButton" class="action-button text-decoration-none" disabled><i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Acheter</button>
                                <button id="sellButton" class="btn btn-danger-dark" disabled><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Vendre</button>
                                <button id="sellAllButton" class="btn btn-danger-dark" disabled title="Vendre la quantité maximale détenue"><i class="fa-solid fa-sack-dollar me-1"></i> Vendre Tout</button>
                        </div><div class="alert alert-info mt-3 d-none" id="tradeInfo"><i class="fa-solid fa-info-circle me-2"></i><span id="tradeInfoText"></span></div></div>
                    </div>
                    <div class="col-12 grid-item" style="overflow-y: auto;" id="crypto-list-card">
                        <h5>Cryptomonnaies</h5>
                         <div class="table-responsive"><table class="table crypto-table"><thead><tr>
                                        <th scope="col" style="width: 50px;"></th><th scope="col">#</th><th scope="col">Crypto</th><th scope="col">Price (CAD)</th>
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
                        <li><strong>Prix Unitaire Estimé (CAD) :</strong> <span id="confirmPrice"></span></li>
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
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="profileSettingsModalLabel">Profil et Paramètres</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Tab Navigation -->
            <nav>
              <div class="nav nav-tabs mb-3" id="profileTab" role="tablist">
                <button class="nav-link active" id="profile-user-tab" data-bs-toggle="tab" data-bs-target="#profile-user-pane" type="button" role="tab" aria-controls="profile-user-pane" aria-selected="true">Mes Informations</button>
                <!-- Admin tab link (initially hidden) -->
                <button class="nav-link d-none" id="profile-admin-tab" data-bs-toggle="tab" data-bs-target="#profile-admin-pane" type="button" role="tab" aria-controls="profile-admin-pane" aria-selected="false">Gestion Cryptos (Admin)</button>
                <!-- Transaction History tab link -->
                <button class="nav-link" id="profile-history-tab" data-bs-toggle="tab" data-bs-target="#profile-history-pane" type="button" role="tab" aria-controls="profile-history-pane" aria-selected="false">Historique</button>
              </div>
            </nav>

            <!-- Tab Content -->
            <div class="tab-content" id="profileTabContent">

              <!-- User Information Tab Pane -->
              <div class="tab-pane fade show active" id="profile-user-pane" role="tabpanel" aria-labelledby="profile-user-tab" tabindex="0">
                <h5 class="mb-3"><i class="fas fa-user-edit me-2"></i>Mes Informations</h5>
                <div id="userProfileMessage" class="mb-3"></div> <!-- For success/error messages -->
                <form id="userProfileForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="profileFullname" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="profileFullname" required>
                        </div>
                        <div class="col-md-6">
                            <label for="profileEmail" class="form-label">Adresse Courriel</label>
                            <input type="email" class="form-control" id="profileEmail" required>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small mb-2">Pour changer votre mot de passe, remplissez les champs ci-dessous. Sinon, laissez-les vides.</p>
                     <div class="row g-3 mb-3">
                         <div class="col-md-6">
                            <label for="profileCurrentPassword" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="profileCurrentPassword" placeholder="Requis si changement de mot de passe">
                        </div>
                     </div>
                     <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="profileNewPassword" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="profileNewPassword">
                        </div>
                        <div class="col-md-6">
                            <label for="profileConfirmPassword" class="form-label">Confirmer nouveau mot de passe</label>
                            <input type="password" class="form-control" id="profileConfirmPassword">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                         <button type="submit" class="action-button text-decoration-none" id="saveProfileButton"><i class="fas fa-save me-1"></i> Sauvegarder Profil</button>
                    </div>
                </form>
              </div>

              <!-- Admin Currency Management Tab Pane (content moved here, still controlled by d-none on inner div) -->
              <div class="tab-pane fade" id="profile-admin-pane" role="tabpanel" aria-labelledby="profile-admin-tab" tabindex="0">
                <div id="adminCurrencyManagementSection" class="d-none"> <!-- Keep d-none here, controlled by JS -->
                    <h5 class="mb-3"><i class="fas fa-coins me-2"></i>Gestion des Cryptomonnaies</h5>
                    <div id="adminCurrencyMessage" class="mb-3"></div> <!-- For success/error messages -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="adminSelectCurrency" class="form-label">Sélectionner / Ajouter</label>
                            <select class="form-select" id="adminSelectCurrency">
                                <option value="new" selected>-- Ajouter Nouvelle Crypto --</option>
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>
                    </div>

                    <form id="adminCurrencyForm" class="mt-3">
                        <input type="hidden" id="adminCurrencyId">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="adminCurrencyName" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="adminCurrencyName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="adminCurrencySymbol" class="form-label">Symbole (ex: BTC)</label>
                                <input type="text" class="form-control" id="adminCurrencySymbol" required maxlength="10">
                            </div>
                        </div>
                         <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="adminCurrencyPrice" class="form-label">Prix Actuel (CAD)</label>
                                <input type="number" step="any" class="form-control" id="adminCurrencyPrice" required>
                            </div>
                            <div class="col-md-6">
                                <label for="adminCurrencyChange" class="form-label">Variation 24h (%)</label>
                                <input type="number" step="any" class="form-control" id="adminCurrencyChange" required>
                            </div>
                        </div>
                         <div class="row g-3 mb-3">
                             <div class="col-md-6">
                                <label for="adminCurrencyMarketCap" class="form-label">Market Cap (CAD)</label>
                                <input type="number" step="any" class="form-control" id="adminCurrencyMarketCap" required>
                            </div>
                             <div class="col-md-6">
                                 <label for="adminCurrencyVolatility" class="form-label">Volatilité Base (ex: 0.015)</label>
                                <input type="number" step="0.0001" class="form-control" id="adminCurrencyVolatility" required>
                            </div>
                        </div>
                         <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="adminCurrencyTrend" class="form-label">Tendance Base (ex: 0.001)</label>
                                <input type="number" step="0.0001" class="form-control" id="adminCurrencyTrend" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="action-button text-decoration-none" id="adminAddCurrencyBtn"><i class="fas fa-plus me-1"></i> Ajouter</button>
                            <button type="button" class="btn btn-primary" id="adminUpdateCurrencyBtn" disabled><i class="fas fa-save me-1"></i> Mettre à jour</button>
                            <button type="button" class="btn btn-danger" id="adminDeleteCurrencyBtn" disabled><i class="fas fa-trash me-1"></i> Supprimer</button>
                        </div>
                    </form>
                </div>
              </div>

              <!-- Transaction History Tab Pane -->
              <div class="tab-pane fade" id="profile-history-pane" role="tabpanel" aria-labelledby="profile-history-tab" tabindex="0">
                <h5 class="mb-3"><i class="fas fa-history me-2"></i>Historique des Transactions</h5>
                <div class="d-flex justify-content-end gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-secondary" id="downloadCsvBtn" disabled><i class="fas fa-file-csv me-1"></i> Télécharger CSV</button>
                    <button class="btn btn-sm btn-outline-secondary" id="downloadPdfBtn" disabled><i class="fas fa-file-pdf me-1"></i> Télécharger PDF</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Type</th>
                                <th scope="col">Crypto</th>
                                <th scope="col">Quantité</th>
                                <th scope="col">Prix/Unité (CAD)</th>
                                <th scope="col">Montant Total (CAD)</th>
                            </tr>
                        </thead>
                        <tbody id="transactionHistoryTableBody">
                            <!-- Transactions will be loaded here by JS -->
                            <tr class="loading-placeholder"><td colspan="6" class="text-center py-4">Chargement de l'historique... <i class="fas fa-spinner fa-spin"></i></td></tr>
                        </tbody>
                    </table>
                </div>
              </div>

            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>
    <!-- End Profile & Settings Modal -->

    <!-- JavaScript - Use root-relative path -->
    <script src="/cryptotrade/js/dashboard_ajax.js" defer></script>

</body>
</html>