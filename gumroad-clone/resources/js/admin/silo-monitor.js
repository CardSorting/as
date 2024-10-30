function viewTransactions(siloId) {
    const modal = document.getElementById('transaction-modal');
    const content = document.getElementById('transaction-content');
    
    // Show loading state
    content.innerHTML = '<div class="flex justify-center items-center h-64"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div></div>';
    modal.classList.remove('hidden');

    // Fetch transactions
    fetch(`/admin/silos/${siloId}/transactions`)
        .then(response => response.json())
        .then(data => {
            content.innerHTML = `
                <div class="mb-4">
                    <h4 class="text-lg font-semibold">${data.silo.store_domain}</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${data.transactions.data.map(transaction => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">${new Date(transaction.transaction_date).toLocaleDateString()}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-mono">${transaction.transaction_id}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">$${parseFloat(transaction.amount).toFixed(2)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        ${transaction.is_paid 
                                            ? `<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Paid</span>`
                                            : `<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Pending</span>`
                                        }
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <button onclick="exportSiloData(${siloId})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Export Data
                    </button>
                    <div class="text-sm text-gray-600">
                        Showing ${data.transactions.from} to ${data.transactions.to} of ${data.transactions.total} transactions
                    </div>
                </div>
            `;
        })
        .catch(error => {
            content.innerHTML = `<div class="text-red-600">Error loading transactions: ${error.message}</div>`;
        });
}

function closeModal() {
    const modal = document.getElementById('transaction-modal');
    modal.classList.add('hidden');
}

function exportSiloData(siloId) {
    window.location.href = `/admin/silos/${siloId}/export`;
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('transaction-modal');
    const modalContent = modal.querySelector('div');
    if (event.target === modal) {
        closeModal();
    }
});
