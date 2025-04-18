// ADDED: Debug log to check global CSRF variables
console.log("CSRF Check:", { token: typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : 'NOT DEFINED', fieldName: typeof CSRF_FIELD_NAME !== 'undefined' ? CSRF_FIELD_NAME : 'NOT DEFINED' });

// Global Constants (from PHP - ensure they are defined before this script)
// const CSRF_TOKEN = \"...\"; // Example, will be injected by PHP
// const CSRF_FIELD_NAME = \"_csrf_token\"; // Example
// const BASE_URL = \"http://localhost/cryptotrade\"; // Example, injected by PHP

// Base URL for API calls (can be dynamic based on environment)
// const API_BASE_URL = \"/cryptotrade/api\"; // CHANGED: Use absolute URL
const API_BASE_URL = `${BASE_URL}/api`; // Use the injected BASE_URL

// Chart instances (to manage updates/destruction)
let portfolioChartInstance = null;

// ADDED: fetchWithCsrf function definition
/**
 * NEW: Wrapper for fetch requests to automatically include CSRF token in body for POST/DELETE.
 * Handles JSON parsing and basic error handling.
 */
async function fetchWithCsrf(url, options = {}) {
    const method = options.method ? options.method.toUpperCase() : 'GET';

    // Add CSRF token to body for state-changing requests
    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
        // Ensure body exists and is an object or FormData
        let bodyData;
        if (options.body instanceof FormData) {
            bodyData = options.body;
        } else if (typeof options.body === 'string') {
            try {
                bodyData = JSON.parse(options.body);
            } catch (e) {
                console.error('Could not parse options.body as JSON. Ensure it\'s a valid JSON string or an object/FormData.');
                bodyData = {}; // Fallback to an empty object
            }
        } else {
            bodyData = options.body || {};
        }

        // Add CSRF token
        if (bodyData instanceof FormData) {
            bodyData.append(CSRF_FIELD_NAME, CSRF_TOKEN);
        } else {
             // Assume it's a plain object for JSON stringify later
            bodyData[CSRF_FIELD_NAME] = CSRF_TOKEN;
        }

        // Re-assign the modified body
        // If it was FormData, keep it as FormData
        // Otherwise, stringify the object for JSON
        if (!(bodyData instanceof FormData)) {
            options.body = JSON.stringify(bodyData);
            // Ensure Content-Type is set for JSON
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
            // Try to parse error message from JSON response
            let errorData = { message: `HTTP error! Status: ${response.status}` };
            try {
                const errorJson = await response.json();
                if (errorJson && errorJson.message) {
                    errorData.message = errorJson.message;
                }
            } catch (e) {
                // Ignore if response is not JSON
            }
            // Log the error with more context
             console.error(`Fetch error for ${url}: ${response.status} - ${errorData.message}`);
             // Add the status code to the error object for more specific handling
             errorData.statusCode = response.status;
            throw new Error(errorData.message, { cause: errorData });
        }
        // Handle no content responses (e.g., successful DELETE)
        if (response.status === 204) {
            return { success: true }; // Or return null/undefined based on convention
        }
        return await response.json();
    } catch (error) {
        console.error('Fetch Error:', error);
        // Re-throw the error to be caught by the calling function
        // Include the status code if available from the fetch error cause
        const statusCode = error.cause?.statusCode;
        throw new Error(error.message, { cause: { statusCode } });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const API_BASE_URL = `${BASE_URL}/api`; // Use the injected BASE_URL

    // --- Selectors ---
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

    // --- Modal Trade Form Selectors ---
    const modalCryptoAmountInput = document.getElementById('modalCryptoAmount');
    const modalCryptoQuantityInput = document.getElementById('modalCryptoQuantity');
    const modalBuyButton = document.getElementById('modalBuyButton');
    const modalSellButton = document.getElementById('modalSellButton');
    const modalSellAllButton = document.getElementById('modalSellAllButton');
    const modalTradeInfoEl = document.getElementById('modalTradeInfo');
    const modalTradeInfoTextEl = document.getElementById('modalTradeInfoText');

    // --- Modal Info Placeholders ---
    const modalInfoPriceEl = document.getElementById('modalInfoPrice');
    const modalInfoChangeEl = document.getElementById('modalInfoChange');
    const modalInfoMarketCapEl = document.getElementById('modalInfoMarketCap');

    // --- Admin Currency Management Selectors (NEW) ---
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

    // Confirmation Modal Selectors
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

    // --- User Profile Settings Selectors (NEW) ---
    const profileSettingsModalEl = document.getElementById('profileSettingsModal');
    const userProfileForm = document.getElementById('userProfileForm');
    const profileFullnameInput = document.getElementById('profileFullname');
    const profileEmailInput = document.getElementById('profileEmail');
    const profileCurrentPasswordInput = document.getElementById('profileCurrentPassword');
    const profileNewPasswordInput = document.getElementById('profileNewPassword');
    const profileConfirmPasswordInput = document.getElementById('profileConfirmPassword');
    const saveProfileButton = document.getElementById('saveProfileButton');
    const userProfileMessageArea = document.getElementById('userProfileMessage');

    // --- NEW: Statistics Tab Selectors ---
    const statsUnrealizedPLEl = document.getElementById('statsUnrealizedPL');
    const profileStatsTabBtn = document.getElementById('profile-stats-tab');
    const statsTotalCryptoValueEl = document.getElementById('statsTotalCryptoValue');
    const statsFiatBalanceEl = document.getElementById('statsFiatBalance');
    const allocationChartCanvas = document.getElementById('allocationChartCanvas');
    const statsAllocationLoadingEl = document.getElementById('statsAllocationLoading');
    const statsAllocationErrorEl = document.getElementById('statsAllocationError');
    const statsAllocationEmptyEl = document.getElementById('statsAllocationEmpty');
    const downloadAllocationChartBtn = document.getElementById('downloadAllocationChartBtn');

    // --- Transaction History Selectors (NEW) ---
    const transactionHistoryTableBody = document.getElementById('transactionHistoryTableBody');
    const downloadCsvBtn = document.getElementById('downloadCsvBtn');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');

    // --- NEW: Navbar Links & Target Cards for Effects ---
    const navLinkCryptos = document.getElementById('nav-link-cryptos');
    const navLinkHoldings = document.getElementById('nav-link-holdings');
    const navLinkTransactions = document.getElementById('nav-link-transactions');
    const cryptoListCard = document.getElementById('crypto-list-card');
    const holdingsCard = document.getElementById('holdings-card');
    const profileHistoryTabBtn = document.getElementById('profile-history-tab');

    // --- NEW: Account Detail Buttons ---
    const viewTransactionsBtn = document.getElementById('viewTransactionsBtn');
    const accountSettingsBtn = document.getElementById('accountSettingsBtn');
    const profileUserTabBtn = document.getElementById('profile-user-tab');
    const viewStatsBtn = document.getElementById('viewStatsBtn');

    // --- NEW: Dark Mode Selectors ---
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const htmlElement = document.documentElement;

    let balanceVisible = true;
    let userBalanceRaw = 0;
    let selectedCryptoForTrade = null;
    let currentUserFullname = '';
    let currentUserEmail = '';
    let userHoldings = {};

    // --- Chart Instances ---
    let portfolioChart = null;
    let cryptoListChartInstances = {};
    let modalChartInstance = null;
    let allocationChart = null; // NEW: Chart instance for allocation

    // --- Store context for the confirmation modal ---
    let currentChartFetchController = null;
    let confirmationContext = {
        action: '',
        crypto: null,
        quantity: 0,
        amountCad: 0
    };

    // --- Dark Mode Logic ---
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

    // --- Helper Functions ---
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

    // --- REFACTORED Update Trade Form Logic ---
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

    // --- REFACTORED Confirmation Modal Trigger ---
    function showConfirmationModal(action, crypto, quantity, amountCad, sourceInfoEl = null) {
        const sourceInfoTextEl = sourceInfoEl ? sourceInfoEl.querySelector('span') : null;

        function displayValidationError(message) {
            console.warn(`Validation Error (${action}): ${message}`);
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

    // --- Transaction Execution --- (Uses confirmationContext)
    async function executeTransaction() {
        if (!confirmTransactionBtn || !confirmErrorEl) return;

        const { action, crypto, quantity, amountCad } = confirmationContext;

        if (!action || !crypto || !crypto.id || isNaN(quantity) || quantity <= 0 || isNaN(amountCad) || amountCad <= 0) {
            console.error("Invalid confirmation context:", confirmationContext);
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

    // --- Rendering Functions ---
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
             if (!response.ok) throw new Error(`Mini chart HTTP error! Status: ${response.status}`);
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
         } catch (error) { console.error(`Error fetching mini chart for ${canvasId}:`, error); }
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
        console.log(`>>> Entering displayChartInModal for ID: ${modalData?.id}`);

        // Check all required modal elements exist
        if (!cryptoChartModalEl || !modalChartLoadingEl || !modalChartContainerEl || !modalChartErrorEl || !modalChartTitleEl || !modalChartCanvas || !modalInfoPriceEl || !modalInfoChangeEl || !modalInfoMarketCapEl || !modalCryptoAmountInput || !modalCryptoQuantityInput || !modalBuyButton || !modalSellButton || !modalSellAllButton || !modalTradeInfoEl)
        {
             console.error("Un ou plusieurs éléments de la modale étendue sont manquants.");
             return;
        }

        // --- Force Cleanup & Reset State FIRST ---
        if (modalChartInstance) {
            console.log("   -> Destroying previous modal chart instance.");
            modalChartInstance.destroy();
            modalChartInstance = null;
        }
        // Abort any pending fetch request for the *previous* chart
        if (currentChartFetchController) {
            console.log("   -> Aborting previous chart fetch request.");
            currentChartFetchController.abort();
        }

        console.log("   -> Resetting modal DOM elements (show loading, hide chart/error).");
        modalChartLoadingEl.classList.remove('d-none');
        modalChartContainerEl.classList.add('d-none');
        modalChartCanvas.classList.add('d-none');
        modalChartErrorEl.classList.add('d-none');
        // --- End Cleanup & Reset ---

        const { id: currencyId, name: cryptoName, symbol: cryptoSymbol, priceCad, changePercent, marketCap } = modalData;

        // Prepare modal state
        modalChartTitleEl.textContent = `Graphique : ${cryptoName || ''} (${cryptoSymbol || ''})`;

        // --- Populate Info Section --- (Do this *before* fetching chart)
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

        // --- Setup Listeners (using cloning to prevent duplicates) --- 
        const setupModalListeners = () => {
            console.log("   -> setupModalListeners: Re-selecting modal trade elements...");
            const amountInputToReplace = document.getElementById('modalCryptoAmount');
            const quantityInputToReplace = document.getElementById('modalCryptoQuantity');
            const buyButtonToReplace = document.getElementById('modalBuyButton');
            const sellButtonToReplace = document.getElementById('modalSellButton');
            const sellAllButtonToReplace = document.getElementById('modalSellAllButton');
            const infoElementForListeners = document.getElementById('modalTradeInfo');

            // Check if elements were found *now*
            if (!amountInputToReplace || !quantityInputToReplace || !buyButtonToReplace || !sellButtonToReplace || !sellAllButtonToReplace || !infoElementForListeners) {
                console.error("   -> ERROR in setupModalListeners: One or more modal trade elements not found before cloning!");
                return; // Stop if elements are missing
            }
            console.log("   -> setupModalListeners: All elements found.");

            // Clone elements to remove old listeners
            const freshAmountInput = amountInputToReplace.cloneNode(true);
            const freshQuantityInput = quantityInputToReplace.cloneNode(true);
            const freshBuyButton = buyButtonToReplace.cloneNode(true);
            const freshSellButton = sellButtonToReplace.cloneNode(true);
            const freshSellAllButton = sellAllButtonToReplace.cloneNode(true);

            console.log("   -> setupModalListeners: Replacing elements with clones...");
            amountInputToReplace.parentNode.replaceChild(freshAmountInput, amountInputToReplace);
            quantityInputToReplace.parentNode.replaceChild(freshQuantityInput, quantityInputToReplace);
            buyButtonToReplace.parentNode.replaceChild(freshBuyButton, buyButtonToReplace);
            sellButtonToReplace.parentNode.replaceChild(freshSellButton, sellButtonToReplace);
            sellAllButtonToReplace.parentNode.replaceChild(freshSellAllButton, sellAllButtonToReplace);

            console.log("   -> setupModalListeners: Attaching listeners to cloned elements...");

            // Attach listeners to fresh elements
            freshAmountInput.addEventListener('input', () => {
                updateCorrespondingTradeInput('amount', freshAmountInput, freshQuantityInput, modalData.priceCad, freshBuyButton, freshSellButton, infoElementForListeners);
            });
            freshQuantityInput.addEventListener('input', () => {
                updateCorrespondingTradeInput('quantity', freshQuantityInput, freshAmountInput, modalData.priceCad, freshBuyButton, freshSellButton, infoElementForListeners);
            });
            freshBuyButton.addEventListener('click', () => {
                const quantity = parseFloat(freshQuantityInput.value);
                const amountCad = parseFloat(freshAmountInput.value);
                // Hide the current chart modal FIRST
                const chartModalInstance = bootstrap.Modal.getInstance(cryptoChartModalEl);
                if (chartModalInstance) {
                    chartModalInstance.hide();
                }
                // THEN show the confirmation modal
                showConfirmationModal('Acheter', modalData, quantity, amountCad, infoElementForListeners);
            });
            freshSellButton.addEventListener('click', () => {
                const quantity = parseFloat(freshQuantityInput.value);
                const amountCad = parseFloat(freshAmountInput.value);
                // Hide the current chart modal FIRST
                const chartModalInstance = bootstrap.Modal.getInstance(cryptoChartModalEl);
                if (chartModalInstance) {
                    chartModalInstance.hide();
                }
                // THEN show the confirmation modal
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

        // --- Create AbortController for the new fetch --- 
        currentChartFetchController = new AbortController();
        const signal = currentChartFetchController.signal;

        // --- Fetch and Render Chart --- 
        console.log(`   -> Initiating fetch for chart data (ID: ${currencyId})...`);
        try {
            const isDarkMode = htmlElement.getAttribute('data-bs-theme') === 'dark';
            if (!currencyId) throw new Error('ID de devise manquant.');
            const response = await fetch(`${API_BASE_URL}/crypto/chart/${currencyId}`, { signal }); // Pass signal
            console.log(`   -> Fetch response received (Status: ${response.status})`);
            if (!response.ok) throw new Error(`Erreur API (${response.status})`);
            const result = await response.json();
            if (result.success && result.data?.datasets?.[0]?.data) {
                console.log("   -> API data successful. Preparing to render chart.");
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
                console.log("   -> Chart rendered. Showing canvas and container.");
                modalChartCanvas.classList.remove('d-none'); // Show canvas
                modalChartContainerEl.classList.remove('d-none'); // Show container
                if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = false;
            } else {
                console.log("   -> API data format invalid or success=false.");
                throw new Error(result.message || 'Format de données invalide');
            }
        } catch (error) {
             if (error.name === 'AbortError') {
                 console.log("   -> Chart fetch aborted (likely due to new modal opening). No error shown.");
             } else {
                 console.error(`>>> ERREUR fetch/render chart (ID: ${currencyId}):`, error);
                 modalChartLoadingEl.classList.add('d-none');
                 modalChartErrorEl.textContent = `Erreur chargement graphique: ${error.message}`;
                 modalChartErrorEl.classList.remove('d-none');
                 modalChartCanvas.classList.add('d-none'); // Keep canvas hidden on error
                 if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
             }
        }
    }

    // --- NEW: Statistics Tab Functions ---
    async function fetchAndRenderStats() {
        console.log(">>> fetchAndRenderStats triggered");
        if (!allocationChartCanvas || !statsAllocationLoadingEl || !statsAllocationErrorEl || !statsTotalCryptoValueEl || !statsFiatBalanceEl || !statsAllocationEmptyEl || !statsUnrealizedPLEl || !downloadAllocationChartBtn) {
            console.error("Stats tab elements not found.");
            return;
        }

        // Reset state
        statsAllocationLoadingEl.classList.remove('d-none');
        statsAllocationErrorEl.classList.add('d-none');
        if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
        statsAllocationEmptyEl.classList.add('d-none');
        allocationChartCanvas.style.display = 'none'; // Hide canvas initially
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
                throw new Error(`API Error: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data) {
                const statsData = result.data;

                // Update summary cards
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

                // Prepare chart data
                const allocation = statsData.allocation || [];
                if (allocation.length === 0) {
                    statsAllocationEmptyEl.classList.remove('d-none');
                    statsAllocationLoadingEl.classList.add('d-none');
                    if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
                    return; // No data to chart
                }

                const labels = allocation.map(item => item.symbol);
                const dataValues = allocation.map(item => item.percentage);

                const chartConfigData = {
                    labels: labels,
                    datasets: [{
                        label: 'Répartition (%)',
                        data: dataValues,
                        // Add nice background colors (can be generated dynamically later)
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(99, 255, 132, 0.7)', // More colors if needed
                            'rgba(235, 54, 162, 0.7)',
                            'rgba(86, 255, 206, 0.7)',
                            'rgba(192, 75, 192, 0.7)'
                        ],
                        borderColor: htmlElement.getAttribute('data-bs-theme') === 'dark' ? '#333' : '#fff', // Border color based on theme
                        borderWidth: 1
                    }]
                };

                const ctx = allocationChartCanvas.getContext('2d');
                allocationChart = new Chart(ctx, {
                    type: 'doughnut', // Or 'pie'
                    data: chartConfigData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                     color: htmlElement.getAttribute('data-bs-theme') === 'dark' ? '#fff' : '#666' // Legend color based on theme
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
                                            // Find the original allocation item to show name and CAD value
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

                allocationChartCanvas.style.display = 'block'; // Show canvas
                if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = false;

            } else {
                throw new Error(result.message || 'Failed to process stats data');
            }

        } catch (error) {
            console.error("Error fetching/rendering stats:", error);
            if(downloadAllocationChartBtn) downloadAllocationChartBtn.disabled = true;
            statsAllocationErrorEl.classList.remove('d-none');
        } finally {
            statsAllocationLoadingEl.classList.add('d-none');
        }
    }

    // --- End NEW Statistics Tab Functions ---

    // --- Main Fetch Function ---
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
                    console.error('Dashboard API data processing failed:', resultDash.message || 'Unknown error');
                }
            } else {
                 console.error(`Dashboard API error! status: ${dashResponse.status}`);
            }

            if (listResponse.ok) {
                const resultList = await listResponse.json();
                if (resultList.success && resultList.data) {
                    if (cryptoListTableBody) renderCryptoList(resultList.data);
                } else {
                    console.error('Crypto List API data processing failed:', resultList.message || 'Unknown error');
                    if (cryptoListTableBody) cryptoListTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur chargement cryptos.</td></tr>';
                }
            } else {
                console.error(`Crypto List API error! status: ${listResponse.status}`);
                if (cryptoListTableBody) cryptoListTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur chargement cryptos.</td></tr>';
            }

        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        }
    }

    // --- Event Listeners ---
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
                console.warn("Modal opened without valid crypto data trigger button.");
            }
        });

        cryptoChartModalEl.addEventListener('hidden.bs.modal', function () {
            // NO chart destruction here. It's handled at the start of displayChartInModal.
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
                console.warn('Sell All (Main): No crypto selected.'); return;
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
            console.error('Error adding currency:', error);
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
             console.error('Error updating currency:', error);
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
                console.error('Error deleting currency:', error);
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
             // Optionally reset admin form or leave as is depending on desired UX
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
            console.error('Error updating profile:', error);
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
                throw new Error(result.message || 'Failed to load transactions');
            }
        } catch (error) {
            console.error('Error fetching transactions:', error);
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
                console.error('Could not get modal or tab instance for Transactions link.');
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

    // --- NEW: Listener for Stats Tab Activation ---
    if (profileStatsTabBtn) {
        profileStatsTabBtn.addEventListener('shown.bs.tab', function (event) {
            fetchAndRenderStats(); // Fetch and render stats when the tab is shown
        });
    }

    // --- NEW: Listener for Allocation Chart Download Button ---
    if (downloadAllocationChartBtn) {
        downloadAllocationChartBtn.addEventListener('click', () => {
            if (allocationChart) {
                const url = allocationChart.toBase64Image();
                const link = document.createElement('a');
                link.href = url;
                link.download = 'cryptotrade-repartition-actifs.png';
                document.body.appendChild(link); // Required for Firefox
                link.click();
                document.body.removeChild(link);
            } else {
                console.error("Allocation chart instance not found for download.");
            }
        });
    }

    if (viewStatsBtn && profileSettingsModalEl && profileStatsTabBtn) {
        viewStatsBtn.addEventListener('click', function() {
            // Modal toggle is handled by data attributes, we just need to switch tab
            const statsTabInstance = bootstrap.Tab.getInstance(profileStatsTabBtn) || new bootstrap.Tab(profileStatsTabBtn);
            if (statsTabInstance) {
                statsTabInstance.show();
            }
        });
    }

    fetchDashboardData();
    const REFRESH_INTERVAL = 60000;
    console.log(`Setting up auto-refresh every ${REFRESH_INTERVAL / 1000} seconds.`);
    setInterval(fetchDashboardData, REFRESH_INTERVAL);

    // --- Admin Functions & Listeners ---
    // Helper to display messages in the admin modal section
    function displayAdminCurrencyMessage(message, isError = false) {
        if (!adminMessageArea) return;
        adminMessageArea.textContent = message;
        adminMessageArea.className = `alert ${isError ? 'alert-danger' : 'alert-success'}`;
    }

    // Reset the admin currency form to its default state (for adding new)
    function clearAdminCurrencyForm() {
        if (!adminCurrencyForm) return;
        adminCurrencyForm.reset(); // Resets form fields
        if (adminCurrencyIdInput) adminCurrencyIdInput.value = ''; // Clear hidden ID
        if (adminUpdateBtn) adminUpdateBtn.disabled = true;
        if (adminDeleteBtn) adminDeleteBtn.disabled = true;
        if (adminAddBtn) adminAddBtn.disabled = false;
        if (adminMessageArea) adminMessageArea.classList.add('d-none'); // Hide messages
    }

    // Fetch the list of currencies and populate the admin dropdown
    async function loadAdminCurrenciesDropdown() {
        if (!adminCurrencySelect) return;

        try {
            const response = await fetch(`${API_BASE_URL}/admin/currencies`); // Use new admin endpoint
            if (!response.ok) throw new Error(`API Error: ${response.status}`);
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                // Clear existing options (keep the first "Add New" option)
                adminCurrencySelect.innerHTML = '<option value="new" selected>-- Ajouter Nouvelle Crypto --</option>';
                result.data.forEach(currency => {
                    const option = document.createElement('option');
                    option.value = currency.id;
                    option.textContent = `${currency.name} (${currency.symbol})`;
                    adminCurrencySelect.appendChild(option);
                });
            } else {
                throw new Error(result.message || 'Failed to load currencies');
            }
        } catch (error) {
            console.error('Error loading admin currencies:', error);
             displayAdminCurrencyMessage(`Erreur chargement devises: ${error.message}`, true);
        }
    }

    // Fetch details for a specific currency and populate the admin form
    async function loadCurrencyDetailsForAdminForm(currencyId) {
        if (!currencyId || currencyId === 'new' || !adminCurrencyForm) return;
        clearAdminCurrencyForm(); // Start with a clean slate

        try {
            const response = await fetch(`${API_BASE_URL}/admin/currency/${currencyId}`);
            if (!response.ok) {
                if (response.status === 404) throw new Error('Devise non trouvée.');
                throw new Error(`API Error: ${response.status}`);
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

                // Enable Update/Delete, disable Add
                if (adminUpdateBtn) adminUpdateBtn.disabled = false;
                if (adminDeleteBtn) adminDeleteBtn.disabled = false;
                if (adminAddBtn) adminAddBtn.disabled = true;
            } else {
                throw new Error(result.message || 'Failed to load currency details');
            }
        } catch (error) {
            console.error(`Error loading currency details for ${currencyId}:`, error);
            displayAdminCurrencyMessage(`Erreur chargement détails: ${error.message}`, true);
             clearAdminCurrencyForm(); // Reset form on error
        }
    }

    // --- User Profile Functions & Listeners ---
    // Helper to display messages in the user profile modal section
    function displayUserProfileMessage(message, isError = false) {
        if (!userProfileMessageArea) return;
        userProfileMessageArea.textContent = message;
        userProfileMessageArea.className = `alert ${isError ? 'alert-danger' : 'alert-success'}`;
        userProfileMessageArea.classList.remove('d-none');
         // Optionally hide after a few seconds
         setTimeout(() => {
            if (userProfileMessageArea) {
                 userProfileMessageArea.classList.add('d-none');
                 userProfileMessageArea.textContent = '';
                 userProfileMessageArea.className = 'mb-3'; // Reset class
             }
         }, 5000); // Hide after 5 seconds
    }
});