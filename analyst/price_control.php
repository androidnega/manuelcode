<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$analyst_id = $_SESSION['analyst_id'];
$analyst_name = $_SESSION['analyst_name'];

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $new_price = floatval($_POST['submission_price']);
    
    if ($new_price >= 0.01 && $new_price <= 1000) {
        try {
            // Update setting in database
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()");
            $stmt->execute(['submission_price', $new_price]);
            
            $success_message = "Price updated successfully to GHS " . number_format($new_price, 2);
            
            // Log the price change
            $stmt = $pdo->prepare("
                INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                VALUES (?, 'update_price', ?, ?, ?, NOW())
            ");
            $details = json_encode(['old_price' => $_POST['old_price'], 'new_price' => $new_price]);
            $stmt->execute([$analyst_id, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            
        } catch (Exception $e) {
            $error_message = "Failed to update price: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid price. Please enter a value between 0.01 and 1000 GHS.";
    }
}

// Get current price
try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
    $stmt->execute(['submission_price']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_price = $result ? floatval($result['value']) : 0.01;
} catch (Exception $e) {
    $current_price = 0.01;
    $error_message = "Failed to load current price: " . $e->getMessage();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Control - Analyst Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 10px;
            border-radius: 5px;
            background: #d1d5db;
            outline: none;
        }
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: #3b82f6;
            cursor: pointer;
        }
        .slider::-moz-range-thumb {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: #3b82f6;
            cursor: pointer;
            border: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/dashboard" class="text-xl font-semibold text-gray-900 mr-6">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <h1 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-dollar-sign mr-2 text-green-600"></i>Price Control
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-700">
                            Welcome, <span class="font-medium"><?php echo htmlspecialchars($analyst_name); ?></span>
                        </div>
                        <a href="?logout=1" 
                           class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors text-sm">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Price Control Card -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-sliders-h mr-2 text-blue-600"></i>Submission Price Control
                    </h2>
                    <p class="text-gray-600">Adjust the price that students pay for project submissions.</p>
                </div>

                <!-- Current Price Display -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-blue-900">Current Price</h3>
                            <p class="text-2xl font-bold text-blue-600" id="current-price-display">
                                GHS <?php echo number_format($current_price, 2); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-blue-600">Price per submission</div>
                            <div class="text-xs text-blue-500">Updated immediately</div>
                        </div>
                    </div>
                </div>

                <!-- Price Slider Form -->
                <form method="POST" id="price-form">
                    <input type="hidden" name="old_price" value="<?php echo $current_price; ?>">
                    
                    <div class="mb-6">
                        <label for="price-slider" class="block text-sm font-medium text-gray-700 mb-3">
                            Adjust Price: <span id="price-display">GHS <?php echo number_format($current_price, 2); ?></span>
                        </label>
                        
                        <div class="relative">
                            <input type="range" 
                                   id="price-slider" 
                                   name="submission_price" 
                                   min="0.01" 
                                   max="100" 
                                   step="0.01" 
                                   value="<?php echo $current_price; ?>" 
                                   class="slider mb-4">
                            
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>GHS 0.01</span>
                                <span>GHS 25.00</span>
                                <span>GHS 50.00</span>
                                <span>GHS 100.00</span>
                            </div>
                        </div>

                        <!-- Quick Price Buttons -->
                        <div class="grid grid-cols-4 gap-2 mt-4">
                            <button type="button" class="quick-price-btn px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50" data-price="0.01">GHS 0.01</button>
                            <button type="button" class="quick-price-btn px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50" data-price="1.00">GHS 1.00</button>
                            <button type="button" class="quick-price-btn px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50" data-price="5.00">GHS 5.00</button>
                            <button type="button" class="quick-price-btn px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50" data-price="10.00">GHS 10.00</button>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Changes take effect immediately for new submissions
                        </div>
                        <button type="submit" name="update_price"
                                class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Update Price
                        </button>
                    </div>
                </form>
            </div>

            <!-- Price Impact Information -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
                <h4 class="font-medium text-yellow-900 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Important Notes
                </h4>
                <ul class="text-sm text-yellow-800 space-y-1">
                    <li>• Price changes only affect new submissions</li>
                    <li>• Existing submissions retain their original price</li>
                    <li>• Students will see the new price on the submission form</li>
                    <li>• All price changes are logged for audit purposes</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Price slider functionality
        const priceSlider = document.getElementById('price-slider');
        const priceDisplay = document.getElementById('price-display');
        const currentPriceDisplay = document.getElementById('current-price-display');
        const quickPriceBtns = document.querySelectorAll('.quick-price-btn');

        function updatePriceDisplay() {
            const price = parseFloat(priceSlider.value);
            priceDisplay.textContent = 'GHS ' + price.toFixed(2);
        }

        priceSlider.addEventListener('input', function() {
            updatePriceDisplay();
        });

        // Quick price buttons
        quickPriceBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const price = parseFloat(this.dataset.price);
                priceSlider.value = price;
                updatePriceDisplay();
            });
        });

        // Initialize display
        updatePriceDisplay();
    </script>
</body>
</html>
