/**
 * Développeur assignés(s) :
 * Entité : Classe 'Admin' de la couche Models
 */

// Ajouté: Log pour vérifier mes variables globales CSRF
console.log("CSRF Check:", { token: typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : 'NOT DEFINED', fieldName: typeof CSRF_FIELD_NAME !== 'undefined' ? CSRF_FIELD_NAME : 'NOT DEFINED' });

// Constantes globales (via PHP - elles doivent être définies avant)
// const CSRF_TOKEN = \"...\"; // Exemple, injecté par PHP
// const CSRF_FIELD_NAME = \"_csrf_token\"; // Exemple
// const BASE_URL = \"http://localhost/cryptotrade\"; // Exemple, injecté par PHP

// URL de base pour les appels API
// const API_BASE_URL = \"/cryptotrade/api\"; // Changé : j'utilise l'URL absolue
const API_BASE_URL = `${BASE_URL}/api`; // J'utilise le BASE_URL injecté

// Instances de graphiques (pour gérer les mises à jour/destruction)
let portfolioChartInstance = null;

// Ajouté : Définition de la fonction fetchWithCsrf
/**
 * Nouveau : Wrapper pour fetch qui ajoute le token CSRF au body pour POST/DELETE.
 * Gère le parsing JSON et les erreurs de base.
 */
