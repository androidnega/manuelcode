<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add New Projects</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        }
        .skip {
            color: #ffc107;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            margin: 10px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Adding New Projects to Database</h1>
<?php
/**
 * Script to add new projects to the database
 * Projects: sellapp.store, bossandj.com, kabbzevent.com, digitstec.store
 */

include 'includes/db.php';

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
    
    foreach ($projects as $project) {
        // Check if project already exists
        $checkStmt = $pdo->prepare("SELECT id FROM projects WHERE title = ? OR live_url = ?");
        $checkStmt->execute([$project['title'], $project['live_url']]);
        
        if ($checkStmt->fetch()) {
            echo "<div class='skip'>⏭️  Skipped: <strong>{$project['title']}</strong> (already exists)</div>";
            $skipped++;
            continue;
        }
        
        // Insert project
        $stmt->execute([
            $project['title'],
            $project['description'],
            $project['category'],
            $project['live_url'],
            $project['technologies'],
            $project['featured']
        ]);
        
        echo "<div class='success'>✅ Added: <strong>{$project['title']}</strong> - <a href='{$project['live_url']}' target='_blank'>{$project['live_url']}</a></div>";
        $added++;
    }
    
    echo "<div class='summary'>";
    echo "<h2>Summary</h2>";
    echo "<p><strong>✅ Added:</strong> $added project(s)</p>";
    echo "<p><strong>⏭️  Skipped:</strong> $skipped project(s)</p>";
    echo "<p><a href='projects.php'>View Projects</a> | <a href='admin/projects.php'>Admin Panel</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    error_log("Error adding projects: " . $e->getMessage());
}
?>
    </div>
</body>
</html>

