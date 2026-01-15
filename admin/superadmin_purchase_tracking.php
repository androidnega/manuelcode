<?php
// Super Admin - Purchase Tracking Page
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase Tracking - Super Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-blue-800 bg-clip-text text-transparent">
                        <i class="fas fa-search text-blue-600 mr-3"></i>Purchase Tracking
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">Track and search for purchases</p>
                </div>
                <a href="../dashboard/superadmin" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-6 py-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-4xl mx-auto">
            <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                <i class="fas fa-search text-blue-600 mr-3"></i>Track Purchase
            </h2>
            <div class="space-y-4">
                <!-- Search Form -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <form id="purchaseSearchForm" class="space-y-3">
                        <div>
                            <label for="searchType" class="block text-sm font-medium text-gray-700 mb-1">Search Type</label>
                            <select id="searchType" name="searchType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="order_id">Order ID</option>
                                <option value="user_id">User ID</option>
                                <option value="email">User Email</option>
                            </select>
                        </div>
                        <div>
                            <label for="searchValue" class="block text-sm font-medium text-gray-700 mb-1">Search Value</label>
                            <input type="text" id="searchValue" name="searchValue" placeholder="Enter order ID, user ID, or email" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Search Purchases
                        </button>
                    </form>
                </div>

                <!-- Search Results -->
                <div id="searchResults" class="hidden">
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-list text-green-600 mr-2"></i>
                                Search Results
                            </h3>
                        </div>
                        <div id="resultsContent" class="p-4">
                            <!-- Results will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('purchaseSearchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const searchType = document.getElementById('searchType').value;
    const searchValue = document.getElementById('searchValue').value;
    const resultsDiv = document.getElementById('searchResults');
    const contentDiv = document.getElementById('resultsContent');
    
    contentDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><p class="mt-2 text-gray-600">Searching...</p></div>';
    resultsDiv.classList.remove('hidden');
    
    try {
        const response = await fetch('superadmin_tools.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=search_purchase&search_type=${encodeURIComponent(searchType)}&search_value=${encodeURIComponent(searchValue)}`
        });
        
        const data = await response.json();
        
        if (data.success && data.purchases && data.purchases.length > 0) {
            let html = '<div class="space-y-4">';
            data.purchases.forEach(purchase => {
                html += `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Order ID</p>
                                <p class="font-semibold">${purchase.id || purchase.order_id || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Status</p>
                                <p class="font-semibold text-green-600">${purchase.status || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Amount</p>
                                <p class="font-semibold">GHS ${purchase.amount || purchase.price || '0.00'}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Date</p>
                                <p class="font-semibold">${purchase.created_at || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = '<div class="text-center py-8 text-gray-600">No purchases found.</div>';
        }
    } catch (error) {
        contentDiv.innerHTML = '<div class="text-center py-8 text-red-600">Error searching purchases: ' + error.message + '</div>';
    }
});
</script>
</body>
</html>

