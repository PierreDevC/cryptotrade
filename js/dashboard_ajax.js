// /cryptotrade/js/dashboard_ajax.js

document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS Loaded");

    // --- Globals / State ---
    let cryptoDetailChartInstance = null;
    let cryptoPortfolioChartInstance = null;
    const usdToCadRate = 1.35; // Get from config via JS variable if possible, else hardcode/fetch

    // --- DOM Element References ---
    const balanceAmountEl = document.getElementById('balanceAmount');
    const hiddenBalanceEl = document.getElementById('hiddenBalance');
    const toggleBalanceBtn = document.getElementById('toggleBalance');
    const accountTypeEl = document.querySelector('.col-md-6 p:nth-child(1) strong'); // Adjust selector if needed
    const accountStatusEl = document.querySelector('.badge.bg-success'); // Adjust selector
    const lastLoginEl = document.querySelector('.col-md-6 p:nth-child(3)'); // Adjust selector
    const weeklyGainEl = document.querySelector('.text-success.mt-2'); // Adjust selector
    const holdingsTableBody = document.querySelector('#section-5 .crypto-table tbody'); // Holdings table
    const cryptoTableBody = document.querySelector('.col-md-6 .crypto-table tbody'); // Crypto list table
    const selectedCryptoNameFormEl = document.getElementById('selectedCryptoName');
    const selectedCryptoPriceFormEl = document.getElementById('selectedCryptoPrice');
    const cryptoAmountInput = document.getElementById('cryptoAmount');
    const cryptoQuantityInput = document.getElementById('cryptoQuantity');
    const buyButton = document.getElementById('buyButton');
    const sellButton = document.getElementById('sellButton');
    const tradeInfoAlert = document.getElementById('tradeInfo');
    const tradeInfoText = document.getElementById('tradeInfoText');

    // Confirmation Modal Elements
    const orderTypeEl = document.getElementById('orderType');
    const orderCryptoNameEl = document.getElementById('cryptoName');
    const orderQuantityEl = document.getElementById('orderQuantity');
    const orderPriceEl = document.getElementById('orderPrice');
    const orderTotalEl = document.getElementById('orderTotal');
    const confirmOrderBtn = document.getElementById('confirmOrderBtn');
    const confirmationModalEl = document.getElementById('confirmationModal');
    const confirmationModal = new bootstrap.Modal(confirmationModalEl); // Init Bootstrap modal instance


    // Crypto Detail Modal Elements
    const cryptoChartModalEl = document.getElementById('cryptoChartModal');
    const modalCryptoNameEl = cryptoChartModalEl.querySelector('#selectedCryptoName'); // Use specific ID from modal
    const modalCryptoPriceEl = cryptoChartModalEl.querySelector('#selectedCryptoPrice'); // Use specific ID
    const modalCryptoChangeEl = cryptoChartModalEl.querySelector('#selectedCryptoChange'); // Use specific ID
    const modalCryptoMarketCapEl = cryptoChartModalEl.querySelector('#selectedCryptoMarketCap'); // Use specific ID


    // --- Initialization ---
    fetchDashboardData();
    fetchCryptoList();
    setupEventListeners();
    // Initial state for buy/sell buttons
    buyButton.disabled = true;
    sellButton.disabled = true;


    // --- Fetch Functions ---
    async function fetchDashboardData() {
        console.log("Fetching dashboard data...");
        try {
            const response = await fetch('/cryptotrade/api/dashboard/data'); // Adjust path if needed
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            console.log("Dashboard Data Received:", result);

            if (result.success && result.data) {
                updateDashboardUI(result.data);
                initializePortfolioChart(result.data.portfolioChart);
            } else {
                console.error("Failed to load dashboard data:", result.message);
                // Display error to user?
            }
        } catch (error) {
            console.error("Error fetching dashboard data:", error);
            // Display error to user?
        }
    }

    async function fetchCryptoList() {
        console.log("Fetching crypto list...");
        try {
            const response = await fetch('/cryptotrade/api/crypto/list'); // Adjust path if needed
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
             console.log("Crypto List Received:", result);

            if (result.success && result.data) {
                populateCryptoTable(result.data);
            } else {
                console.error("Failed to load crypto list:", result.message);
            }
        } catch (error) {
            console.error("Error fetching crypto list:", error);
        }
    }

    async function fetchCryptoChartData(currencyId) {
        console.log(`Fetching chart data for currency ID: ${currencyId}`);
         try {
            const response = await fetch(`/cryptotrade/api/crypto/chart/${currencyId}`); // Adjust path
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
             console.log("Chart Data Received:", result);
            if (result.success && result.data) {
                return result.data; // Return the chart data structure
            } else {
                console.error("Failed to load chart data:", result.message);
                 alert('Failed to load chart data: ' + result.message);
                 return null;
            }
        } catch (error) {
            console.error("Error fetching chart data:", error);
             alert('Error loading chart data.');
             return null;
        }
    }


    // --- UI Update Functions ---
    function updateDashboardUI(data) {
        // Account Details
         if (data.account) {
            balanceAmountEl.textContent = data.account.balance_cad_formatted;
            hiddenBalanceEl.textContent = '******$ CAD'; // Keep hidden format
            accountTypeEl.textContent = data.account.type;
             accountStatusEl.textContent = data.account.status;
             lastLoginEl.textContent = `Dernière connexion: ${data.account.last_login}`;
             weeklyGainEl.innerHTML = `<i class="fa-solid fa-arrow-trend-up me-1"></i> +${data.account.weekly_gain_percent}% cette semaine`; // Assumes positive gain
         }

         // Holdings Table
        if (data.holdings && holdingsTableBody) {
            holdingsTableBody.innerHTML = ''; // Clear existing rows
            data.holdings.forEach(holding => {
                const row = document.createElement('tr');
                 const profitLossClass = holding.profit_loss_percent >= 0 ? 'text-success' : 'text-danger';
                row.innerHTML = `
                    <td><img src="${holding.image_url}" width="20" class="me-2">${holding.name} (${holding.symbol})</td>
                    <td>${holding.quantity}</td>
                    <td>$${holding.current_price_cad}</td>
                    <td>$${holding.total_value_cad}</td>
                     <td class="${profitLossClass}">${holding.profit_loss_percent >= 0 ? '+' : ''}${holding.profit_loss_percent.toFixed(2)}%</td>
                `;
                holdingsTableBody.appendChild(row);
            });
         } else if (holdingsTableBody) {
             holdingsTableBody.innerHTML = '<tr><td colspan="5">No holdings found.</td></tr>';
         }
    }

    function populateCryptoTable(cryptoList) {
        if (!cryptoTableBody) return;
         cryptoTableBody.innerHTML = ''; // Clear existing
         cryptoList.forEach(crypto => {
            const row = document.createElement('tr');
            // Store necessary data in data attributes for later use
            row.dataset.cryptoId = crypto.id;
            row.dataset.cryptoSymbol = crypto.symbol;
             row.dataset.cryptoName = crypto.name;
             row.dataset.priceUsd = crypto.price_usd_raw;
             row.dataset.change24h = crypto.change_24h_raw;
             row.dataset.marketCap = crypto.market_cap_raw;
             row.dataset.marketCapFormatted = crypto.market_cap;
             row.dataset.change24hFormatted = crypto.change_24h;
             row.dataset.priceUsdFormatted = crypto.price_usd;


             const changeClass = crypto.change_24h_raw >= 0 ? 'text-success' : 'text-danger';

            row.innerHTML = `
                <td>
                    <div class="form-check">
                        <input class="form-check-input crypto-select" type="checkbox" value="${crypto.id}" id="select${crypto.symbol}">
                    </div>
                </td>
                <th scope="row">${crypto.rank}</th>
                <td><img src="${crypto.image_url}" width="20" class="me-2">${crypto.name} (${crypto.symbol})</td>
                <td>$${crypto.price_usd}</td>
                <td class="${changeClass}">${crypto.change_24h}</td>
                <td>${crypto.market_cap}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary show-chart-btn">
                        <i class="fa-solid fa-chart-line me-1"></i>Voir graphique
                    </button>
                </td>
            `;
            cryptoTableBody.appendChild(row);
         });

          // Re-attach event listeners to newly added buttons/checkboxes
         attachCryptoTableListeners();
    }

     function attachCryptoTableListeners() {
         document.querySelectorAll('.crypto-select').forEach(checkbox => {
             checkbox.removeEventListener('change', handleCryptoSelectChange); // Prevent duplicate listeners
             checkbox.addEventListener('change', handleCryptoSelectChange);
         });
         document.querySelectorAll('.show-chart-btn').forEach(button => {
             button.removeEventListener('click', handleShowChartClick); // Prevent duplicate listeners
             button.addEventListener('click', handleShowChartClick);
         });
     }


    // --- Chart Initialization ---
     function initializePortfolioChart(chartData) {
         if (!chartData || !chartData.labels || !chartData.values) {
             console.warn("Invalid portfolio chart data received.");
             return;
         }
         const ctx = document.getElementById('cryptoChart');
         if (!ctx) return;

         if (cryptoPortfolioChartInstance) {
             cryptoPortfolioChartInstance.destroy(); // Destroy previous instance if exists
         }

         cryptoPortfolioChartInstance = new Chart(ctx.getContext('2d'), {
             type: 'line',
             data: {
                 labels: chartData.labels, // Use labels from API
                 datasets: [{
                     label: 'Valeur du Portefeuille (CAD)',
                     data: chartData.values, // Use values from API
                     borderColor: '#2d3a36',
                     backgroundColor: 'rgba(45, 58, 54, 0.1)',
                     tension: 0.3,
                     fill: true
                 }]
             },
             options: {
                 responsive: true,
                 maintainAspectRatio: false, // Important for sizing in container
                 scales: {
                     y: {
                         beginAtZero: false, // Don't force start at 0
                         title: { display: true, text: 'Valeur (CAD)' }
                     },
                     x: { title: { display: true, text: 'Période' } } // Use generic period label
                 },
                 plugins: {
                     title: { display: true, text: 'Performance Estimée du Portefeuille' }, // Updated title
                      tooltip: {
                         callbacks: {
                             label: function(context) {
                                 let label = context.dataset.label || '';
                                 if (label) { label += ': '; }
                                 if (context.parsed.y !== null) {
                                     label += new Intl.NumberFormat('en-CA', { style: 'currency', currency: 'CAD' }).format(context.parsed.y);
                                 }
                                 return label;
                             }
                         }
                     }
                 }
             }
         });
     }

     function initializeDetailChart(chartConfig) {
          const ctx = document.getElementById('cryptoDetailChart');
          if (!ctx) {
              console.error("Detail chart canvas not found");
              return;
          }

          if (cryptoDetailChartInstance) {
              cryptoDetailChartInstance.destroy();
          }

           cryptoDetailChartInstance = new Chart(ctx.getContext('2d'), {
               type: 'line',
               data: chartConfig, // Use the config structure from API
               options: {
                   responsive: true,
                   maintainAspectRatio: false,
                   scales: {
                       y: {
                           beginAtZero: false,
                           title: { display: true, text: 'Prix (USD)' }
                       },
                       x: { title: { display: true, text: 'Période' } }
                   },
                   plugins: {
                       title: {
                           display: true,
                           // Title is set dynamically when data is fetched
                            text: `Historique des prix`
                       },
                        tooltip: {
                         callbacks: {
                             label: function(context) {
                                 let label = context.dataset.label || '';
                                 if (label) { label += ': '; }
                                 if (context.parsed.y !== null) {
                                     label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                 }
                                 return label;
                             }
                         }
                     }
                   }
               }
           });
            // Update chart title dynamically if needed
           if (cryptoDetailChartInstance && chartConfig.datasets[0]?.label) {
               const cryptoName = chartConfig.datasets[0].label.split('(')[0].trim(); // Extract name
               cryptoDetailChartInstance.options.plugins.title.text = `Historique des prix de ${cryptoName}`;
               cryptoDetailChartInstance.update();
           }
     }

    // --- Event Handlers ---
     function setupEventListeners() {
         // Balance Toggle
        if (toggleBalanceBtn) {
            toggleBalanceBtn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (balanceAmountEl.classList.contains('d-none')) {
                    balanceAmountEl.classList.remove('d-none');
                    hiddenBalanceEl.classList.add('d-none');
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    balanceAmountEl.classList.add('d-none');
                    hiddenBalanceEl.classList.remove('d-none');
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }

         // Amount/Quantity Input Synchronization
         if (cryptoAmountInput) {
             cryptoAmountInput.addEventListener('input', handleAmountInputChange);
         }
         if (cryptoQuantityInput) {
             cryptoQuantityInput.addEventListener('input', handleQuantityInputChange);
         }

         // Buy/Sell Button Clicks -> Open Confirmation Modal
         if (buyButton) {
            buyButton.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default form submission
                prepareConfirmationModal('buy');
            });
        }
         if (sellButton) {
            sellButton.addEventListener('click', function(event) {
                event.preventDefault();
                prepareConfirmationModal('sell');
            });
         }

          // Confirm Order Button Click (inside modal)
         if (confirmOrderBtn) {
            confirmOrderBtn.addEventListener('click', handleConfirmOrder);
         }

         // Clean up chart instance when detail modal is closed
         if (cryptoChartModalEl) {
             cryptoChartModalEl.addEventListener('hidden.bs.modal', function() {
                 if (cryptoDetailChartInstance) {
                     cryptoDetailChartInstance.destroy();
                     cryptoDetailChartInstance = null;
                     console.log("Detail chart destroyed");
                 }
             });
         }
     }

     // Attached dynamically after table population
     function handleCryptoSelectChange() {
         // Uncheck others
         document.querySelectorAll('.crypto-select').forEach(cb => {
             if (cb !== this) cb.checked = false;
         });

         const row = this.closest('tr');
         if (this.checked && row) {
             buyButton.disabled = false;
             sellButton.disabled = false;

             const cryptoName = row.dataset.cryptoName;
             const cryptoSymbol = row.dataset.cryptoSymbol;
             const cryptoPrice = parseFloat(row.dataset.priceUsd); // Use raw numeric value
             const currencyId = row.dataset.cryptoId; // Store the ID

             // Store data for later use (e.g., in confirmation modal)
             buyButton.dataset.selectedCurrencyId = currencyId;
             sellButton.dataset.selectedCurrencyId = currencyId;
             buyButton.dataset.selectedCryptoPriceUsd = cryptoPrice;
             sellButton.dataset.selectedCryptoPriceUsd = cryptoPrice;
             buyButton.dataset.selectedCryptoSymbol = cryptoSymbol;
             sellButton.dataset.selectedCryptoSymbol = cryptoSymbol;
             buyButton.dataset.selectedCryptoName = cryptoName;
             sellButton.dataset.selectedCryptoName = cryptoName;


             selectedCryptoNameFormEl.textContent = `${cryptoName} (${cryptoSymbol})`;
             selectedCryptoPriceFormEl.textContent = `Prix actuel: $${cryptoPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 5 })}`;

             // Reset inputs and recalculate if needed
              handleAmountInputChange(); // Trigger recalculation based on amount (if any)

         } else {
             resetTradeForm();
         }
     }

      // Attached dynamically
     async function handleShowChartClick(e) {
         e.preventDefault();
         e.stopPropagation();

         const row = this.closest('tr');
         if (!row) return;

         const currencyId = row.dataset.cryptoId;
         const cryptoName = row.dataset.cryptoName;
         const displayPrice = `$${row.dataset.priceUsdFormatted}`;
         const changeFormatted = row.dataset.change24hFormatted;
         const marketCapFormatted = row.dataset.marketCapFormatted;
         const changeRaw = parseFloat(row.dataset.change24h);


         if (!currencyId || !cryptoName) {
            console.error('Missing data for chart modal', row.dataset);
             alert('Could not load details for this crypto.');
            return;
         }

         // Update modal text content *before* fetching chart data
         modalCryptoNameEl.textContent = cryptoName;
         modalCryptoPriceEl.textContent = displayPrice;
         modalCryptoChangeEl.textContent = changeFormatted;
         modalCryptoChangeEl.className = changeRaw >= 0 ? 'text-success' : 'text-danger'; // Set class based on sign
         modalCryptoMarketCapEl.textContent = marketCapFormatted;

         // Fetch chart data
         const chartConfig = await fetchCryptoChartData(currencyId);

         if (chartConfig) {
              initializeDetailChart(chartConfig); // Initialize chart with fetched data
               // Show the modal
              const modal = bootstrap.Modal.getInstance(cryptoChartModalEl) || new bootstrap.Modal(cryptoChartModalEl);
              modal.show();
         }
     }


     function handleAmountInputChange() {
        const amount = parseFloat(cryptoAmountInput.value);
        const selectedPrice = parseFloat(buyButton.dataset.selectedCryptoPriceUsd); // Use stored price

         if (!isNaN(amount) && amount > 0 && selectedPrice > 0) {
            const quantity = amount / selectedPrice;
             cryptoQuantityInput.value = quantity.toFixed(8); // Adjust precision as needed
         } else if (amount <= 0) {
             cryptoQuantityInput.value = '';
         }
     }

     function handleQuantityInputChange() {
         const quantity = parseFloat(cryptoQuantityInput.value);
         const selectedPrice = parseFloat(buyButton.dataset.selectedCryptoPriceUsd); // Use stored price

         if (!isNaN(quantity) && quantity > 0 && selectedPrice > 0) {
             const amount = quantity * selectedPrice;
             cryptoAmountInput.value = amount.toFixed(2); // Standard currency precision
         } else if (quantity <= 0) {
              cryptoAmountInput.value = '';
         }
     }

     function resetTradeForm() {
         selectedCryptoNameFormEl.textContent = 'Sélectionnez une cryptomonnaie dans le tableau ci-dessous';
         selectedCryptoPriceFormEl.textContent = 'Prix actuel: --';
         cryptoAmountInput.value = '';
         cryptoQuantityInput.value = '';
         buyButton.disabled = true;
         sellButton.disabled = true;
          // Clear stored data
         delete buyButton.dataset.selectedCurrencyId;
         delete sellButton.dataset.selectedCurrencyId;
         delete buyButton.dataset.selectedCryptoPriceUsd;
         delete sellButton.dataset.selectedCryptoPriceUsd;
         delete buyButton.dataset.selectedCryptoSymbol;
         delete sellButton.dataset.selectedCryptoSymbol;
         delete buyButton.dataset.selectedCryptoName;
         delete sellButton.dataset.selectedCryptoName;
     }


     function prepareConfirmationModal(type) { // 'buy' or 'sell'
        const amountInputVal = cryptoAmountInput.value;
        const quantityInputVal = cryptoQuantityInput.value;
        const buttonRef = (type === 'buy') ? buyButton : sellButton;

        const currencyId = buttonRef.dataset.selectedCurrencyId;
        const cryptoName = buttonRef.dataset.selectedCryptoName;
        const cryptoSymbol = buttonRef.dataset.selectedCryptoSymbol;
        const currentPriceUSD = parseFloat(buttonRef.dataset.selectedCryptoPriceUsd);

         if (!currencyId || !cryptoName || !cryptoSymbol || isNaN(currentPriceUSD)) {
             alert('Please select a cryptocurrency from the table first.');
             return;
         }
        if (!amountInputVal || !quantityInputVal || parseFloat(amountInputVal) <= 0 || parseFloat(quantityInputVal) <= 0) {
             alert('Please enter a valid amount and quantity.');
             return;
         }

         const quantity = parseFloat(quantityInputVal);
         const amountUSD = parseFloat(amountInputVal); // The amount entered is USD based on calculation
         const amountCAD = amountUSD * usdToCadRate; // Convert to CAD for display/backend

         // Populate Modal
         orderTypeEl.textContent = type === 'buy' ? 'Achat' : 'Vente';
         orderTypeEl.className = `col-6 ${type === 'buy' ? 'text-success' : 'text-danger'}`;
         orderCryptoNameEl.textContent = `${cryptoName} (${cryptoSymbol})`;
         orderQuantityEl.textContent = `${quantity.toFixed(8)} ${cryptoSymbol}`; // Show crypto quantity
         orderPriceEl.textContent = `$${currentPriceUSD.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 5 })} USD / ${cryptoSymbol}`; // Price per unit
         orderTotalEl.textContent = `$${amountCAD.toLocaleString('en-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} CAD`; // Total cost/proceeds in CAD

         // Store data needed for submission on the confirm button
         confirmOrderBtn.dataset.orderType = type;
         confirmOrderBtn.dataset.currencyId = currencyId;
         confirmOrderBtn.dataset.quantity = quantity; // Use the exact quantity
         confirmOrderBtn.dataset.amountCad = amountCAD; // Use the calculated CAD amount


         // Open the modal
         confirmationModal.show();
     }

     async function handleConfirmOrder() {
        const type = confirmOrderBtn.dataset.orderType;
        const currencyId = confirmOrderBtn.dataset.currencyId;
        const quantity = confirmOrderBtn.dataset.quantity;
        const amountCAD = confirmOrderBtn.dataset.amountCad;
        const cryptoName = orderCryptoNameEl.textContent; // Get from modal display


         if (!type || !currencyId || !quantity || !amountCAD) {
            console.error("Missing order details for confirmation.");
             showTradeFeedback('error', 'Erreur: Détails de la commande manquants.');
            return;
         }

         // Disable button to prevent double clicks
         confirmOrderBtn.disabled = true;
         confirmOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';


        const apiUrl = `/cryptotrade/api/transaction/${type}`; // 'buy' or 'sell'
        const payload = {
            currencyId: parseInt(currencyId),
            quantity: parseFloat(quantity),
            amountCAD: parseFloat(amountCAD)
             // Backend will use current price, amountCAD is for verification/logging
        };

         try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showTradeFeedback('success', `${type === 'buy' ? 'Achat' : 'Vente'} de ${parseFloat(quantity).toFixed(6)} ${cryptoName} pour ${parseFloat(amountCAD).toFixed(2)}$ CAD réussi!`);
                confirmationModal.hide();
                resetTradeForm(); // Clear the form
                 fetchDashboardData(); // Refresh dashboard data (holdings, balance)
                 // Optionally refresh crypto list if prices might have updated significantly (less likely needed)
                 // fetchCryptoList();
            } else {
                throw new Error(result.message || `HTTP error! status: ${response.status}`);
            }

        } catch (error) {
             console.error(`Error during ${type} transaction:`, error);
             showTradeFeedback('error', `Échec de la transaction: ${error.message}`);
             // Keep modal open? Or hide and show error? Hiding might be better UX.
             // confirmationModal.hide();
         } finally {
             // Re-enable button
              confirmOrderBtn.disabled = false;
              confirmOrderBtn.textContent = 'Confirmer la commande';
         }
     }

    function showTradeFeedback(type, message) {
        tradeInfoText.textContent = message;
        tradeInfoAlert.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-danger');

        if (type === 'success') {
             tradeInfoAlert.classList.add('alert-success');
        } else if (type === 'error') {
             tradeInfoAlert.classList.add('alert-danger');
        } else {
             tradeInfoAlert.classList.add('alert-info'); // Default/processing
        }

         // Optional: Hide after a few seconds
         setTimeout(() => {
            if (type === 'success') { // Only auto-hide success messages
                 tradeInfoAlert.classList.add('d-none');
             }
         }, 5000);
    }

}); // End DOMContentLoaded