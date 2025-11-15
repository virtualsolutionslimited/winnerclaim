<?php
/**
 * Draw Schedule Viewer
 * Displays current and next draw week information
 */

// Include the draw functions
require_once 'draw_functions.php';

// Database configuration
$host = '127.0.0.1';
$dbname = 'raffle';
$username = 'root';
$password = '';

// Initialize variables
$currentDraw = null;
$nextDraw = null;
$upcomingDraws = [];
$latestPastDraw = null;
$error = '';

// Connect to database and get draw information
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $currentDraw = getCurrentDrawWeek($pdo);
    $nextDraw = getNextDrawWeek($pdo);
    $upcomingDraws = getUpcomingDraws($pdo, 5);
    $latestPastDraw = getLatestPastDraw($pdo);
    
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draw Schedule</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }
        
        .draw-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .draw-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .draw-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }
        
        .current-draw .card-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .next-draw .card-icon {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }
        
        .past-draw .card-icon {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }
        
        .upcoming-draw .card-icon {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: white;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }
        
        .draw-date {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin: 15px 0;
        }
        
        .draw-time {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 15px;
        }
        
        .time-until {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-today {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-tomorrow {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-upcoming {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .upcoming-list {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .upcoming-list h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .upcoming-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s ease;
        }
        
        .upcoming-item:hover {
            background: #f8f9fa;
        }
        
        .upcoming-item:last-child {
            border-bottom: none;
        }
        
        .upcoming-date {
            font-weight: 600;
            color: #333;
        }
        
        .upcoming-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        .upcoming-status {
            text-align: right;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        
        .no-data {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: transform 0.3s ease;
            margin: 20px auto;
            display: block;
        }
        
        .refresh-btn:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .draw-grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .draw-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎯 Draw Schedule</h1>
            <p class="subtitle">Weekly Sunday Draws at 6:00 PM</p>
        </header>
        
        <?php if ($error): ?>
            <div class="error-message">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            
            <div class="draw-grid">
                <!-- Current Draw -->
                <?php if ($currentDraw): ?>
                    <div class="draw-card current-draw">
                        <div class="card-header">
                            <div class="card-icon">🎯</div>
                            <div class="card-title">Current Draw</div>
                        </div>
                        <div class="draw-date"><?php echo $currentDraw['formatted']; ?></div>
                        <div class="draw-time"><?php echo $currentDraw['day_name']; ?> at <?php echo date('g:i A', strtotime($currentDraw['time'])); ?></div>
                        <div class="time-until">
                            <?php echo getTimeUntil($currentDraw); ?>
                        </div>
                        <span class="status-badge status-<?php echo $currentDraw['status']; ?>">
                            <?php echo ucfirst($currentDraw['status']); ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="draw-card">
                        <div class="no-data">
                            No current draw found
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Next Draw -->
                <?php if ($nextDraw): ?>
                    <div class="draw-card next-draw">
                        <div class="card-header">
                            <div class="card-icon">📅</div>
                            <div class="card-title">Next Draw</div>
                        </div>
                        <div class="draw-date"><?php echo $nextDraw['formatted']; ?></div>
                        <div class="draw-time"><?php echo $nextDraw['day_name']; ?> at <?php echo date('g:i A', strtotime($nextDraw['time'])); ?></div>
                        <div class="time-until">
                            <?php echo getTimeUntil($nextDraw); ?>
                        </div>
                        <span class="status-badge status-<?php echo $nextDraw['status']; ?>">
                            <?php echo ucfirst($nextDraw['status']); ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="draw-card">
                        <div class="no-data">
                            No next draw found
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Latest Past Draw -->
                <?php if ($latestPastDraw): ?>
                    <div class="draw-card past-draw">
                        <div class="card-header">
                            <div class="card-icon">✅</div>
                            <div class="card-title">Latest Completed Draw</div>
                        </div>
                        <div class="draw-date"><?php echo $latestPastDraw['formatted']; ?></div>
                        <div class="draw-time"><?php echo $latestPastDraw['day_name']; ?> at <?php echo date('g:i A', strtotime($latestPastDraw['time'])); ?></div>
                        <div class="time-until">
                            Draw completed
                        </div>
                        <span class="status-badge status-completed">
                            Completed
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Upcoming Draws List -->
            <?php if (!empty($upcomingDraws)): ?>
                <div class="upcoming-list">
                    <h2>📋 Upcoming Draws</h2>
                    <?php foreach ($upcomingDraws as $draw): ?>
                        <div class="upcoming-item">
                            <div>
                                <div class="upcoming-date"><?php echo $draw['short_date']; ?></div>
                                <div class="upcoming-time"><?php echo $draw['day_name']; ?> at <?php echo date('g:i A', strtotime($draw['time'])); ?></div>
                            </div>
                            <div class="upcoming-status">
                                <span class="status-badge status-<?php echo $draw['status']; ?>">
                                    <?php echo getTimeUntil($draw); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <button class="refresh-btn" onclick="location.reload()">
            🔄 Refresh Schedule
        </button>
    </div>
</body>
</html>
