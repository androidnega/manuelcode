<?php
/**
 * Pre-add Projects to Database
 * Run this once on your server (manuelcode.info) to add projects directly
 * Access via: https://manuelcode.info/pre_add_projects.php
 * 
 * Projects to add:
 * - SellApp Store (sellapp.store)
 * - Boss and J (bossandj.com)
 * - Kabz Event (kabbzevent.com)
 * - DigitsTec Store (digitstec.store)
 */

// Include database connection
include 'includes/db.php';

// Security: Add a simple check to prevent accidental runs
$run_key = isset($_GET['key']) ? $_GET['key'] : '';
$expected_key = 'add2024'; // Change this to something secure

if ($run_key !== $expected_key) {
    die('Access denied. Add ?key=add2024 to the URL to proceed.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pre-Add Projects to Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #536895;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
            margin: 10px 0;
            padding: 10px;
            background: #d4edda;
            border-radius: 4px;
        }
        .skip {
            color: #856404;
            margin: 10px 0;
            padding: 10px;
            background: #fff3cd;
            border-radius: 4px;
        }
        .error {
            color: #dc3545;
            margin: 10px 0;
            padding: 10px;
            background: #f8d7da;
            border-radius: 4px;
        }
        .info {
            color: #004085;
            margin: 10px 0;
            padding: 10px;
            background: #cce5ff;
            border-radius: 4px;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        a {
            color: #536895;
            text-decoration: none;
            margin-right: 15px;
        }
        a:hover {
            text-decoration: underline;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pre-Adding Projects to Database</h1>
        
        <?php
        // Projects to add
        $projects = [
            [
                'title' => 'SellApp Store',
                'description' => 'A modern e-commerce platform for selling digital products and applications. Features include secure payment processing, product management, user accounts, and download management. Built with a focus on user experience and seamless transactions.',
                'category' => 'E-commerce',
                'live_url' => 'https://sellapp.store',
                'technologies' => 'PHP, MySQL, JavaScript, HTML5, CSS3, Payment Gateway Integration',
                'featured' => 1
            ],
            [
                'title' => 'Boss and J',
                'description' => 'Professional business website showcasing services and portfolio. Features modern design, responsive layout, contact forms, and service pages. Built to represent the brand effectively with smooth user experience.',
                'category' => 'Web Development',
                'live_url' => 'https://bossandj.com',
                'technologies' => 'PHP, MySQL, JavaScript, HTML5, CSS3, Responsive Design',
                'featured' => 1
            ],
            [
                'title' => 'Kabz Event',
                'description' => 'Event management and ticketing platform. Features include event creation, ticket sales, attendee management, and event promotion tools. Designed to streamline event planning and ticket distribution.',
                'category' => 'Software Solution',
                'live_url' => 'https://kabbzevent.com',
                'technologies' => 'PHP, MySQL, JavaScript, Event Management System, Payment Integration',
                'featured' => 0
            ],
            [
                'title' => 'DigitsTec Store',
                'description' => 'E-commerce platform specializing in digital products and technology solutions. Features include product catalog, shopping cart, secure checkout, and customer account management. Optimized for digital product sales.',
                'category' => 'E-commerce',
                'live_url' => 'https://digitstec.store',
                'technologies' => 'PHP, MySQL, JavaScript, E-commerce Platform, Payment Processing',
                'featured' => 0
            ]
        ];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (title, description, category, live_url, technologies, featured, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $added = 0;
            $skipped = 0;
            $errors = 0;
            
            echo "<div class='info'>📋 Processing " . count($projects) . " projects...</div><br>";
            
            foreach ($projects as $project) {
                // Check if project already exists by title or URL
                $checkStmt = $pdo->prepare("SELECT id, title FROM projects WHERE title = ? OR live_url = ?");
                $checkStmt->execute([$project['title'], $project['live_url']]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    echo "<div class='skip'>⏭️  Skipped: <strong>{$project['title']}</strong> (already exists - ID: {$existing['id']})</div>";
                    $skipped++;
                    continue;
                }
                
                // Insert project
                try {
                    $stmt->execute([
                        $project['title'],
                        $project['description'],
                        $project['category'],
                        $project['live_url'],
                        $project['technologies'],
                        $project['featured']
                    ]);
                    
                    $projectId = $pdo->lastInsertId();
                    $featuredBadge = $project['featured'] ? ' ⭐ Featured' : '';
                    echo "<div class='success'>✅ Added: <strong>{$project['title']}</strong> - <a href='{$project['live_url']}' target='_blank'>{$project['live_url']}</a>{$featuredBadge} (ID: {$projectId})</div>";
                    $added++;
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Error adding <strong>{$project['title']}</strong>: " . htmlspecialchars($e->getMessage()) . "</div>";
                    $errors++;
                }
            }
            
            echo "<div class='summary'>";
            echo "<h2>Summary</h2>";
            echo "<p><strong>✅ Added:</strong> $added project(s)</p>";
            echo "<p><strong>⏭️  Skipped:</strong> $skipped project(s) (already exist)</p>";
            if ($errors > 0) {
                echo "<p><strong>❌ Errors:</strong> $errors project(s)</p>";
            }
            echo "<hr>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ul>";
            echo "<li><a href='projects.php' target='_blank'>View Projects Page</a></li>";
            echo "<li><a href='admin/projects.php' target='_blank'>Admin Projects Panel</a></li>";
            echo "</ul>";
            echo "<p><small>💡 <strong>Note:</strong> You can safely delete this file (<code>pre_add_projects.php</code>) after running it once.</small></p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            error_log("Error pre-adding projects: " . $e->getMessage());
        }
        ?>
    </div>
</body>
</html>

