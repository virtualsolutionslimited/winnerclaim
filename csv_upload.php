<?php
require_once 'db.php';
require_once 'draw_functions.php';

/**
 * Normalize phone number for CSV upload
 * - If 9 digits and doesn't start with 0, add 0 at the beginning
 * - If starts with 233, replace with 0
 * - Handle Excel scientific notation (e.g., 2.33549E+11 -> 233549000000)
 * - Handle Excel truncated numbers (e.g., 2.34E+11 -> 234000000000)
 * @param string $phone The phone number to normalize
 * @return string The normalized phone number
 */
function normalizePhoneForUpload($phone) {
    // Handle Excel scientific notation first
    if (stripos($phone, 'E') !== false || stripos($phone, 'e') !== false) {
        // Convert scientific notation to regular number
        $phone = number_format(floatval($phone), 0, '.', '');
        
        // Check if this looks like an Excel-truncated Ghana number
        // Excel often rounds 233XXXXXXXXX to 2.34E+11 (234000000000)
        if (strlen($phone) === 12 && substr($phone, 0, 3) === '234') {
            // This is likely a truncated Ghana number, replace 234 with 0
            return '0' . substr($phone, 3);
        }
    }
    
    // Remove any non-digit characters
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    
    // If starts with 233, replace with 0
    if (strlen($cleanPhone) >= 12 && substr($cleanPhone, 0, 3) === '233') {
        return '0' . substr($cleanPhone, 3);
    }
    
    // If exactly 9 digits and doesn't start with 0, add 0 at the beginning
    if (strlen($cleanPhone) === 9 && substr($cleanPhone, 0, 1) !== '0') {
        return '0' . $cleanPhone;
    }
    
    // If starts with 0 and has correct length (10 digits), return as is
    if (strlen($cleanPhone) === 10 && substr($cleanPhone, 0, 1) === '0') {
        return $cleanPhone;
    }
    
    // Return original if no normalization needed
    return $phone;
}

// Get upcoming draws for dropdown
$upcomingDraws = [];
try {
    $upcomingDraws = getUpcomingDraws($pdo, 10); // Get next 10 draws
    
    // Debug: Check if we found any draws
    error_log("CSV Upload - Found " . count($upcomingDraws) . " upcoming draws");
    
    // If no upcoming draws, try to get all draws as a fallback
    if (empty($upcomingDraws)) {
        error_log("No upcoming draws found, trying fallback to get all draws");
        $stmt = $pdo->query("SELECT * FROM draw_dates ORDER BY date ASC LIMIT 10");
        $allDraws = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $now = new DateTime();
        foreach ($allDraws as $draw) {
            $drawDate = new DateTime($draw['date']);
            // Only include current and future draws, not past ones
            if ($drawDate >= $now) {
                $upcomingDraws[] = formatDrawInfo($draw, $drawDate, $now);
            }
        }
        
        error_log("Fallback found " . count($upcomingDraws) . " upcoming draws (excluding past)");
    }
    
} catch (Exception $e) {
    error_log("Error getting upcoming draws: " . $e->getMessage());
}