async function fetchWithCsrf(url, options = {}) {
    const method = options.method ? options.method.toUpperCase() : 'GET';

    // J'ajoute le token CSRF pour les requêtes qui modifient l'état
    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
        // Je vérifie que le body existe et est un objet ou FormData
        let bodyData;
        if (options.body instanceof FormData) {
            bodyData = options.body;
        } else if (typeof options.body === 'string') {
            try {
                bodyData = JSON.parse(options.body);
            } catch (e) {
                console.error('Impossible de parser options.body en JSON. Assurez-vous que c\'est une chaîne JSON valide ou un objet/FormData.');
                bodyData = {}; // Fallback à un objet vide
            }
        } else {
            bodyData = options.body || {};
        }

        // J'ajoute le token CSRF
        if (bodyData instanceof FormData) {
            bodyData.append(CSRF_FIELD_NAME, CSRF_TOKEN);
        } else {
             // Je suppose que c'est un objet simple pour JSON.stringify
            bodyData[CSRF_FIELD_NAME] = CSRF_TOKEN;
        }

        // Je réassigne le body modifié
        // Si c'était FormData, je le garde en FormData
        // Sinon, je stringify l'objet pour JSON
        if (!(bodyData instanceof FormData)) {
            options.body = JSON.stringify(bodyData);
            // Je m'assure que Content-Type est défini pour JSON
            if (!options.headers) {
                options.headers = {};
            }
            if (!options.headers['Content-Type']) {
                options.headers['Content-Type'] = 'application/json';
            }
        }
    }

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            // J'essaie de parser le message d'erreur JSON
            let errorData = { message: `Erreur HTTP ! Statut: ${response.status}` };
            try {
                const errorJson = await response.json();
                if (errorJson && errorJson.message) {
                    errorData.message = errorJson.message;
                }
            } catch (e) {
                // J'ignore si la réponse n'est pas JSON
            }
            // Je log l'erreur avec plus de contexte
             console.error(`Erreur Fetch pour ${url}: ${response.status} - ${errorData.message}`);
             // J'ajoute le code de statut à l'objet d'erreur pour une gestion plus spécifique
             errorData.statusCode = response.status;
            throw new Error(errorData.message, { cause: errorData });
        }
        // Je gère les réponses sans contenu (ex: DELETE réussi)
        if (response.status === 204) {
            return { success: true }; // Ou null/undefined selon la convention
        }
        return await response.json();
    } catch (error) {
        console.error('Erreur Fetch :', error);
        // Je relance l'erreur pour qu'elle soit attrapée par la fonction appelante
        // J'inclus le code de statut si dispo dans la cause de l'erreur fetch
        const statusCode = error.cause?.statusCode;
        throw new Error(error.message, { cause: { statusCode } });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const API_BASE_URL = `${BASE_URL}/api`; // J'utilise le BASE_URL injecté

    // --- Sélecteurs ---
    const accountTypeEl = document.querySelector('.account-type');
    const accountStatusEl = document.querySelector('.account-status');
    const accountLastLoginEl = document.getElementById('lastLoginValue');
    const balanceAmountEl = document.getElementById('balanceAmount');
    const hiddenBalanceEl = document.getElementById('hiddenBalance');
    const toggleBalanceBtn = document.getElementById('toggleBalance');
    const weeklyGainEl = document.querySelector('.weekly-gain');
    const portfolioTotalValueEl = document.getElementById('portfolioTotalValue');
    const portfolioCryptoValueEl = document.getElementById('portfolioCryptoValue');
    const portfolioChange24hEl = document.getElementById('portfolioChange24h');
    const investmentAmountEl = document.getElementById('investmentAmount');
    const hiddenInvestmentEl = document.getElementById('hiddenInvestment');
    const holdingsTableBody = document.getElementById('holdingsTableBody');
    const cryptoListTableBody = document.getElementById('cryptoListTableBody');
    const cryptoChartModalEl = document.getElementById('cryptoChartModal');
    const modalChartLoadingEl = document.getElementById('modalChartLoading');
    const modalChartContainerEl = document.getElementById('modalChartContainer');
    const modalChartCanvas = document.getElementById('modalCryptoChartCanvas');
    const modalChartErrorEl = document.getElementById('modalChartError');
    const modalChartTitleEl = document.getElementById('cryptoChartModalLabel');
    const nameElBuySell = document.getElementById('selectedCryptoName');
    const priceElBuySell = document.getElementById('selectedCryptoPrice');
    const buyButton = document.getElementById('buyButton');
    const sellButton = document.getElementById('sellButton');
    const amountInput = document.getElementById('cryptoAmount');
    const quantityInput = document.getElementById('cryptoQuantity');
    const sellAllButton = document.getElementById('sellAllButton');
    const tradeInfoEl = document.getElementById('tradeInfo');
    const tradeInfoTextEl = document.getElementById('tradeInfoText');

    // --- Sélecteurs Formulaire Trade Modale ---
    const modalCryptoAmountInput = document.getElementById('modalCryptoAmount');
    const modalCryptoQuantityInput = document.getElementById('modalCryptoQuantity');
    const modalBuyButton = document.getElementById('modalBuyButton');
    const modalSellButton = document.getElementById('modalSellButton');
    const modalSellAllButton = document.getElementById('modalSellAllButton');
    const modalTradeInfoEl = document.getElementById('modalTradeInfo');
    const modalTradeInfoTextEl = document.getElementById('modalTradeInfoText');

    // --- Placeholders Infos Modale ---
    const modalInfoPriceEl = document.getElementById('modalInfoPrice');
    const modalInfoChangeEl = document.getElementById('modalInfoChange');
    const modalInfoMarketCapEl = document.getElementById('modalInfoMarketCap');

    // --- Sélecteurs Gestion Devises Admin (Nouveau) ---
    const adminCurrencySection = document.getElementById('adminCurrencyManagementSection');
    const adminCurrencySelect = document.getElementById('adminSelectCurrency');
    const adminCurrencyForm = document.getElementById('adminCurrencyForm');
    const adminCurrencyIdInput = document.getElementById('adminCurrencyId');
    const adminCurrencyNameInput = document.getElementById('adminCurrencyName');
    const adminCurrencySymbolInput = document.getElementById('adminCurrencySymbol');
    const adminCurrencyPriceInput = document.getElementById('adminCurrencyPrice');
    const adminCurrencyChangeInput = document.getElementById('adminCurrencyChange');
    const adminCurrencyMarketCapInput = document.getElementById('adminCurrencyMarketCap');
    const adminCurrencyVolatilityInput = document.getElementById('adminCurrencyVolatility');
    const adminCurrencyTrendInput = document.getElementById('adminCurrencyTrend');
    const adminAddBtn = document.getElementById('adminAddCurrencyBtn');
    const adminUpdateBtn = document.getElementById('adminUpdateCurrencyBtn');
    const adminDeleteBtn = document.getElementById('adminDeleteCurrencyBtn');
    const adminMessageArea = document.getElementById('adminCurrencyMessage');

    // Sélecteurs Modale Confirmation
    const confirmationModalEl = document.getElementById('confirmationModal');
    const confirmationModal = confirmationModalEl ? new bootstrap.Modal(confirmationModalEl) : null;
    const confirmActionEl = document.getElementById('confirmAction');
    const confirmCryptoNameEl = document.getElementById('confirmCryptoName');
    const confirmCryptoSymbolEl = document.getElementById('confirmCryptoSymbol');
    const confirmQuantityEl = document.getElementById('confirmQuantity');
    const confirmPriceEl = document.getElementById('confirmPrice');
    const confirmAmountCadEl = document.getElementById('confirmAmountCad');
    const confirmErrorEl = document.getElementById('confirmationError');
    const confirmTransactionBtn = document.getElementById('confirmTransactionBtn');

    // --- Sélecteurs Paramètres Profil User (Nouveau) ---
    const profileSettingsModalEl = document.getElementById('profileSettingsModal');
    const userProfileForm = document.getElementById('userProfileForm');
    const profileFullnameInput = document.getElementById('profileFullname');
    const profileEmailInput = document.getElementById('profileEmail');
    const profileCurrentPasswordInput = document.getElementById('profileCurrentPassword');
    const profileNewPasswordInput = document.getElementById('profileNewPassword');
    const profileConfirmPasswordInput = document.getElementById('profileConfirmPassword');
    const saveProfileButton = document.getElementById('saveProfileButton');
    const userProfileMessageArea = document.getElementById('userProfileMessage');

    // --- Nouveau : Sélecteurs Onglet Statistiques ---
    const statsUnrealizedPLEl = document.getElementById('statsUnrealizedPL');
    const profileStatsTabBtn = document.getElementById('profile-stats-tab');
    const statsTotalCryptoValueEl = document.getElementById('statsTotalCryptoValue');
    const statsFiatBalanceEl = document.getElementById('statsFiatBalance');
    const allocationChartCanvas = document.getElementById('allocationChartCanvas');
    const statsAllocationLoadingEl = document.getElementById('statsAllocationLoading');
    const statsAllocationErrorEl = document.getElementById('statsAllocationError');
    const statsAllocationEmptyEl = document.getElementById('statsAllocationEmpty');
    const downloadAllocationChartBtn = document.getElementById('downloadAllocationChartBtn');

    // --- Sélecteurs Historique Transactions (Nouveau) ---
    const transactionHistoryTableBody = document.getElementById('transactionHistoryTableBody');
    const downloadCsvBtn = document.getElementById('downloadCsvBtn');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');

    // --- Nouveau : Liens Navbar & Cartes Cibles pour Effets ---
    const navLinkCryptos = document.getElementById('nav-link-cryptos');
    const navLinkHoldings = document.getElementById('nav-link-holdings');
    const navLinkTransactions = document.getElementById('nav-link-transactions');
    const cryptoListCard = document.getElementById('crypto-list-card');
    const holdingsCard = document.getElementById('holdings-card');
    const profileHistoryTabBtn = document.getElementById('profile-history-tab');

    // --- Nouveau : Boutons Détails Compte ---
    const viewTransactionsBtn = document.getElementById('viewTransactionsBtn');
    const accountSettingsBtn = document.getElementById('accountSettingsBtn');
    const profileUserTabBtn = document.getElementById('profile-user-tab');
    const viewStatsBtn = document.getElementById('viewStatsBtn');

    // --- Nouveau : Sélecteurs Mode Sombre ---
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const htmlElement = document.documentElement;

    let balanceVisible = true;
    let userBalanceRaw = 0;
    let selectedCryptoForTrade = null;
    let currentUserFullname = '';
    let currentUserEmail = '';
    let userHoldings = {};

    // --- Instances de Graphiques ---
    let portfolioChart = null;
    let cryptoListChartInstances = {};
    let modalChartInstance = null;
    let allocationChart = null; // Nouveau : Instance pour graphique d'allocation

    // --- Stocke le contexte pour la modale de confirmation ---
    let currentChartFetchController = null;
    let confirmationContext = {
        action: '',
        crypto: null,
        quantity: 0,
        amountCad: 0
    };

    // --- Logique Mode Sombre ---
    const THEME_KEY = 'themePreference';
    function applyTheme(theme, redrawCharts = false) {
        if (theme === 'dark') {
            htmlElement.setAttribute('data-bs-theme', 'dark');
            if (darkModeSwitch) darkModeSwitch.checked = true;
            if (darkModeIcon) darkModeIcon.className = 'fa-solid fa-moon';
        } else {
            htmlElement.removeAttribute('data-bs-theme');
            if (darkModeSwitch) darkModeSwitch.checked = false;
            if (darkModeIcon) darkModeIcon.className = 'fa-solid fa-sun';
        }
        if (redrawCharts) {
            fetchDashboardData();
        }
    }
    function toggleTheme() {
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(THEME_KEY, newTheme);
        applyTheme(newTheme, true);
    }
    const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
    applyTheme(savedTheme);
    if (darkModeSwitch) {
        darkModeSwitch.addEventListener('change', toggleTheme);
    }

    // --- Fonctions Utilitaires ---
    function formatCurrency(value, currency = 'CAD') {
        if (typeof value !== 'number' || isNaN(value)) return 'N/A';
        return new Intl.NumberFormat('fr-CA', { style: 'currency', currency: currency }).format(value);
    }
    function getChangeClass(value) {
        const numValue = parseFloat(value);
        if (numValue > 0) return 'text-success';
        if (numValue < 0) return 'text-danger';
        return 'text-muted';
    }

    // --- Refactoré : Logique MàJ Formulaire Trade ---
    function updateCorrespondingTradeInput(source, sourceInput, targetInput, priceCad, buyBtn, sellBtn, infoEl) {
        if (!sourceInput || !targetInput || !priceCad || priceCad <= 0) {
            if(buyBtn) buyBtn.disabled = true;
            if(sellBtn) sellBtn.disabled = true;
            return;
        }

        let amountCad = source === 'amount' ? parseFloat(sourceInput.value) : 0;
        let quantity = source === 'quantity' ? parseFloat(sourceInput.value) : 0;

        if (source === 'amount') {
            if (!isNaN(amountCad) && amountCad > 0) {
                quantity = amountCad / priceCad;
                targetInput.value = quantity.toFixed(8);
            } else if (sourceInput.value === '' || amountCad <= 0) {
                 targetInput.value = '';
                 amountCad = 0;
            }
        } else if (source === 'quantity') {
            if (!isNaN(quantity) && quantity > 0) {
                amountCad = quantity * priceCad;
                targetInput.value = amountCad.toFixed(2);
            } else if (sourceInput.value === '' || quantity <= 0) {
                 targetInput.value = '';
                 quantity = 0;
            }
        }

        const finalAmountCad = parseFloat(source === 'amount' ? sourceInput.value : targetInput.value);
        const finalQuantity = parseFloat(source === 'quantity' ? sourceInput.value : targetInput.value);
        const isValid = !isNaN(finalAmountCad) && finalAmountCad > 0 && !isNaN(finalQuantity) && finalQuantity > 0;

        if (buyBtn) buyBtn.disabled = !isValid;
        if (sellBtn) sellBtn.disabled = !isValid;

        if (infoEl) infoEl.classList.add('d-none');
    }

    // --- Refactoré : Déclencheur Modale Confirmation ---
    function showConfirmationModal(action, crypto, quantity, amountCad, sourceInfoEl = null) {
        const sourceInfoTextEl = sourceInfoEl ? sourceInfoEl.querySelector('span') : null;

        function displayValidationError(message) {
            console.warn(`Erreur Validation (${action}): ${message}`);
            if (sourceInfoEl && sourceInfoTextEl) {
                sourceInfoTextEl.textContent = message;
                sourceInfoEl.className = 'alert alert-danger mt-3';
            }
        }

        if (!confirmationModal || !crypto || !crypto.id || !crypto.name || !crypto.symbol || isNaN(crypto.priceCad)) {
            displayValidationError("Erreur interne: Données crypto invalides pour confirmation.");
            return;
        }
        if (isNaN(quantity) || quantity <= 0 || isNaN(amountCad) || amountCad <= 0) {
            displayValidationError("Veuillez entrer un montant ou une quantité valide.");
            return;
        }

        if (action === 'Acheter' && userBalanceRaw < amountCad) {
            displayValidationError(`Solde insuffisant. Vous avez ${formatCurrency(userBalanceRaw)}, besoin de ${formatCurrency(amountCad)}.`);
            return;
        }
        if (action === 'Vendre') {
            const ownedQuantity = userHoldings[crypto.id] || 0;
            if (ownedQuantity < quantity) {
                displayValidationError(`Solde ${crypto.symbol} insuffisant. Vous avez ${ownedQuantity.toFixed(8)}, besoin de ${quantity.toFixed(8)}.`);
                return;
            }
        }

        confirmationContext = { action, crypto, quantity, amountCad };

        if (confirmActionEl) confirmActionEl.textContent = action;
        if (confirmCryptoNameEl) confirmCryptoNameEl.textContent = crypto.name;
        if (confirmCryptoSymbolEl) confirmCryptoSymbolEl.textContent = crypto.symbol;
        if (confirmQuantityEl) confirmQuantityEl.textContent = quantity.toFixed(8);
        if (confirmPriceEl) confirmPriceEl.textContent = formatCurrency(crypto.priceCad);
        if (confirmAmountCadEl) confirmAmountCadEl.textContent = formatCurrency(amountCad);
        if (confirmErrorEl) confirmErrorEl.classList.add('d-none');

        if(confirmTransactionBtn) {
            confirmTransactionBtn.dataset.action = action;
            confirmTransactionBtn.dataset.currencyId = crypto.id;
            confirmTransactionBtn.dataset.quantity = quantity;
            confirmTransactionBtn.dataset.amountCad = amountCad;
        }

        confirmationModal.show();
    }

    // --- Exécution Transaction --- (utilise confirmationContext)
    async function executeTransaction() {
        if (!confirmTransactionBtn || !confirmErrorEl) return;

        const { action, crypto, quantity, amountCad } = confirmationContext;

        if (!action || !crypto || !crypto.id || isNaN(quantity) || quantity <= 0 || isNaN(amountCad) || amountCad <= 0) {
            console.error("Contexte de confirmation invalide:", confirmationContext);
            confirmErrorEl.textContent = "Erreur interne: Données de confirmation invalides.";
            confirmErrorEl.classList.remove('d-none');
            return;
        }

        const endpoint = action === 'Acheter' ? '/transaction/buy' : '/transaction/sell';

        try {
            const data = await fetchWithCsrf(`${API_BASE_URL}${endpoint}`, {
                method: 'POST',
                body: { currencyId: crypto.id, quantity, amountCad }
            });

            if (data.success) {
                if(confirmationModal) confirmationModal.hide();
                const chartModalInstance = bootstrap.Modal.getInstance(cryptoChartModalEl);
                if (chartModalInstance) {
                    chartModalInstance.hide();
                }
                console.log('Transaction réussie:', data.message);
                fetchDashboardData();
                if (amountInput) amountInput.value = '';
                if (quantityInput) quantityInput.value = '';
                if (buyButton) buyButton.disabled = true;
                if (sellButton) sellButton.disabled = true;
                if (tradeInfoEl) tradeInfoEl.classList.add('d-none');
                selectedCryptoForTrade = null;
                if (nameElBuySell) nameElBuySell.textContent = 'Sélectionnez une cryptomonnaie...';
                if (priceElBuySell) priceElBuySell.textContent = `Prix actuel: --`;
                const activeRow = cryptoListTableBody.querySelector('tr.table-active');
                if(activeRow) activeRow.classList.remove('table-active');
            } else {
                console.error(`Erreur lors de l'exécution (${action}):`, data.message);
                confirmErrorEl.textContent = `Erreur: ${data.message}`;
                confirmErrorEl.classList.remove('d-none');
            }
        } catch (error) {
            console.error(`Erreur lors de l'exécution (${action}):`, error);
            confirmErrorEl.textContent = `Erreur: ${error.message}`;
            confirmErrorEl.classList.remove('d-none');
        } finally {
            confirmTransactionBtn.disabled = false;
            confirmTransactionBtn.innerHTML = 'Confirmer';
        }
    }

    // --- Fonctions de Rendu ---
    function renderHoldings(holdings) {
        if (!holdingsTableBody) return;
        userHoldings = {};
        holdingsTableBody.innerHTML = '';
        if (!holdings || holdings.length === 0) {
            holdingsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Vous ne détenez aucun actif.</td></tr>';
            return;
        }
        holdings.forEach(holding => {
            const changePercent = parseFloat(holding.currency_change_24h_percent);
            const changeValueFormatted = holding.currency_change_24h_value_cad_formatted;
            const changeClass = getChangeClass(changePercent);
            userHoldings[holding.currency_id] = parseFloat(holding.quantity_raw);
            const row = `
                <tr data-crypto-id="${holding.currency_id}">
                    <td><img src="${holding.image_url || 'https://via.placeholder.com/20'}" alt="${holding.symbol || ''}" width="20" height="20" class="me-2 align-middle"> <span class="align-middle">${holding.name || 'N/A'} (${holding.symbol || 'N/A'})</span></td>
                    <td class="holding-quantity">${holding.quantity || '0'}</td>
                    <td class="holding-price">${holding.current_price_cad_formatted || 'N/A'}</td>
                    <td class="holding-value">${holding.total_value_cad_formatted || 'N/A'}</td>
                    <td class="holding-change ${changeClass}">${!isNaN(changePercent) ? changePercent.toFixed(2) + '%' : 'N/A'} <small class="d-block">(${changeValueFormatted || 'N/A'})</small></td>
                </tr>`;
            holdingsTableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    function renderPortfolioChart(chartData) {
        const ctx = document.getElementById('cryptoChart')?.getContext('2d');
        if (!ctx || !chartData || !chartData.labels || !chartData.values) return;
        if (portfolioChart) portfolioChart.destroy();
        const isDarkMode = htmlElement.getAttribute('data-bs-theme') === 'dark';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        const labelColor = isDarkMode ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.7)';
        const lineColor = isDarkMode ? '#6ea8fe' : '#2d3a36';
        const lineBackgroundColor = isDarkMode ? 'rgba(110, 168, 254, 0.2)' : 'rgba(45, 58, 54, 0.1)';
        portfolioChart = new Chart(ctx, {
            type: 'line',
            data: { labels: chartData.labels, datasets: [{ label: 'Valeur Totale du Portefeuille (CAD)', data: chartData.values, borderColor: lineColor, backgroundColor: lineBackgroundColor, tension: 0.3, fill: true, pointRadius: 0, pointHoverRadius: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { display: false, grid: { color: gridColor } }, y: { beginAtZero: false, ticks: { callback: value => formatCurrency(value), color: labelColor }, grid: { color: gridColor } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => context.parsed.y !== null ? formatCurrency(context.parsed.y) : '' } } } }
        });
    }

    function renderCryptoList(cryptos) {
         if (!cryptoListTableBody) return;
         cryptoListTableBody.innerHTML = '';
         if (!cryptos || cryptos.length === 0) {
             cryptoListTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Aucune cryptomonnaie disponible.</td></tr>';
             return;
         }
         Object.values(cryptoListChartInstances).forEach(chart => chart.destroy());
         cryptoListChartInstances = {};
         cryptos.forEach((crypto) => {
             const changeClass = getChangeClass(crypto.change_24h_raw);
             const chartId = `miniChart-${crypto.symbol}`;
             const row = `
                 <tr style="cursor: pointer;" class="crypto-list-row" data-crypto-id="${crypto.id}" data-crypto-name="${crypto.name}" data-crypto-price-cad="${crypto.price_cad_raw}" data-crypto-symbol="${crypto.symbol}" data-crypto-change-percent="${crypto.change_24h_raw}" data-crypto-market-cap="${crypto.market_cap}">
                     <td class="align-middle"><img src="${crypto.image_url || 'https://via.placeholder.com/20'}" alt="${crypto.symbol || ''}" width="20" height="20"></td>
                     <td class="align-middle">${crypto.rank || '#'}</td>
                     <td class="align-middle">${crypto.name || 'N/A'} <small class="text-muted">(${crypto.symbol || 'N/A'})</small></td>
                     <td class="align-middle">${formatCurrency(crypto.price_cad_raw)}</td>
                     <td class="align-middle ${changeClass}">${crypto.change_24h || 'N/A'}</td>
                     <td class="align-middle">${crypto.market_cap || 'N/A'}</td>
                     <td class="align-middle" style="width: 100px; height: 40px;"><canvas id="${chartId}" style="max-height: 40px;"></canvas></td>
                     <td class="align-middle text-center">
                         <button class="btn btn-sm btn-outline-secondary view-chart-btn"
                                 data-bs-toggle="modal"
                                 data-bs-target="#cryptoChartModal"
                                 data-crypto-id="${crypto.id}"
                                 data-crypto-name="${crypto.name}"
                                 data-crypto-price-cad="${crypto.price_cad_raw}"
                                 data-crypto-symbol="${crypto.symbol}"
                                 data-crypto-change-percent="${crypto.change_24h_raw}"
                                 data-crypto-market-cap="${crypto.market_cap}"
                                 title="Voir graphique détaillé">
                             <i class="fas fa-chart-line"></i>
                         </button>
                         <button class="btn btn-sm btn-outline-success buy-btn-quick ms-1" title="Acheter ${crypto.symbol}"
                                 data-crypto-id="${crypto.id}"
                                 data-crypto-name="${crypto.name}"
                                 data-crypto-price-cad="${crypto.price_cad_raw}"
                                 data-crypto-symbol="${crypto.symbol}">
                             <i class="fas fa-shopping-cart"></i>
                         </button>
                         <button class="btn btn-sm btn-outline-danger sell-btn-quick ms-1" title="Vendre ${crypto.symbol}"
                                 data-crypto-id="${crypto.id}"
                                 data-crypto-name="${crypto.name}"
                                 data-crypto-price-cad="${crypto.price_cad_raw}"
                                 data-crypto-symbol="${crypto.symbol}">
                             <i class="fas fa-dollar-sign"></i>
                         </button>
                     </td>
                 </tr>`;
             cryptoListTableBody.insertAdjacentHTML('beforeend', row);
             fetchMiniChartData(crypto.id, chartId, changeClass);
         });
         addCryptoListRowListeners();
     }

    async function fetchMiniChartData(currencyId, canvasId, changeClass) {
         if (!currencyId || !canvasId) return;
         try {
             const isDarkMode = htmlElement.getAttribute('data-bs-theme') === 'dark';
             const response = await fetch(`${API_BASE_URL}/crypto/chart/${currencyId}`);
             if (!response.ok) throw new Error(`Erreur HTTP mini chart! Statut: ${response.status}`);
             const result = await response.json();
             if (result.success && result.data?.datasets?.[0]?.data) {
                 const ctx = document.getElementById(canvasId)?.getContext('2d');
                 if (!ctx) return;
                 const chartColor = changeClass === 'text-success' ? 'rgba(25, 135, 84, 0.7)' : (changeClass === 'text-danger' ? 'rgba(220, 53, 69, 0.7)' : 'rgba(108, 117, 125, 0.7)');
                 const finalChartColor = chartColor;
                 cryptoListChartInstances[canvasId] = new Chart(ctx, {
                     type: 'line', data: { labels: result.data.labels, datasets: [{ data: result.data.datasets[0].data, borderColor: finalChartColor, borderWidth: 1.5, pointRadius: 0, tension: 0.4 }] },
                     options: { responsive: true, maintainAspectRatio: false, scales: { x: { display: false }, y: { display: false } }, plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: false }
                 });
             }
         } catch (error) { console.error(`Erreur fetch mini chart pour ${canvasId}:`, error); }
     }

    function addCryptoListRowListeners() {
         const rows = cryptoListTableBody.querySelectorAll('.crypto-list-row');
         if (sellAllButton) sellAllButton.disabled = true;

         rows.forEach(row => {
             row.addEventListener('click', (event) => {
                 if (event.target.closest('.view-chart-btn, .buy-btn-quick, .sell-btn-quick')) {
                     return;
                 }

                 cryptoListTableBody.querySelector('tr.table-active')?.classList.remove('table-active');
                 row.classList.add('table-active');
                 selectedCryptoForTrade = {
                     id: row.dataset.cryptoId,
                     name: row.dataset.cryptoName,
                     priceCad: parseFloat(row.dataset.cryptoPriceCad),
                     symbol: row.dataset.cryptoSymbol
                 };

                 if (nameElBuySell) nameElBuySell.textContent = `${selectedCryptoForTrade.name} (${selectedCryptoForTrade.symbol})`;
                 if (priceElBuySell) priceElBuySell.textContent = `Prix actuel: ${formatCurrency(selectedCryptoForTrade.priceCad)}`;
                 if (amountInput) amountInput.value = '';
                 if (quantityInput) quantityInput.value = '';
                 updateCorrespondingTradeInput('amount', amountInput, quantityInput, selectedCryptoForTrade.priceCad, buyButton, sellButton, tradeInfoEl);
                 if (tradeInfoEl) tradeInfoEl.classList.add('d-none');

                 if (sellAllButton) {
                     const ownedQuantity = userHoldings[selectedCryptoForTrade.id];
                     sellAllButton.disabled = !(ownedQuantity && ownedQuantity > 0);
                 }
             });

            const quickBuyBtn = row.querySelector('.buy-btn-quick');
            const quickSellBtn = row.querySelector('.sell-btn-quick');
            const cryptoData = {
                 id: row.dataset.cryptoId,
                 name: row.dataset.cryptoName,
                 priceCad: parseFloat(row.dataset.cryptoPriceCad),
                 symbol: row.dataset.cryptoSymbol
             };

            if(quickBuyBtn) {
                quickBuyBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const chartButton = row.querySelector('.view-chart-btn');
                    if(chartButton) chartButton.click();
                });
            }
             if(quickSellBtn) {
                 quickSellBtn.addEventListener('click', (e) => {
                     e.stopPropagation();
                     const chartButton = row.querySelector('.view-chart-btn');
                     if(chartButton) chartButton.click();
                 });
             }

         });
     }

    async function displayChartInModal(modalData) {
        console.log(`>>> Entrée displayChartInModal pour ID: ${modalData?.id}`);

        // Je vérifie que tous les éléments requis de la modale existent
        if (!cryptoChartModalEl || !modalChartLoadingEl || !modalChartContainerEl || !modalChartErrorEl || !modalChartTitleEl || !modalChartCanvas || !modalInfoPriceEl || !modalInfoChangeEl || !modalInfoMarketCapEl || !modalCryptoAmountInput || !modalCryptoQuantityInput || !modalBuyButton || !modalSellButton || !modalSellAllButton || !modalTradeInfoEl)
        {
             console.error("Un ou plusieurs éléments de la modale étendue sont manquants.");
             return;
        }

        // --- Je force le nettoyage & la réinitialisation de l'état D'ABORD ---
        if (modalChartInstance) {
            console.log("   -> Destruction de l'instance de graphique modale précédente.");
            modalChartInstance.destroy();
            modalChartInstance = null;
        }
        // J'annule toute requête fetch en attente pour le graphique *précédent*
        if (currentChartFetchController) {
            console.log("   -> Annulation de la requête fetch du graphique précédent.");
            currentChartFetchController.abort();
        }

        console.log("   -> Réinitialisation des éléments DOM de la modale (affiche chargement, masque graphique/erreur).");
        modalChartLoadingEl.classList.remove('d-none');
        modalChartContainerEl.classList.add('d-none');
        modalChartCanvas.classList.add('d-none');
        modalChartErrorEl.classList.add('d-none');
        // --- Fin Nettoyage & Réinitialisation ---

        const { id: currencyId, name: cryptoName, symbol: cryptoSymbol, priceCad, changePercent, marketCap } = modalData;

        // Je prépare l'état de la modale
        modalChartTitleEl.textContent = `Graphique : ${cryptoName || ''} (${cryptoSymbol || ''})`;

        // --- Je remplis la section Infos --- (Avant de fetch le graphique)
        modalInfoPriceEl.textContent = formatCurrency(priceCad);
        const changeClass = getChangeClass(changePercent);
        modalInfoChangeEl.innerHTML = `<span class="${changeClass}">${changePercent.toFixed(2)}%</span>`;
        modalInfoMarketCapEl.textContent = marketCap;

        modalCryptoAmountInput.value = '';
        modalCryptoQuantityInput.value = '';
        modalBuyButton.disabled = true;
        modalSellButton.disabled = true;
        modalTradeInfoEl.classList.add('d-none');
        const ownedQuantity = userHoldings[currencyId] || 0;
        modalSellAllButton.disabled = !(ownedQuantity && ownedQuantity > 0);

        // --- J'installe les Listeners (en clonant pour éviter les doublons) --- 
        const setupModalListeners = () => {
            console.log("   -> setupModalListeners: Re-sélection des éléments de trade de la modale...");
            const amountInputToReplace = document.getElementById('modalCryptoAmount');
            const quantityInputToReplace = document.getElementById('modalCryptoQuantity');
            const buyButtonToReplace = document.getElementById('modalBuyButton');
            const sellButtonToReplace = document.getElementById('modalSellButton');
            const sellAllButtonToReplace = document.getElementById('modalSellAllButton');
            const infoElementForListeners = document.getElementById('modalTradeInfo');

            // Je vérifie si les éléments ont été trouvés *maintenant*
            if (!amountInputToReplace || !quantityInputToReplace || !buyButtonToReplace || !sellButtonToReplace || !sellAllButtonToReplace || !infoElementForListeners) {
                console.error("   -> ERREUR dans setupModalListeners: Un ou plusieurs éléments de trade de la modale non trouvés avant le clonage !");
                return; // J'arrête si des éléments manquent
            }
            console.log("   -> setupModalListeners: Tous les éléments trouvés.");

            // Je clone les éléments pour enlever les anciens listeners
            const freshAmountInput = amountInputToReplace.cloneNode(true);
            const freshQuantityInput = quantityInputToReplace.cloneNode(true);
            const freshBuyButton = buyButtonToReplace.cloneNode(true);
            const freshSellButton = sellButtonToReplace.cloneNode(true);
            const freshSellAllButton = sellAllButtonToReplace.cloneNode(true);

            console.log("   -> setupModalListeners: Remplacement des éléments par les clones...");
            amountInputToReplace.parentNode.replaceChild(freshAmountInput, amountInputToReplace);
            quantityInputToReplace.parentNode.replaceChild(freshQuantityInput, quantityInputToReplace);
            buyButtonToReplace.parentNode.replaceChild(freshBuyButton, buyButtonToReplace);
            sellButtonToReplace.parentNode.replaceChild(freshSellButton, sellButtonToReplace);
            sellAllButtonToReplace.parentNode.replaceChild(freshSellAllButton, sellAllButtonToReplace);

            console.log("   -> setupModalListeners: Attachement des listeners aux éléments clonés...");

            // J'attache les listeners aux nouveaux éléments
            freshAmountInput.addEventListener('input', () => {
                updateCorrespondingTradeInput('amount', freshAmountInput, freshQuantityInput, modalData.priceCad, freshBuyButton, freshSellButton, infoElementForListeners);
            });
            freshQuantityInput.addEventListener('input', () => {
                updateCorrespondingTradeInput('quantity', freshQuantityInput, freshAmountInput, modalData.priceCad, freshBuyButton, freshSellButton, infoElementForListeners);
            });
            freshBuyButton.addEventListener('click', () => {
                const quantity = parseFloat(freshQuantityInput.value);
                const amountCad = parseFloat(freshAmountInput.value);
                // Je masque la modale du graphique actuelle D'ABORD
                const chartModalInstance = bootstrap.Modal.getInstance(cryptoChartModalEl);
                if (chartModalInstance) {
                    chartModalInstance.hide();
                }
                // ENSUITE j'affiche la modale de confirmation
                showConfirmationModal('Acheter', modalData, quantity, amountCad, infoElementForListeners);
            });
            freshSellButton.addEventListener('click', () => {
                const quantity = parseFloat(freshQuantityInput.value);
                const amountCad = parseFloat(freshAmountInput.value);
                // Je masque la modale du graphique actuelle D'ABORD
                const chartModalInstance = bootstrap.Modal.getInstance(cryptoChartModalEl);
                if (chartModalInstance) {
                    chartModalInstance.hide();
                }
                // ENSUITE j'affiche la modale de confirmation
                showConfirmationModal('Vendre', modalData, quantity, amountCad, infoElementForListeners);
            });
            freshSellAllButton.addEventListener('click', () => {
                const owned = userHoldings[currencyId] || 0;
                if (owned > 0) {
                    freshQuantityInput.value = owned.toFixed(8);
                    updateCorrespondingTradeInput('quantity', freshQuantityInput, freshAmountInput, modalData.priceCad, freshBuyButton, freshSellButton, infoElementForListeners);
                }
            });
        };

        setupModalListeners();

        // --- Je crée l'AbortController pour le nouveau fetch --- 
        currentChartFetchController = new AbortController();
        const signal = currentChartFetchController.signal;

        // --- Fetch et Rendu du Graphique --- 
        console.log(`   -> Lancement du fetch pour les données du graphique (ID: ${currencyId})...`);
        try {
            const isDarkMode = htmlElement.getAttribute('data-bs-theme') === 'dark';
            if (!currencyId) throw new Error('ID de devise manquant.');
            const response = await fetch(`${API_BASE_URL}/crypto/chart/${currencyId}`, { signal }); // Je passe le signal
            console.log(`   -> Réponse Fetch reçue (Statut: ${response.status})`);
            if (!response.ok) throw new Error(`Erreur API (${response.status})`);
            const result = await response.json();
            if (result.success && result.data?.datasets?.[0]?.data) {
                console.log("   -> Données API réussies. Préparation du rendu du graphique.");
                const chartData = result.data;
                const ctx = modalChartCanvas.getContext('2d');
                const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                const labelColor = isDarkMode ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
                const pointColor = isDarkMode ? '#6ea8fe' : '#0d6efd';
                const lineBackgroundColor = isDarkMode ? 'rgba(110, 168, 254, 0.2)' : 'rgba(13, 110, 253, 0.1)';
                const legendColor = isDarkMode ? '#fff' : '#666';
                modalChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: `Prix (${cryptoSymbol} - CAD)`,
                            data: chartData.datasets[0].data,
                            borderColor: pointColor,
                            backgroundColor: lineBackgroundColor,
                            tension: 0.3, fill: true, pointRadius: 2, pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: {
                            x: { ticks: { color: labelColor }, grid: { color: gridColor } },
                            y: { beginAtZero: false, ticks: { callback: value => formatCurrency(value), color: labelColor }, grid: { color: gridColor } }
                        },
                        plugins: { legend: { display: true, labels: { color: legendColor } }, tooltip: { callbacks: { label: context => context.parsed.y !== null ? formatCurrency(context.parsed.y) : '' } } }
                    }
                });
                modalChartLoadingEl.classList.add('d-none');
                console.log("   -> Graphique rendu. Affichage du canvas et du conteneur.");
                modalChartCanvas.classList.remove('d-none'); // J'affiche le canvas
                modalChartContainerEl.classList.remove('d-none'); // J'affiche le conteneur
                if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = false;
            } else {
                console.log("   -> Format des données API invalide ou success=false.");
                throw new Error(result.message || 'Format de données invalide');
            }
        } catch (error) {
             if (error.name === 'AbortError') {
                 console.log("   -> Fetch du graphique annulé (probablement à cause d'une nouvelle ouverture de modale). Aucune erreur affichée.");
             } else {
                 console.error(`>>> ERREUR fetch/render graphique (ID: ${currencyId}):`, error);
                 modalChartLoadingEl.classList.add('d-none');
                 modalChartErrorEl.textContent = `Erreur chargement graphique: ${error.message}`;
                 modalChartErrorEl.classList.remove('d-none');
                 modalChartCanvas.classList.add('d-none'); // Je garde le canvas masqué en cas d'erreur
                 if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
             }
        }
    }

    // --- Nouveau : Fonctions Onglet Statistiques ---
    async function fetchAndRenderStats() {
        console.log(">>> fetchAndRenderStats déclenché");
        if (!allocationChartCanvas || !statsAllocationLoadingEl || !statsAllocationErrorEl || !statsTotalCryptoValueEl || !statsFiatBalanceEl || !statsAllocationEmptyEl || !statsUnrealizedPLEl || !downloadAllocationChartBtn) {
            console.error("Éléments de l'onglet statistiques non trouvés.");
            return;
        }

        // Je réinitialise l'état
        statsAllocationLoadingEl.classList.remove('d-none');
        statsAllocationErrorEl.classList.add('d-none');
        if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
        statsAllocationEmptyEl.classList.add('d-none');
        allocationChartCanvas.style.display = 'none'; // Je masque le canvas initialement
        if (allocationChart) {
            allocationChart.destroy();
            allocationChart = null;
        }
        if (statsTotalCryptoValueEl) statsTotalCryptoValueEl.textContent = 'Chargement...';
        if (statsFiatBalanceEl) statsFiatBalanceEl.textContent = 'Chargement...';
        if (statsUnrealizedPLEl) statsUnrealizedPLEl.textContent = 'Chargement...';

        try {
            const response = await fetch(`${API_BASE_URL}/stats/allocation`);
            if (!response.ok) {
                throw new Error(`Erreur API: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                const statsData = result.data;

                // Je mets à jour les cartes résumé
                if (statsTotalCryptoValueEl) statsTotalCryptoValueEl.textContent = formatCurrency(statsData.total_crypto_value_cad);
                if (statsFiatBalanceEl) statsFiatBalanceEl.textContent = formatCurrency(statsData.user_balance_cad);
                if (statsUnrealizedPLEl) {
                    const plValue = statsData.total_unrealized_pl_cad;
                    statsUnrealizedPLEl.textContent = formatCurrency(plValue);
                    statsUnrealizedPLEl.classList.remove('text-success', 'text-danger', 'text-muted');
                    if (plValue > 0) {
                        statsUnrealizedPLEl.classList.add('text-success');
                    } else if (plValue < 0) {
                        statsUnrealizedPLEl.classList.add('text-danger');
                    } else {
                        statsUnrealizedPLEl.classList.add('text-muted');
                    }
                }

                // Je prépare les données du graphique
                const allocation = statsData.allocation || [];
                if (allocation.length === 0) {
                    statsAllocationEmptyEl.classList.remove('d-none');
                    statsAllocationLoadingEl.classList.add('d-none');
                    if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
                    return; // Pas de données à afficher
                }

                const labels = allocation.map(item => item.symbol);
                const dataValues = allocation.map(item => item.percentage);

                const chartConfigData = {
                    labels: labels,
                    datasets: [{
                        label: 'Répartition (%)',
                        data: dataValues,
                        // J'ajoute de jolies couleurs de fond (peut être généré dynamiquement plus tard)
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(99, 255, 132, 0.7)', // Plus de couleurs si besoin
                            'rgba(235, 54, 162, 0.7)',
                            'rgba(86, 255, 206, 0.7)',
                            'rgba(192, 75, 192, 0.7)'
                        ],
                        borderColor: htmlElement.getAttribute('data-bs-theme') === 'dark' ? '#333' : '#fff', // Couleur bordure selon thème
                        borderWidth: 1
                    }]
                };

                const ctx = allocationChartCanvas.getContext('2d');
                allocationChart = new Chart(ctx, {
                    type: 'doughnut', // Ou 'pie'
                    data: chartConfigData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                     color: htmlElement.getAttribute('data-bs-theme') === 'dark' ? '#fff' : '#666' // Couleur légende selon thème
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                            // Je trouve l'élément d'allocation original pour afficher nom et valeur CAD
                                            const symbol = context.label;
                                            const item = allocation.find(a => a.symbol === symbol);
                                            label += `${context.parsed.toFixed(2)}%`;
                                            if (item) {
                                                label += ` (${item.name}: ${formatCurrency(item.value_cad)}, P/L: ${formatCurrency(item.unrealized_pl_cad)})`;
                                            }
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });

                allocationChartCanvas.style.display = 'block'; // J'affiche le canvas
                if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = false;

            } else {
                throw new Error(result.message || 'Échec traitement données stats');
            }

        } catch (error) {
            console.error("Erreur fetch/render stats:", error);
            if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
            statsAllocationErrorEl.classList.remove('d-none');
        } finally {
            statsAllocationLoadingEl.classList.add('d-none');
        }
    }

    // --- Fin Nouveau Fonctions Onglet Statistiques ---

    // --- Fonction Fetch Principale ---
    async function fetchDashboardData() {
        if(holdingsTableBody) holdingsTableBody.innerHTML = '<tr class="loading-placeholder"><td colspan="5">Chargement des actifs... <i class="fas fa-spinner fa-spin"></i></td></tr>';
        if(cryptoListTableBody) cryptoListTableBody.innerHTML = '<tr class="loading-placeholder"><td colspan="8">Chargement des cryptomonnaies... <i class="fas fa-spinner fa-spin"></i></td></tr>';
        if(accountTypeEl) accountTypeEl.textContent = 'Chargement...';
        if(accountStatusEl) { accountStatusEl.textContent = 'Chargement...'; accountStatusEl.className = 'badge account-status'; }
        if(accountLastLoginEl) accountLastLoginEl.textContent = 'Chargement...';
        if(balanceAmountEl) balanceAmountEl.textContent = 'Chargement...';
        if(investmentAmountEl) investmentAmountEl.textContent = 'Chargement...';
        if (weeklyGainEl) weeklyGainEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Chargement...';

        try {
            const [dashResponse, listResponse] = await Promise.all([
                fetch(`${API_BASE_URL}/dashboard/data`),
                fetch(`${API_BASE_URL}/crypto/list`)
            ]);

            if (dashResponse.ok) {
                const resultDash = await dashResponse.json();
                if (resultDash.success && resultDash.data) {
                    const data = resultDash.data;
                    if (accountTypeEl) accountTypeEl.textContent = data.account.type;
                    if (accountStatusEl) { accountStatusEl.textContent = data.account.status; accountStatusEl.className = `badge account-status ${data.account.status === 'Active' ? 'bg-success' : 'bg-warning text-dark'}`; }
                    if (accountLastLoginEl) accountLastLoginEl.textContent = data.account.last_login;
                    userBalanceRaw = data.account.balance_cad_raw;
                    if (balanceAmountEl) balanceAmountEl.textContent = data.account.balance_cad_formatted;
                    if (hiddenBalanceEl) hiddenBalanceEl.textContent = '******$ CAD';
                    if (investmentAmountEl) investmentAmountEl.textContent = data.portfolio.crypto_value_cad_formatted || '0.00$ CAD';
                    if (hiddenInvestmentEl) hiddenInvestmentEl.textContent = '******$ CAD';
                    currentUserFullname = data.account.fullname || '';
                    currentUserEmail = data.account.email || '';
                    const navbarDropdownButton = document.getElementById('navbarDropdown');
                    if (navbarDropdownButton && currentUserFullname) {
                        navbarDropdownButton.textContent = `Compte (${currentUserFullname})`;
                        let icon = document.createElement('i'); icon.className = 'fa-solid fa-user ms-2'; navbarDropdownButton.appendChild(icon);
                    }
                    const adminTabLink = document.getElementById('profile-admin-tab');
                    if (adminCurrencySection && adminTabLink) {
                        if(data.account.is_admin === true) {
                            adminCurrencySection.classList.remove('d-none'); adminTabLink.classList.remove('d-none'); loadAdminCurrenciesDropdown();
                        } else {
                            adminCurrencySection.classList.add('d-none'); adminTabLink.classList.add('d-none');
                        }
                    }
                    const portfolioChangeClass = getChangeClass(parseFloat(data.portfolio?.change_24h_percent_formatted));
                    if (weeklyGainEl) { weeklyGainEl.innerHTML = `Gain Crypto (24h): <span class="${portfolioChangeClass}">${data.portfolio.change_24h_cad_formatted} (${data.portfolio.change_24h_percent_formatted})</span>`; }
                    if (holdingsTableBody) renderHoldings(data.holdings);
                    if (data.portfolioChart) renderPortfolioChart(data.portfolioChart);
                } else {
                    console.error('Échec traitement données API Dashboard:', resultDash.message || 'Erreur inconnue');
                }
            } else {
                 console.error(`Erreur API Dashboard! statut: ${dashResponse.status}`);
            }

            if (listResponse.ok) {
                const resultList = await listResponse.json();
                if (resultList.success && resultList.data) {
                    if (cryptoListTableBody) renderCryptoList(resultList.data);
                } else {
                    console.error('Échec traitement données API Liste Crypto:', resultList.message || 'Erreur inconnue');
                    if (cryptoListTableBody) cryptoListTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur chargement cryptos.</td></tr>';
                }
            } else {
                console.error(`Erreur API Liste Crypto! statut: ${listResponse.status}`);
                if (cryptoListTableBody) cryptoListTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur chargement cryptos.</td></tr>';
            }

        } catch (error) {
            console.error('Erreur fetch données dashboard:', error);
        }
    }

    // --- Écouteurs d'Événements ---
    if (toggleBalanceBtn && balanceAmountEl && hiddenBalanceEl) {
        toggleBalanceBtn.addEventListener('click', () => {
            balanceVisible = !balanceVisible;
            balanceAmountEl.classList.toggle('d-none', !balanceVisible);
            hiddenBalanceEl.classList.toggle('d-none', balanceVisible);
            if (investmentAmountEl) investmentAmountEl.classList.toggle('d-none', !balanceVisible);
            if (hiddenInvestmentEl) hiddenInvestmentEl.classList.toggle('d-none', balanceVisible);
            toggleBalanceBtn.querySelector('i').className = balanceVisible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        });
    }

    if (cryptoChartModalEl) {
        cryptoChartModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (button && button.dataset.cryptoId) {
                const modalData = {
                    id: button.dataset.cryptoId,
                    name: button.dataset.cryptoName || 'N/A',
                    symbol: button.dataset.cryptoSymbol || 'N/A',
                    priceCad: parseFloat(button.dataset.cryptoPriceCad) || 0,
                    changePercent: parseFloat(button.dataset.cryptoChangePercent) || 0,
                    marketCap: button.dataset.cryptoMarketCap || 'N/A'
                };
                displayChartInModal(modalData);
            } else {
                console.warn("Modale ouverte sans bouton déclencheur valide de données crypto.");
            }
        });

        cryptoChartModalEl.addEventListener('hidden.bs.modal', function () {
            // PAS de destruction de graphique ici. Géré au début de displayChartInModal.
        });
    }

    if (amountInput) {
        amountInput.addEventListener('input', () => {
            updateCorrespondingTradeInput('amount', amountInput, quantityInput, selectedCryptoForTrade?.priceCad, buyButton, sellButton, tradeInfoEl);
        });
    }
    if (quantityInput) {
        quantityInput.addEventListener('input', () => {
            updateCorrespondingTradeInput('quantity', quantityInput, amountInput, selectedCryptoForTrade?.priceCad, buyButton, sellButton, tradeInfoEl);
        });
    }

    [buyButton, sellButton].forEach(button => {
        if(button) {
            button.addEventListener('click', function() {
                if (!selectedCryptoForTrade) {
                    if(tradeInfoEl && tradeInfoTextEl) {
                        tradeInfoTextEl.textContent = "Veuillez d'abord sélectionner une cryptomonnaie dans la liste.";
                        tradeInfoEl.className = 'alert alert-warning mt-3';
                    }
                    return;
                }
                const action = button.id === 'buyButton' ? 'Acheter' : 'Vendre';
                const quantity = parseFloat(quantityInput.value);
                const amountCad = parseFloat(amountInput.value);
                showConfirmationModal(action, selectedCryptoForTrade, quantity, amountCad, tradeInfoEl);
            });
        }
    });

    if (sellAllButton && quantityInput && amountInput) {
        sellAllButton.addEventListener('click', function() {
            if (!selectedCryptoForTrade) {
                console.warn('Vendre Tout (Principal): Aucune crypto sélectionnée.'); return;
            }
            const ownedQuantity = userHoldings[selectedCryptoForTrade.id] || 0;
            if (ownedQuantity > 0) {
                quantityInput.value = ownedQuantity.toFixed(8);
                updateCorrespondingTradeInput('quantity', quantityInput, amountInput, selectedCryptoForTrade.priceCad, buyButton, sellButton, tradeInfoEl);
            } else {
                quantityInput.value = '';
                amountInput.value = '';
                updateCorrespondingTradeInput('quantity', quantityInput, amountInput, selectedCryptoForTrade.priceCad, buyButton, sellButton, tradeInfoEl);
            }
        });
    }

    if (confirmTransactionBtn) {
        confirmTransactionBtn.addEventListener('click', executeTransaction);
    }

    if (confirmationModalEl) {
        confirmationModalEl.addEventListener('hidden.bs.modal', function () {
            if(confirmErrorEl) confirmErrorEl.classList.add('d-none');
            if(confirmTransactionBtn) {
                 confirmTransactionBtn.disabled = false;
                 confirmTransactionBtn.innerHTML = 'Confirmer';
            }
            confirmationContext = { action: '', crypto: null, quantity: 0, amountCad: 0 };
        });
    }

    if (adminCurrencySelect) { adminCurrencySelect.addEventListener('change', function() {
        const selectedId = this.value;
        if (selectedId === 'new') {
            clearAdminCurrencyForm();
        } else {
            loadCurrencyDetailsForAdminForm(selectedId);
        }
    }); }
    if (adminAddBtn) { adminAddBtn.addEventListener('click', async function() {
        if (!adminCurrencyForm) return;
        if (!adminCurrencyForm.checkValidity()) {
            displayAdminCurrencyMessage('Veuillez remplir tous les champs requis.', true);
            adminCurrencyForm.reportValidity();
            return;
        }

        const currencyData = {
            name: adminCurrencyNameInput.value,
            symbol: adminCurrencySymbolInput.value,
            current_price_cad: adminCurrencyPriceInput.value,
            change_24h_percent: adminCurrencyChangeInput.value,
            market_cap_cad: adminCurrencyMarketCapInput.value,
            base_volatility: adminCurrencyVolatilityInput.value,
            base_trend: adminCurrencyTrendInput.value
        };

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Ajout...';

        try {
            const response = await fetchWithCsrf(`${API_BASE_URL}/admin/currency/add`, {
                method: 'POST',
                body: currencyData
            });

            displayAdminCurrencyMessage(response.message || 'Devise ajoutée avec succès!', !response.success);
            clearAdminCurrencyForm();
            await loadAdminCurrenciesDropdown();
            await fetchDashboardData();

        } catch (error) {
            console.error('Erreur ajout devise:', error);
            displayAdminCurrencyMessage(`Erreur ajout: ${error.message}`, true);
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-plus me-1"></i> Ajouter';
        }
    }); }
    if (adminUpdateBtn) { adminUpdateBtn.addEventListener('click', async function() {
        if (!adminCurrencyForm) return;
        const currencyId = adminCurrencyIdInput.value;
        if (!currencyId) {
            displayAdminCurrencyMessage('Aucune devise sélectionnée pour la mise à jour.', true);
            return;
        }
        if (!adminCurrencyForm.checkValidity()) {
             displayAdminCurrencyMessage('Veuillez remplir tous les champs requis.', true);
             adminCurrencyForm.reportValidity();
             return;
         }

         const currencyData = {
             name: adminCurrencyNameInput.value,
             symbol: adminCurrencySymbolInput.value,
             current_price_cad: adminCurrencyPriceInput.value,
             change_24h_percent: adminCurrencyChangeInput.value,
             market_cap_cad: adminCurrencyMarketCapInput.value,
             base_volatility: adminCurrencyVolatilityInput.value,
             base_trend: adminCurrencyTrendInput.value
         };

         this.disabled = true;
         this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mise à jour...';

        try {
             const response = await fetchWithCsrf(`${API_BASE_URL}/admin/currency/update/${currencyId}`, {
                 method: 'POST',
                 body: currencyData
             });

             displayAdminCurrencyMessage(response.message || 'Devise mise à jour avec succès!', !response.success);
             await loadAdminCurrenciesDropdown();
             await fetchDashboardData();

         } catch (error) {
             console.error('Erreur MàJ devise:', error);
             displayAdminCurrencyMessage(`Erreur MàJ: ${error.message}`, true);
         } finally {
             this.disabled = false;
             this.innerHTML = '<i class="fas fa-save me-1"></i> Mettre à jour';
         }
    }); }
    if (adminDeleteBtn) { adminDeleteBtn.addEventListener('click', async function() {
        const currencyId = adminCurrencyIdInput.value;
        const currencyName = adminCurrencyNameInput.value || 'cette devise';
        if (!currencyId) {
            displayAdminCurrencyMessage('Aucune devise sélectionnée pour la suppression.', true);
            return;
        }

        if (confirm(`Êtes-vous sûr de vouloir supprimer ${currencyName} (ID: ${currencyId}) ? Cette action est irréversible.`)) {

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Suppression...';

            try {
                const response = await fetchWithCsrf(`${API_BASE_URL}/admin/currency/delete/${currencyId}`, {
                    method: 'DELETE'
                });

                displayAdminCurrencyMessage(response.message || 'Devise supprimée avec succès!', !response.success);
                clearAdminCurrencyForm();
                await loadAdminCurrenciesDropdown();
                await fetchDashboardData();

            } catch (error) {
                console.error('Erreur suppression devise:', error);
                displayAdminCurrencyMessage(`Erreur suppression: ${error.message}`, true);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-trash me-1"></i> Supprimer';
            }
        }
    }); }

    if (profileSettingsModalEl) { profileSettingsModalEl.addEventListener('show.bs.modal', function () {
        if (profileFullnameInput) profileFullnameInput.value = currentUserFullname;
        if (profileEmailInput) profileEmailInput.value = currentUserEmail;

        if (profileCurrentPasswordInput) profileCurrentPasswordInput.value = '';
        if (profileNewPasswordInput) profileNewPasswordInput.value = '';
        if (profileConfirmPasswordInput) profileConfirmPasswordInput.value = '';
        if (userProfileMessageArea) userProfileMessageArea.classList.add('d-none');
        if (adminMessageArea) adminMessageArea.classList.add('d-none');

        fetchAndRenderTransactions();

        if (adminCurrencySelect && adminCurrencySelect.value !== 'new') {
             // Optionnel : réinitialise le formulaire admin ou laisse tel quel selon l'UX voulue
            // clearAdminCurrencyForm();
            // adminCurrencySelect.value = 'new';
        }
    }); }

    if (userProfileForm) { userProfileForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        if (!userProfileForm.checkValidity()) {
             displayUserProfileMessage('Veuillez remplir le nom et l\'email.', true);
             userProfileForm.reportValidity();
            return;
        }

        const newPassword = profileNewPasswordInput.value;
        const confirmPassword = profileConfirmPasswordInput.value;

        if (newPassword !== confirmPassword) {
            displayUserProfileMessage('Les nouveaux mots de passe ne correspondent pas.', true);
            return;
        }
        if (newPassword && !profileCurrentPasswordInput.value) {
             displayUserProfileMessage('Veuillez entrer votre mot de passe actuel pour définir un nouveau mot de passe.', true);
            return;
        }

        const formData = {
            fullname: profileFullnameInput.value,
            email: profileEmailInput.value,
            currentPassword: profileCurrentPasswordInput.value,
            newPassword: newPassword,
            confirmPassword: confirmPassword
        };

        if (saveProfileButton) {
            saveProfileButton.disabled = true;
            saveProfileButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sauvegarde...';
        }

        try {
            const response = await fetchWithCsrf(`${API_BASE_URL}/user/update`, {
                method: 'POST',
                body: formData
            });

            displayUserProfileMessage(response.message || 'Profil mis à jour avec succès!', !response.success);

             currentUserFullname = response.data?.fullname;
             currentUserEmail = response.data?.email;

             if (navbarDropdownButton = document.getElementById('navbarDropdown')) {
                navbarDropdownButton.textContent = `Compte (${currentUserFullname})`;
                let icon = document.createElement('i'); icon.className = 'fa-solid fa-user ms-2'; navbarDropdownButton.appendChild(icon);
             }

        } catch (error) {
            console.error('Erreur MàJ profil:', error);
            displayUserProfileMessage(`Erreur mise à jour profil: ${error.message}`, true);
        } finally {
            if (saveProfileButton) {
                saveProfileButton.disabled = false;
                saveProfileButton.innerHTML = '<i class="fas fa-save me-1"></i> Sauvegarder Profil';
            }
        }
    }); }

    function renderTransactionsTable(transactions) {
        if (!transactionHistoryTableBody) return;

        transactionHistoryTableBody.innerHTML = '';

        if (!transactions || transactions.length === 0) {
            transactionHistoryTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Aucune transaction trouvée.</td></tr>';
             if (downloadCsvBtn) downloadCsvBtn.disabled = true;
             if (downloadPdfBtn) downloadPdfBtn.disabled = true;
            return;
        }

        transactions.forEach(tx => {
            const row = `
                <tr>
                    <td>${tx.timestamp || 'N/A'}</td>
                    <td class="${tx.type_class || ''}">${tx.type || 'N/A'}</td>
                    <td>${tx.currency_name || 'N/A'} (${tx.currency_symbol || 'N/A'})</td>
                    <td>${tx.quantity || 'N/A'}</td>
                    <td>${formatCurrency(tx.price_per_unit_cad) || 'N/A'}</td>
                    <td class="${tx.type_class || ''} fw-bold">${tx.total_amount_cad_display || 'N/A'}</td>
                </tr>`;
            transactionHistoryTableBody.insertAdjacentHTML('beforeend', row);
        });

        if (downloadCsvBtn) downloadCsvBtn.disabled = false;
        if (downloadPdfBtn) downloadPdfBtn.disabled = false;
    }

    async function fetchAndRenderTransactions() {
        const loadingRow = '<tr class="loading-placeholder"><td colspan="6" class="text-center py-4">Chargement de l\&#39;historique... <i class="fas fa-spinner fa-spin"></i></td></tr>';
        transactionHistoryTableBody.innerHTML = loadingRow;
        downloadCsvBtn.disabled = true;
        downloadPdfBtn.disabled = true;

        try {
            const response = await fetch(`${API_BASE_URL}/user/transactions`);
            if (!response.ok) {
                throw new Error(`Erreur API: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                renderTransactionsTable(result.data);
                if(result.data.length > 0) {
                    downloadCsvBtn.disabled = false;
                    downloadPdfBtn.disabled = false;
                }
            } else {
                throw new Error(result.message || 'Échec chargement transactions');
            }
        } catch (error) {
            console.error('Erreur fetch transactions:', error);
            transactionHistoryTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Erreur: ${error.message}</td></tr>`;
        }
    }

    if (downloadCsvBtn) {
        downloadCsvBtn.addEventListener('click', () => {
            window.location.href = `${API_BASE_URL}/user/transactions/csv`;
        });
    }

    if (downloadPdfBtn) {
        downloadPdfBtn.disabled = true;
        downloadPdfBtn.style.cursor = 'not-allowed';

        downloadPdfBtn.addEventListener('click', () => {
            alert('La fonctionnalité de téléchargement PDF est en cours de développement.');
        });
    }

    function applyBounceEffect(targetElement) {
        if (!targetElement) return;

        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

        targetElement.classList.add('bounce-effect');

        setTimeout(() => {
            targetElement.classList.remove('bounce-effect');
        }, 1000);
    }

    if (navLinkCryptos && cryptoListCard) {
        navLinkCryptos.addEventListener('click', function(event) {
            event.preventDefault();
            applyBounceEffect(cryptoListCard);
        });
    }

    if (navLinkHoldings && holdingsCard) {
        navLinkHoldings.addEventListener('click', function(event) {
            event.preventDefault();
            applyBounceEffect(holdingsCard);
        });
    }

    if (navLinkTransactions && profileSettingsModalEl && profileHistoryTabBtn) {
        navLinkTransactions.addEventListener('click', function(event) {
            event.preventDefault();

            const profileModalInstance = bootstrap.Modal.getInstance(profileSettingsModalEl) || new bootstrap.Modal(profileSettingsModalEl);
            const historyTabInstance = bootstrap.Tab.getInstance(profileHistoryTabBtn) || new bootstrap.Tab(profileHistoryTabBtn);

            if (profileModalInstance && historyTabInstance) {
                historyTabInstance.show();
            } else {
                console.error('Impossible d\'obtenir l\'instance de modale ou d\'onglet pour le lien Transactions.');
            }
        });
    }

    if (viewTransactionsBtn && profileSettingsModalEl && profileHistoryTabBtn) {
        viewTransactionsBtn.addEventListener('click', function() {
            const historyTabInstance = bootstrap.Tab.getInstance(profileHistoryTabBtn) || new bootstrap.Tab(profileHistoryTabBtn);
            if (historyTabInstance) {
                historyTabInstance.show();
            }
        });
    }

    if (accountSettingsBtn && profileSettingsModalEl && profileUserTabBtn) {
        accountSettingsBtn.addEventListener('click', function() {
            const userTabInstance = bootstrap.Tab.getInstance(profileUserTabBtn) || new bootstrap.Tab(profileUserTabBtn);
            if (userTabInstance) {
                userTabInstance.show();
            }
        });
    }

    // --- Nouveau : Listener pour Activation Onglet Stats ---
    if (profileStatsTabBtn) {
        profileStatsTabBtn.addEventListener('shown.bs.tab', function (event) {
            fetchAndRenderStats(); // Je fetch et rends les stats quand l'onglet est affiché
        });
    }

    // --- Nouveau : Listener pour Bouton Téléchargement Graphique Allocation ---
    if (downloadAllocationChartBtn) {
        downloadAllocationChartBtn.addEventListener('click', () => {
            if (allocationChart) {
                const url = allocationChart.toBase64Image();
                const link = document.createElement('a');
                link.href = url;
                link.download = 'cryptotrade-repartition-actifs.png';
                document.body.appendChild(link); // Requis pour Firefox
                link.click();
                document.body.removeChild(link);
            } else {
                console.error("Instance du graphique d'allocation non trouvée pour le téléchargement.");
            }
        });
    }

    if (viewStatsBtn && profileSettingsModalEl && profileStatsTabBtn) {
        viewStatsBtn.addEventListener('click', function() {
            // L'ouverture/fermeture de la modale est gérée par les attributs data, je dois juste changer d'onglet
            const statsTabInstance = bootstrap.Tab.getInstance(profileStatsTabBtn) || new bootstrap.Tab(profileStatsTabBtn);
            if (statsTabInstance) {
                statsTabInstance.show();
            }
        });
    }

    fetchDashboardData();
    const REFRESH_INTERVAL = 60000;
    console.log(`Mise en place du rafraîchissement auto toutes les ${REFRESH_INTERVAL / 1000} secondes.`);
    setInterval(fetchDashboardData, REFRESH_INTERVAL);

    // --- Fonctions & Listeners Admin ---
    // Aide pour afficher les messages dans la section modale admin
    function displayAdminCurrencyMessage(message, isError = false) {
        if (!adminMessageArea) return;
        adminMessageArea.textContent = message;
        adminMessageArea.className = `alert ${isError ? 'alert-danger' : 'alert-success'}`;
    }

    // Réinitialise le formulaire de devise admin à son état par défaut (pour ajout)
    function clearAdminCurrencyForm() {
        if (!adminCurrencyForm) return;
        adminCurrencyForm.reset(); // Réinitialise les champs du formulaire
        if (adminCurrencyIdInput) adminCurrencyIdInput.value = ''; // Vide l'ID caché
        if (adminUpdateBtn) adminUpdateBtn.disabled = true;
        if (adminDeleteBtn) adminDeleteBtn.disabled = true;
        if (adminAddBtn) adminAddBtn.disabled = false;
        if (adminMessageArea) adminMessageArea.classList.add('d-none'); // Masque les messages
    }

    // Fetch la liste des devises et peuple le dropdown admin
    async function loadAdminCurrenciesDropdown() {
        if (!adminCurrencySelect) return;

        try {
            const response = await fetch(`${API_BASE_URL}/admin/currencies`); // J'utilise le nouvel endpoint admin
            if (!response.ok) throw new Error(`Erreur API: ${response.status}`);
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                // Je vide les options existantes (garde la première "Ajouter Nouvelle")
                adminCurrencySelect.innerHTML = '<option value="new" selected>-- Ajouter Nouvelle Crypto --</option>';
                result.data.forEach(currency => {
                    const option = document.createElement('option');
                    option.value = currency.id;
                    option.textContent = `${currency.name} (${currency.symbol})`;
                    adminCurrencySelect.appendChild(option);
                });
            } else {
                throw new Error(result.message || 'Échec chargement devises');
            }
        } catch (error) {
            console.error('Erreur chargement devises admin:', error);
             displayAdminCurrencyMessage(`Erreur chargement devises: ${error.message}`, true);
        }
    }

    // Fetch les détails d'une devise spécifique et peuple le formulaire admin
    async function loadCurrencyDetailsForAdminForm(currencyId) {
        if (!currencyId || currencyId === 'new' || !adminCurrencyForm) return;
        clearAdminCurrencyForm(); // Je commence avec un formulaire propre

        try {
            const response = await fetch(`${API_BASE_URL}/admin/currency/${currencyId}`);
            if (!response.ok) {
                if (response.status === 404) throw new Error('Devise non trouvée.');
                throw new Error(`Erreur API: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                const details = result.data;
                if (adminCurrencyIdInput) adminCurrencyIdInput.value = details.id;
                if (adminCurrencyNameInput) adminCurrencyNameInput.value = details.name || '';
                if (adminCurrencySymbolInput) adminCurrencySymbolInput.value = details.symbol || '';
                if (adminCurrencyPriceInput) adminCurrencyPriceInput.value = details.current_price_cad || '';
                if (adminCurrencyChangeInput) adminCurrencyChangeInput.value = details.change_24h_percent || '';
                if (adminCurrencyMarketCapInput) adminCurrencyMarketCapInput.value = details.market_cap_cad || '';
                if (adminCurrencyVolatilityInput) adminCurrencyVolatilityInput.value = details.base_volatility || '';
                if (adminCurrencyTrendInput) adminCurrencyTrendInput.value = details.base_trend || '';

                // J'active MàJ/Suppr, désactive Ajout
                if (adminUpdateBtn) adminUpdateBtn.disabled = false;
                if (adminDeleteBtn) adminDeleteBtn.disabled = false;
                if (adminAddBtn) adminAddBtn.disabled = true;
            } else {
                throw new Error(result.message || 'Échec chargement détails devise');
            }
        } catch (error) {
            console.error(`Erreur chargement détails pour ${currencyId}:`, error);
            displayAdminCurrencyMessage(`Erreur chargement détails: ${error.message}`, true);
             clearAdminCurrencyForm(); // Je réinitialise le formulaire en cas d'erreur
        }
    }

    // --- Fonctions & Listeners Profil Utilisateur ---
    // Aide pour afficher les messages dans la section modale profil user
    function displayUserProfileMessage(message, isError = false) {
        if (!userProfileMessageArea) return;
        userProfileMessageArea.textContent = message;
        userProfileMessageArea.className = `alert ${isError ? 'alert-danger' : 'alert-success'}`;
        userProfileMessageArea.classList.remove('d-none');
         // Optionnel : masque après quelques secondes
         setTimeout(() => {
            if (userProfileMessageArea) {
                 userProfileMessageArea.classList.add('d-none');
                 userProfileMessageArea.textContent = '';
                 userProfileMessageArea.className = 'mb-3'; // Je réinitialise la classe
             }
         }, 5000); // Masque après 5 secondes
    }
});