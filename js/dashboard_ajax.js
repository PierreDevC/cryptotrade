// PASTEBIN_DISABLED
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE_URL = '/cryptotrade/api'; // Use root-relative path
    const USD_TO_CAD_RATE = 1.35; // TODO: Fetch this from config or API

    // --- Selectors ---
    const accountTypeEl = document.querySelector('.account-type');
    const accountStatusEl = document.querySelector('.account-status');
    const accountLastLoginEl = document.querySelector('.account-last-login strong');
    const balanceAmountEl = document.getElementById('balanceAmount');
    const hiddenBalanceEl = document.getElementById('hiddenBalance');
    const toggleBalanceBtn = document.getElementById('toggleBalance');
    const weeklyGainEl = document.querySelector('.weekly-gain');
    const portfolioTotalValueEl = document.getElementById('portfolioTotalValue');
    const portfolioCryptoValueEl = document.getElementById('portfolioCryptoValue');
    const portfolioChange24hEl = document.getElementById('portfolioChange24h');
    const holdingsTableBody = document.getElementById('holdingsTableBody');
    const cryptoListTableBody = document.getElementById('cryptoListTableBody');
    const cryptoChartModalEl = document.getElementById('cryptoChartModal'); // <<< VERIFIER que cet ID existe dans dashboard.php
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
    const tradeInfoEl = document.getElementById('tradeInfo');
    const tradeInfoTextEl = document.getElementById('tradeInfoText');

    // Confirmation Modal Selectors
    const confirmationModalEl = document.getElementById('confirmationModal');
    const confirmationModal = confirmationModalEl ? new bootstrap.Modal(confirmationModalEl) : null;
    const confirmActionEl = document.getElementById('confirmAction');
    const confirmCryptoNameEl = document.getElementById('confirmCryptoName');
    const confirmCryptoSymbolEl = document.getElementById('confirmCryptoSymbol');
    const confirmQuantityEl = document.getElementById('confirmQuantity');
    const confirmPriceUsdEl = document.getElementById('confirmPriceUsd');
    const confirmAmountCadEl = document.getElementById('confirmAmountCad');
    const confirmErrorEl = document.getElementById('confirmationError');
    const confirmTransactionBtn = document.getElementById('confirmTransactionBtn');

    let balanceVisible = true;
    let userBalanceRaw = 0;
    let selectedCryptoForTrade = null;

    // --- Chart Instances ---
    let portfolioChart = null;
    let cryptoListChartInstances = {};
    let modalChartInstance = null;

    // --- Helper Functions ---
    function formatCurrency(value, currency = 'CAD') {
        if (typeof value !== 'number' || isNaN(value)) return 'N/A';
        return new Intl.NumberFormat('fr-CA', { style: 'currency', currency: currency }).format(value);
    }
    function formatUsd(value) {
        if (typeof value !== 'number' || isNaN(value)) return 'N/A';
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    }
    function getChangeClass(value) {
        const numValue = parseFloat(value);
        if (numValue > 0) return 'text-success';
        if (numValue < 0) return 'text-danger';
        return 'text-muted';
    }

    // --- Update Trade Form --- NEW
    function updateTradeForm(source) {
        if (!selectedCryptoForTrade || !amountInput || !quantityInput) return;

        const priceUsd = selectedCryptoForTrade.priceUsd;
        let amountUsd = parseFloat(amountInput.value);
        let quantity = parseFloat(quantityInput.value);

        if (source === 'amount') {
            if (!isNaN(amountUsd) && amountUsd > 0 && priceUsd > 0) {
                quantity = amountUsd / priceUsd;
                quantityInput.value = quantity.toFixed(8); // Adjust precision as needed
            } else if (amountInput.value === '') {
                 quantityInput.value = '';
            }
        } else if (source === 'quantity') {
            if (!isNaN(quantity) && quantity > 0 && priceUsd > 0) {
                amountUsd = quantity * priceUsd;
                amountInput.value = amountUsd.toFixed(2);
            } else if (quantityInput.value === '') {
                 amountInput.value = '';
            }
        }

        // Enable/Disable buttons
        const finalAmount = parseFloat(amountInput.value);
        const finalQuantity = parseFloat(quantityInput.value);
        const isValid = !isNaN(finalAmount) && finalAmount > 0 && !isNaN(finalQuantity) && finalQuantity > 0;

        if(buyButton) buyButton.disabled = !isValid;
        if(sellButton) sellButton.disabled = !isValid;

        // Clear previous info messages
        if (tradeInfoEl) tradeInfoEl.classList.add('d-none');
    }

    // --- Transaction Logic --- NEW
    async function executeTransaction(details) {
        if (!confirmTransactionBtn || !confirmErrorEl) return;

        const originalButtonText = confirmTransactionBtn.innerHTML;
        confirmTransactionBtn.disabled = true;
        confirmTransactionBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> Exécution...`;
        confirmErrorEl.classList.add('d-none');
        confirmErrorEl.textContent = '';

        const endpoint = details.action === 'Acheter' ? '/transaction/buy' : '/transaction/sell';

        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    currencyId: details.currencyId,
                    quantity: details.quantity,
                    amountCAD: details.amountCAD // Send the confirmed CAD amount
                })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || `Erreur ${response.status} lors de la transaction.`);
            }

            // Success
            if(confirmationModal) confirmationModal.hide();
            // TODO: Show a success toast/alert instead of console log
            console.log('Transaction réussie:', result.message);
            // Refresh data
            fetchDashboardData();
            // Reset trade form (optional)
            if (amountInput) amountInput.value = '';
            if (quantityInput) quantityInput.value = '';
            if (buyButton) buyButton.disabled = true;
            if (sellButton) sellButton.disabled = true;
            if (tradeInfoEl) tradeInfoEl.classList.add('d-none');
            selectedCryptoForTrade = null; // Clear selection after successful trade
            if (nameElBuySell) nameElBuySell.textContent = 'Sélectionnez une cryptomonnaie...';
            if (priceElBuySell) priceElBuySell.textContent = `Prix actuel: --`;
            cryptoListTableBody.querySelector('tr.table-active')?.classList.remove('table-active');


        } catch (error) {
            console.error(`Erreur lors de l'exécution (${details.action}):`, error);
            confirmErrorEl.textContent = `Erreur: ${error.message}`;
            confirmErrorEl.classList.remove('d-none');
        } finally {
            confirmTransactionBtn.disabled = false;
            confirmTransactionBtn.innerHTML = originalButtonText;
        }
    }

    // --- Rendering Functions ---
    function renderHoldings(holdings) {
        if (!holdingsTableBody) return;
        holdingsTableBody.innerHTML = '';
        if (!holdings || holdings.length === 0) {
            holdingsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Vous ne détenez aucun actif.</td></tr>';
            return;
        }
        holdings.forEach(holding => {
            const changePercent = parseFloat(holding.currency_change_24h_percent);
            const changeValueFormatted = holding.currency_change_24h_value_cad_formatted;
            const changeClass = getChangeClass(changePercent);
            const row = `
                <tr>
                    <td><img src="${holding.image_url || 'https://via.placeholder.com/20'}" alt="${holding.symbol || ''}" width="20" height="20" class="me-2 align-middle"> <span class="align-middle">${holding.name || 'N/A'} (${holding.symbol || 'N/A'})</span></td>
                    <td>${holding.quantity || '0'}</td>
                    <td>${holding.current_price_cad_formatted || 'N/A'}</td>
                    <td>${holding.total_value_cad_formatted || 'N/A'}</td>
                    <td class="${changeClass}">${!isNaN(changePercent) ? changePercent.toFixed(2) + '%' : 'N/A'} <small class="d-block">(${changeValueFormatted || 'N/A'})</small></td>
                </tr>`;
            holdingsTableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    function renderPortfolioChart(chartData) {
        const ctx = document.getElementById('cryptoChart')?.getContext('2d');
        if (!ctx || !chartData || !chartData.labels || !chartData.values) return;
        if (portfolioChart) portfolioChart.destroy();
        portfolioChart = new Chart(ctx, {
            type: 'line', data: { labels: chartData.labels, datasets: [{ label: 'Valeur Totale du Portefeuille (CAD)', data: chartData.values, borderColor: '#2d3a36', backgroundColor: 'rgba(45, 58, 54, 0.1)', tension: 0.3, fill: true, pointRadius: 0, pointHoverRadius: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { display: false }, y: { beginAtZero: false, ticks: { callback: value => formatCurrency(value) } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => context.parsed.y !== null ? formatCurrency(context.parsed.y) : '' } } } }
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
             // DEBUG: Log the price value and type from the API
             console.log(`Crypto: ${crypto.symbol}, price_usd_raw:`, crypto.price_usd_raw, `(Type: ${typeof crypto.price_usd_raw})`);

             const changeClass = getChangeClass(crypto.change_24h_raw);
             const chartId = `miniChart-${crypto.symbol}`;
             const row = `
                 <tr style="cursor: pointer;" class="crypto-list-row" data-crypto-id="${crypto.id}" data-crypto-name="${crypto.name}" data-crypto-price-usd="${crypto.price_usd_raw}" data-crypto-symbol="${crypto.symbol}">
                     <td class="align-middle"><img src="${crypto.image_url || 'https://via.placeholder.com/20'}" alt="${crypto.symbol || ''}" width="20" height="20"></td>
                     <td class="align-middle">${crypto.rank || '#'}</td>
                     <td class="align-middle">${crypto.name || 'N/A'} <small class="text-muted">(${crypto.symbol || 'N/A'})</small></td>
                     <td class="align-middle">${formatUsd(crypto.price_usd_raw)}</td>
                     <td class="align-middle ${changeClass}">${crypto.change_24h || 'N/A'}</td>
                     <td class="align-middle">${crypto.market_cap || 'N/A'}</td>
                     <td class="align-middle" style="width: 100px; height: 40px;"><canvas id="${chartId}" style="max-height: 40px;"></canvas></td>
                     <td class="align-middle text-center">
                         <button class="btn btn-sm btn-outline-secondary view-chart-btn"
                                 data-bs-toggle="modal"
                                 data-bs-target="#cryptoChartModal"  /* <<< Vérifiez cet attribut */
                                 data-crypto-id="${crypto.id}"
                                 data-crypto-name="${crypto.name}"
                                 data-crypto-symbol="${crypto.symbol}"
                                 title="Voir graphique détaillé">
                             <i class="fas fa-chart-line"></i>
                         </button>
                         <button class="btn btn-sm btn-outline-success buy-btn-quick ms-1" title="Acheter ${crypto.symbol}"
                                 data-crypto-id="${crypto.id}"
                                 data-crypto-name="${crypto.name}"
                                 data-crypto-price-usd="${crypto.price_usd_raw}"
                                 data-crypto-symbol="${crypto.symbol}">
                             <i class="fas fa-shopping-cart"></i>
                         </button>
                         <button class="btn btn-sm btn-outline-danger sell-btn-quick ms-1" title="Vendre ${crypto.symbol}"
                                 data-crypto-id="${crypto.id}"
                                 data-crypto-name="${crypto.name}"
                                 data-crypto-price-usd="${crypto.price_usd_raw}"
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
             const response = await fetch(`${API_BASE_URL}/crypto/chart/${currencyId}`);
             if (!response.ok) throw new Error(`Mini chart HTTP error! Status: ${response.status}`);
             const result = await response.json();
             if (result.success && result.data?.datasets?.[0]?.data) {
                 const ctx = document.getElementById(canvasId)?.getContext('2d');
                 if (!ctx) return;
                 const chartColor = changeClass === 'text-success' ? 'rgba(25, 135, 84, 0.7)' : (changeClass === 'text-danger' ? 'rgba(220, 53, 69, 0.7)' : 'rgba(108, 117, 125, 0.7)');
                 cryptoListChartInstances[canvasId] = new Chart(ctx, {
                     type: 'line', data: { labels: result.data.labels, datasets: [{ data: result.data.datasets[0].data, borderColor: chartColor, borderWidth: 1.5, pointRadius: 0, tension: 0.4 }] },
                     options: { responsive: true, maintainAspectRatio: false, scales: { x: { display: false }, y: { display: false } }, plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: false }
                 });
             }
         } catch (error) { console.error(`Error fetching mini chart for ${canvasId}:`, error); }
     }

    function addCryptoListRowListeners() {
         const rows = cryptoListTableBody.querySelectorAll('.crypto-list-row');
         rows.forEach(row => {
             row.addEventListener('click', (event) => {
                 if (event.target.closest('.view-chart-btn')) return;
                 // NEW: Handle quick buy/sell clicks
                 const quickBuyBtn = event.target.closest('.buy-btn-quick');
                 const quickSellBtn = event.target.closest('.sell-btn-quick');

                 if (quickBuyBtn || quickSellBtn) {
                     const button = quickBuyBtn || quickSellBtn;
                     // Select the row
                     cryptoListTableBody.querySelector('tr.table-active')?.classList.remove('table-active');
                     row.classList.add('table-active');
                     // Populate the trade form
                     selectedCryptoForTrade = { id: button.dataset.cryptoId, name: button.dataset.cryptoName, priceUsd: parseFloat(button.dataset.cryptoPriceUsd), symbol: button.dataset.cryptoSymbol };
                     if (nameElBuySell) nameElBuySell.textContent = `${selectedCryptoForTrade.name} (${selectedCryptoForTrade.symbol})`;
                     if (priceElBuySell) priceElBuySell.textContent = `Prix actuel: ${formatUsd(selectedCryptoForTrade.priceUsd)}`;
                     if (amountInput) amountInput.value = ''; if (quantityInput) quantityInput.value = '';
                     if (buyButton) buyButton.disabled = true; // Will be enabled by input
                     if (sellButton) sellButton.disabled = true; // Will be enabled by input
                     if (tradeInfoEl) tradeInfoEl.classList.add('d-none');

                     // Scroll to the form (optional)
                     const tradeFormElement = document.getElementById('cryptoTradeForm');
                     if(tradeFormElement) tradeFormElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

                     // Focus the amount input for buy, quantity for sell?
                     if (amountInput && quickBuyBtn) amountInput.focus();
                     if (quantityInput && quickSellBtn) quantityInput.focus();

                     return; // Don't proceed with normal row selection
                 }

                 // Original row selection logic
                 cryptoListTableBody.querySelector('tr.table-active')?.classList.remove('table-active');
                 row.classList.add('table-active');
                 selectedCryptoForTrade = { id: row.dataset.cryptoId, name: row.dataset.cryptoName, priceUsd: parseFloat(row.dataset.cryptoPriceUsd), symbol: row.dataset.cryptoSymbol };
                 if (nameElBuySell) nameElBuySell.textContent = `${selectedCryptoForTrade.name} (${selectedCryptoForTrade.symbol})`;
                 if (priceElBuySell) priceElBuySell.textContent = `Prix actuel: ${formatUsd(selectedCryptoForTrade.priceUsd)}`;
                 if (amountInput) amountInput.value = ''; if (quantityInput) quantityInput.value = '';
                 if (buyButton) buyButton.disabled = false; if (sellButton) sellButton.disabled = false;
                 if (tradeInfoEl) tradeInfoEl.classList.add('d-none');
             });
         });
     }

    async function displayChartInModal(currencyId, cryptoName, cryptoSymbol) {
        if (!cryptoChartModalEl || !modalChartLoadingEl || !modalChartContainerEl || !modalChartErrorEl || !modalChartTitleEl || !modalChartCanvas) {
             console.error("Un ou plusieurs éléments du modal sont manquants dans le DOM.");
             return;
        }
        console.log(`>>> Début displayChartInModal pour ID: ${currencyId}`);

        modalChartLoadingEl.classList.remove('d-none');
        modalChartContainerEl.classList.add('d-none');
        modalChartErrorEl.classList.add('d-none');
        modalChartTitleEl.textContent = `Graphique : ${cryptoName || ''} (${cryptoSymbol || ''})`;
        if (modalChartInstance) modalChartInstance.destroy();
        modalChartInstance = null;

        try {
            if (!currencyId) throw new Error('ID de devise manquant.');
            console.log(`>>> Appel API: /api/crypto/chart/${currencyId}`);
            const response = await fetch(`${API_BASE_URL}/crypto/chart/${currencyId}`);
            console.log(`<<< Réponse: /api/crypto/chart/${currencyId} Status:`, response.status);
            if (!response.ok) throw new Error(`Erreur API (${response.status})`);
            const result = await response.json();
            console.log(`<<< Données: /api/crypto/chart/${currencyId}:`, result);

            if (result.success && result.data?.datasets?.[0]?.data) {
                const chartData = result.data;
                const ctx = modalChartCanvas.getContext('2d');
                modalChartInstance = new Chart(ctx, {
                    type: 'line', data: { labels: chartData.labels, datasets: [{ label: `Prix (${cryptoSymbol} - USD)`, data: chartData.datasets[0].data, borderColor: chartData.datasets[0].borderColor || '#0d6efd', backgroundColor: chartData.datasets[0].backgroundColor || 'rgba(13, 110, 253, 0.1)', tension: chartData.datasets[0].tension || 0.3, fill: chartData.datasets[0].fill !== undefined ? chartData.datasets[0].fill : true, pointRadius: 2, pointHoverRadius: 5 }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: false, ticks: { callback: value => formatUsd(value) } } }, plugins: { legend: { display: true }, tooltip: { callbacks: { label: context => context.parsed.y !== null ? formatUsd(context.parsed.y) : '' } } } }
                });
                modalChartLoadingEl.classList.add('d-none');
                modalChartContainerEl.classList.remove('d-none');
                console.log(`<<< Graphique modal rendu pour ID: ${currencyId}`);
            } else {
                throw new Error(result.message || 'Format de données invalide');
            }
        } catch (error) {
            console.error(`>>> ERREUR dans displayChartInModal pour ${currencyId}:`, error);
            modalChartLoadingEl.classList.add('d-none');
            modalChartErrorEl.textContent = `Erreur chargement graphique: ${error.message}`;
            modalChartErrorEl.classList.remove('d-none');
        }
    }

    // --- Main Fetch Function ---
    async function fetchDashboardData() {
        if(holdingsTableBody) holdingsTableBody.innerHTML = '<tr class="loading-placeholder"><td colspan="5">Chargement des actifs... <i class="fas fa-spinner fa-spin"></i></td></tr>';
        if(cryptoListTableBody) cryptoListTableBody.innerHTML = '<tr class="loading-placeholder"><td colspan="8">Chargement des cryptomonnaies... <i class="fas fa-spinner fa-spin"></i></td></tr>';
        if(accountTypeEl) accountTypeEl.textContent = 'Chargement...';
        if(accountStatusEl) { accountStatusEl.textContent = 'Chargement...'; accountStatusEl.className = 'badge account-status'; }
        if(accountLastLoginEl) accountLastLoginEl.textContent = 'Chargement...';
        if(balanceAmountEl) balanceAmountEl.textContent = 'Chargement...';
        if (portfolioTotalValueEl) portfolioTotalValueEl.textContent = 'Chargement...';
        if (portfolioCryptoValueEl) portfolioCryptoValueEl.textContent = '';
        if (portfolioChange24hEl) portfolioChange24hEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        if (weeklyGainEl) weeklyGainEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Chargement...';

        try {
            const [dashResponse, listResponse] = await Promise.all([
                fetch(`${API_BASE_URL}/dashboard/data`),
                fetch(`${API_BASE_URL}/crypto/list`)
            ]);

            // Process Dashboard Data
            if (!dashResponse.ok) console.error(`Dashboard API error! status: ${dashResponse.status}`);
            const resultDash = await dashResponse.json();
            if (resultDash.success && resultDash.data) {
                const data = resultDash.data;
                if (accountTypeEl) accountTypeEl.textContent = data.account.type;
                if (accountStatusEl) { accountStatusEl.textContent = data.account.status; accountStatusEl.className = `badge account-status ${data.account.status === 'Active' ? 'bg-success' : 'bg-warning'}`; }
                if (accountLastLoginEl) accountLastLoginEl.textContent = data.account.last_login;
                userBalanceRaw = data.account.balance_cad_raw;
                if (balanceAmountEl) balanceAmountEl.textContent = data.account.balance_cad_formatted;
                if (hiddenBalanceEl) hiddenBalanceEl.textContent = '******$ CAD';
                const portfolioChangeClass = getChangeClass(parseFloat(data.portfolio?.change_24h_percent_formatted));
                if (portfolioTotalValueEl) portfolioTotalValueEl.textContent = data.portfolio.total_value_cad_formatted;
                if (portfolioCryptoValueEl) portfolioCryptoValueEl.textContent = `Crypto: ${data.portfolio.crypto_value_cad_formatted}`;
                if (portfolioChange24hEl) { portfolioChange24hEl.innerHTML = `24h: <span class="${portfolioChangeClass}">${data.portfolio.change_24h_percent_formatted} (${data.portfolio.change_24h_cad_formatted}$)</span>`; }
                if (weeklyGainEl) weeklyGainEl.textContent = '';
                if (holdingsTableBody) renderHoldings(data.holdings);
                if (data.portfolioChart) renderPortfolioChart(data.portfolioChart);
            } else {
                console.error('Dashboard API data processing failed:', resultDash.message || 'Unknown error');
                if (accountTypeEl) accountTypeEl.textContent = 'Erreur';
                if (holdingsTableBody) holdingsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur chargement actifs.</td></tr>';
            }

            // Process Crypto List Data
            if (!listResponse.ok) console.error(`Crypto List API error! status: ${listResponse.status}`);
            const resultList = await listResponse.json();
            if (resultList.success && resultList.data) {
                if (cryptoListTableBody) renderCryptoList(resultList.data); // Appel qui génère les boutons
            } else {
                console.error('Crypto List API data processing failed:', resultList.message || 'Unknown error');
                if (cryptoListTableBody) cryptoListTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur chargement cryptos.</td></tr>';
            }

        } catch (error) {
            console.error('Error fetching dashboard data:', error);
            if (holdingsTableBody?.querySelector('.loading-placeholder')) holdingsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur chargement actifs.</td></tr>';
            if (cryptoListTableBody?.querySelector('.loading-placeholder')) cryptoListTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur chargement cryptos.</td></tr>';
            if (accountTypeEl?.textContent === 'Chargement...') accountTypeEl.textContent = 'Erreur';
        }
    }

    // --- Event Listeners ---
    if (toggleBalanceBtn && balanceAmountEl && hiddenBalanceEl) {
        toggleBalanceBtn.addEventListener('click', () => {
            balanceVisible = !balanceVisible;
            balanceAmountEl.classList.toggle('d-none', !balanceVisible);
            hiddenBalanceEl.classList.toggle('d-none', balanceVisible);
            const icon = toggleBalanceBtn.querySelector('i');
            icon.className = balanceVisible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        });
    }

    // Listener for Crypto Chart Modal Initialization <<< CELUI QUI EST IMPORTANT ICI
    if (cryptoChartModalEl) {
        console.log(">>> Initialisation de l'écouteur pour le modal #cryptoChartModal");
        cryptoChartModalEl.addEventListener('show.bs.modal', function (event) {
            console.log(">>> Événement show.bs.modal déclenché");
            const button = event.relatedTarget;
            if (button && button.hasAttribute('data-crypto-id')) {
                console.log(">>> Bouton déclencheur trouvé:", button);
                const cryptoId = button.getAttribute('data-crypto-id');
                const cryptoName = button.getAttribute('data-crypto-name');
                const cryptoSymbol = button.getAttribute('data-crypto-symbol');
                console.log(`>>> Appel displayChartInModal avec ID: ${cryptoId}`);
                displayChartInModal(cryptoId, cryptoName, cryptoSymbol);
            } else {
                console.warn(">>> Événement show.bs.modal déclenché sans bouton valide ou attributs data.");
            }
        });

        cryptoChartModalEl.addEventListener('hidden.bs.modal', function () {
            console.log(">>> Événement hidden.bs.modal déclenché");
            if (modalChartInstance) modalChartInstance.destroy();
            modalChartInstance = null;
            if(modalChartTitleEl) modalChartTitleEl.textContent = 'Graphique : Chargement...';
            if(modalChartLoadingEl) modalChartLoadingEl.classList.remove('d-none');
            if(modalChartContainerEl) modalChartContainerEl.classList.add('d-none');
            if(modalChartErrorEl) modalChartErrorEl.classList.add('d-none');
        });
    } else {
         console.error(">>> Élément modal #cryptoChartModal non trouvé ! Vérifiez l'ID dans dashboard.php.");
    }

    // Listener for buy/sell buttons
    [buyButton, sellButton].forEach(button => {
        if(button) {
            button.addEventListener('click', function() {
                if (!selectedCryptoForTrade || !confirmationModal || !amountInput || !quantityInput) {
                    console.error("Éléments manquants pour la transaction.");
                    return;
                }

                const action = button.id === 'buyButton' ? 'Acheter' : 'Vendre';
                const quantity = parseFloat(quantityInput.value);
                const amountUsd = parseFloat(amountInput.value);
                const priceUsd = selectedCryptoForTrade.priceUsd;
                const amountCAD = amountUsd * USD_TO_CAD_RATE; // Use calculated USD amount * rate

                // --- Basic Client-Side Validation ---
                if (isNaN(quantity) || quantity <= 0 || isNaN(amountCAD) || amountCAD <= 0) {
                    if (tradeInfoEl && tradeInfoTextEl) {
                        tradeInfoTextEl.textContent = "Veuillez entrer un montant ou une quantité valide.";
                        tradeInfoEl.className = 'alert alert-warning mt-3'; // Show warning
                    }
                    return;
                }

                // Check sufficient CAD balance for BUY
                if (action === 'Acheter' && userBalanceRaw < amountCAD) {
                    if (tradeInfoEl && tradeInfoTextEl) {
                        tradeInfoTextEl.textContent = `Solde insuffisant. Vous avez ${formatCurrency(userBalanceRaw)}, besoin de ${formatCurrency(amountCAD)}.`;
                        tradeInfoEl.className = 'alert alert-danger mt-3'; // Show error
                    }
                    return;
                }

                 // TODO: Add client-side check for sufficient crypto balance for SELL
                 // This requires fetching the user's specific holding for the selected crypto.
                 // For now, we rely on the server-side validation for selling.
                 if (action === 'Vendre') {
                      console.warn("Validation côté client de la quantité détenue pour la vente non implémentée.");
                 }

                // --- Populate and Show Confirmation Modal ---
                if (confirmActionEl) confirmActionEl.textContent = action;
                if (confirmCryptoNameEl) confirmCryptoNameEl.textContent = selectedCryptoForTrade.name;
                if (confirmCryptoSymbolEl) confirmCryptoSymbolEl.textContent = selectedCryptoForTrade.symbol;
                if (confirmQuantityEl) confirmQuantityEl.textContent = quantity.toFixed(8);
                if (confirmPriceUsdEl) confirmPriceUsdEl.textContent = formatUsd(priceUsd);
                if (confirmAmountCadEl) confirmAmountCadEl.textContent = formatCurrency(amountCAD);
                if (confirmErrorEl) confirmErrorEl.classList.add('d-none'); // Clear previous errors

                // Store details for the confirmation button
                if(confirmTransactionBtn) {
                    confirmTransactionBtn.dataset.action = action;
                    confirmTransactionBtn.dataset.currencyId = selectedCryptoForTrade.id;
                    confirmTransactionBtn.dataset.quantity = quantity;
                    confirmTransactionBtn.dataset.amountCad = amountCAD; // Store CAD value
                }

                confirmationModal.show();
            });
        }
    });

    // Listener for the final confirmation button in the modal
    if (confirmTransactionBtn) {
        confirmTransactionBtn.addEventListener('click', function() {
            const details = {
                action: this.dataset.action,
                currencyId: parseInt(this.dataset.currencyId, 10),
                quantity: parseFloat(this.dataset.quantity),
                amountCAD: parseFloat(this.dataset.amountCad)
            };
            if (!details.action || isNaN(details.currencyId) || isNaN(details.quantity) || isNaN(details.amountCAD)) {
                console.error("Données de confirmation invalides", details);
                if(confirmErrorEl) {
                    confirmErrorEl.textContent = "Erreur interne : Données de confirmation invalides.";
                    confirmErrorEl.classList.remove('d-none');
                }
                return;
            }
            executeTransaction(details);
        });
    }

    // Clear modal errors when hidden
    if (confirmationModalEl) {
        confirmationModalEl.addEventListener('hidden.bs.modal', function () {
            if(confirmErrorEl) confirmErrorEl.classList.add('d-none');
            if(confirmTransactionBtn) {
                 confirmTransactionBtn.disabled = false;
                 confirmTransactionBtn.innerHTML = 'Confirmer'; // Reset button text
            }
        });
    }

    // --- Event Listeners for Trade Form Inputs --- NEW
    if (amountInput) {
        amountInput.addEventListener('input', () => updateTradeForm('amount'));
    }
    if (quantityInput) {
        quantityInput.addEventListener('input', () => updateTradeForm('quantity'));
    }

    // --- Initial Load ---
    fetchDashboardData();
});