$message = '';
$error = '';
$uploadedCount = 0;
if (isset($_GET['download_template'])) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="winners_template.csv"');
    header('Cache-Control: max-age=0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // Write header row with tab delimiter (Excel's default)
    fwrite($output, "Name\tPhone\n");
    
    // Add sample data (optional)
    fwrite($output, "John Doe\t0201234567\n");
    fwrite($output, "Jane Smith\t0507654321\n");
    
    fclose($output);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        // Check if draw week was selected
        if (!isset($_POST['draw_week']) || empty($_POST['draw_week'])) {
            throw new Exception("Please select a draw week for the upload.");
        }
        $drawWeekId = (int)$_POST['draw_week'];
        
        // Check if file was uploaded
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $_FILES['csv_file']['error']);
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'csv') {
            throw new Exception("Invalid file type. Please upload a CSV file (.csv)");
        }
        
        // Read the CSV file
        $csvData = [];
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        
        if (!$handle) {
            throw new Exception("Unable to open the uploaded file");
        }
        
        // Skip BOM if present
        $firstLine = fgets($handle);
        if (strpos($firstLine, "\xEF\xBB\xBF") === 0) {
            $firstLine = substr($firstLine, 3);
        }
        
        // Put first line back and read as CSV
        rewind($handle);
        
        // Detect delimiter by reading first line
        $firstLine = fgets($handle);
        $delimiter = ',';
        if (strpos($firstLine, "\t") !== false) {
            $delimiter = "\t";
        }
        rewind($handle);
        
        $rowNumber = 0;
        $errors = [];
        $batch = [];
        
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare insert statement
        $stmt = $pdo->prepare("
            INSERT INTO winners (name, phone, draw_week, createdAt, updatedAt) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $rowNumber++;
            
            // Skip header row (assuming first row contains headers)
            if ($rowNumber === 1) {
                // Debug: Log what we're actually reading
                error_log("Row 1 data: " . print_r($data, true));
                error_log("Header 0: '" . trim($data[0]) . "'");
                error_log("Header 1: '" . trim($data[1]) . "'");
                
                // Validate headers with more flexible checking
                $header0 = strtolower(trim($data[0], " \t\n\r\0\x0B\xEF\xBB\xBF"));
                $header1 = strtolower(trim($data[1], " \t\n\r\0\x0B\xEF\xBB\xBF"));
                
                if ($header0 !== 'name' || $header1 !== 'phone') {
                    throw new Exception("Invalid CSV format. First row must contain 'Name' and 'Phone' headers. Found: '$header0' and '$header1'");
                }
                continue;
            }
            
            try {
                // Get name and phone from columns A and B (indices 0 and 1)
                $name = isset($data[0]) ? trim($data[0], " \t\n\r\0\x0B\xEF\xBB\xBF") : '';
                $phone = isset($data[1]) ? trim($data[1], " \t\n\r\0\x0B\xEF\xBB\xBF") : '';
                
                // Validate required fields
                if (empty($name) || empty($phone)) {
                    $errors[] = "Row $rowNumber: Name and Phone are required";
                    continue;
                }
                
                // Normalize phone number first
                $normalizedPhone = normalizePhoneForUpload($phone);
                
                // Log normalization for debugging
                error_log("Row $rowNumber: Phone normalized from '$phone' to '$normalizedPhone'");
                
                // Validate phone format (basic validation) - use normalized phone
                if (!preg_match('/^[0-9+\s()-]+$/', $normalizedPhone)) {
                    $errors[] = "Row $rowNumber: Invalid phone format for '$phone' (normalized to '$normalizedPhone')";
                    continue;
                }
                
                // Check for Excel-truncated numbers (all zeros after prefix)
                if (preg_match('/^0{10}$/', $normalizedPhone)) {
                    $errors[] = "Row $rowNumber: Excel truncated number '$phone' - please format phone column as text in Excel";
                    continue;
                }
                
                // Add to batch with draw week
                $batch[] = [
                    'name' => $name,
                    'phone' => $normalizedPhone,
                    'draw_week' => $drawWeekId
                ];
                
            } catch (PDOException $e) {
                $errors[] = "Row $rowNumber: Database error - " . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        
        // Prepare and execute insert statement with draw week
        $stmt = $pdo->prepare("
            INSERT INTO winners (name, phone, draw_week, createdAt, updatedAt) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($batch as $row) {
            $stmt->execute([$row['name'], $row['phone'], $row['draw_week']]);
            $uploadedCount++;
        }
        
        if ($uploadedCount > 0) {
            $message = "Successfully uploaded $uploadedCount winners!";
        }
        
        if (!empty($errors)) {
            $error .= "Some rows had errors:\n" . implode("\n", $errors);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winners CSV Upload</title>
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
            max-width: 800px;
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
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .template-info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        .template-info h3 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        
        .template-info ul {
            margin-left: 20px;
            color: #555;
        }
        
        .template-info li {
            margin-bottom: 8px;
        }
        
        .download-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .download-btn:hover {
            transform: scale(1.05);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }
        
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
        }
        
        .form-group select:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }
        
        .form-group input[type="file"] {
            padding: 10px 15px;
        }
        
        .form-group input[type="file"]::file-selector-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 15px;
            transition: transform 0.2s ease;
        }
        
        .form-group input[type="file"]::file-selector-button:hover {
            transform: scale(1.05);
        }
        
        .upload-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-button {
            display: block;
            width: 100%;
            padding: 15px;
            background: white;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            color: #666;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .file-input-button:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: scale(1.05);
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            white-space: pre-line;
        }
        
        .file-name {
            margin-top: 10px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .instructions {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .instructions h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .instructions ol {
            margin-left: 20px;
            color: #856404;
        }
        
        .instructions li {
            margin-bottom: 5px;
        }
        
        .excel-note {
            background: #e2e3e5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #6c757d;
        }
        
        .excel-note h4 {
            color: #495057;
            margin-bottom: 10px;
        }
        
        .excel-note p {
            color: #495057;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Winners CSV Upload</h1>
            <p class="subtitle">Download template and upload winner data</p>
        </header>
        
        <?php if ($message): ?>
            <div class="success-message">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Template Download Section -->
        <div class="card">
            <h2 class="section-title">📥 Download CSV Template</h2>
            
            <div class="template-info">
                <h3>Template Information</h3>
                <ul>
                    <li><strong>Column A:</strong> Name (Required)</li>
                    <li><strong>Column B:</strong> Phone (Required)</li>
                    <li>Template includes sample data in rows 2-3</li>
                    <li>Replace sample data with your actual winner data</li>
                    <li>Keep the header row (Row 1) unchanged</li>
                </ul>
            </div>
            
            <div class="excel-note">
                <h4>📋 Excel Compatible</h4>
                <p>This CSV file will open directly in Excel and can be edited like a regular Excel spreadsheet. When you save, choose "CSV (Comma delimited)" format.</p>
            </div>
            
            <a href="?download_template=1" class="download-btn">
                📥 Download Winners Template
            </a>
        </div>
        
        <!-- Upload Section -->
        <div class="card">
            <h2 class="section-title">📤 Upload Winners Data</h2>
            
            <div class="instructions">
                <h4>Instructions:</h4>
                <ol>
                    <li>Download and open the CSV template in Excel</li>
                    <li>Fill the template with winner data (Name and Phone columns)</li>
                    <li>Save the file as CSV format in Excel</li>
                    <li>Select the saved CSV file below and upload</li>
                    <li>System will validate and insert data into the winners table</li>
                </ol>
                
                <?php if (empty($upcomingDraws)): ?>
                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                        <strong>⚠️ No Draw Weeks Available</strong><br>
                        The draw dates table appears to be empty. Please <a href="run_seeder.php" style="color: #667eea; font-weight: bold;">run the draw dates seeder</a> first to populate upcoming draw weeks.
                    </div>
                <?php endif; ?>
            </div>
            
            <form action="" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="draw_week">Select Draw Week:</label>
                    <select name="draw_week" id="draw_week" required>
                        <option value="">-- Choose Draw Week --</option>
                        <?php if (!empty($upcomingDraws)): ?>
                            <?php foreach ($upcomingDraws as $draw): ?>
                                <?php if (!$draw['is_past']): // Only show current and future draws ?>
                                <option value="<?php echo $draw['id']; ?>">
                                    <?php echo $draw['formatted']; ?>
                                    <?php if ($draw['is_today']): echo ' (Today)'; elseif ($draw['days_until'] == 1): echo ' (Tomorrow)'; elseif ($draw['days_until'] > 1 && $draw['days_until'] <= 7): echo ' (In ' . $draw['days_until'] . ' days)'; endif; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No upcoming draws available</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="csv_file">Choose CSV file:</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    🚀 Upload Winners Data
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function validateForm() {
            const fileInput = document.getElementById('csv_file');
            const drawWeekInput = document.getElementById('draw_week');
            
            if (!drawWeekInput.value) {
                alert('Please select a draw week.');
                drawWeekInput.focus();
                return false;
            }
            
            if (fileInput.files.length === 0) {
                alert('Please select a file to upload.');
                fileInput.focus();
                return false;
            }
            
            const fileName = fileInput.files[0].name;
            if (!fileName.endsWith('.csv')) {
                alert('Please select a CSV file.');
                fileInput.focus();
                return false;
            }
            
            return true;
        }
        
        // Update file name when file is selected
        document.querySelector('.file-input').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html>
