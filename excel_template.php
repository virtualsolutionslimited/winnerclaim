<?php
/**
 * Excel Template Generator and Upload System for Winners
 */

require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Database configuration
$host = '127.0.0.1';
$dbname = 'raffle';
$username = 'root';
$password = '';

$message = '';
$error = '';
$uploadedCount = 0;

// Handle template download
if (isset($_GET['download_template'])) {
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Phone');
        
        // Style the header row
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
        
        // Add sample data (optional - can be removed)
        $sheet->setCellValue('A2', 'John Doe');
        $sheet->setCellValue('B2', '0201234567');
        
        $sheet->setCellValue('A3', 'Jane Smith');
        $sheet->setCellValue('B3', '0507654321');
        
        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        
        // Set active sheet
        $sheet->setTitle('Winners Data');
        
        // Create the writer
        $writer = new Xlsx($spreadsheet);
        
        // Send the file to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="winners_template.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        $error = "Error generating template: " . $e->getMessage();
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        // Check if file was uploaded
        if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $_FILES['excel_file']['error']);
        }
        
        // Check file type
        $allowedTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['excel_file']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Invalid file type. Please upload an Excel file (.xls or .xlsx)");
        }
        
        // Load the spreadsheet
        $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Get the highest row number
        $highestRow = $worksheet->getHighestRow();
        
        if ($highestRow < 2) {
            throw new Exception("No data found in the Excel file. Please add at least one row of data.");
        }
        
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare insert statement
        $stmt = $pdo->prepare("
            INSERT INTO winners (name, phone, is_claimed, createdAt, updatedAt) 
            VALUES (?, ?, 0, NOW(), NOW())
        ");
        
        $uploadedCount = 0;
        $errors = [];
        
        // Process each row (starting from row 2 to skip header)
        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $name = trim($worksheet->getCell('A' . $row)->getValue());
                $phone = trim($worksheet->getCell('B' . $row)->getValue());
                
                // Validate required fields
                if (empty($name) || empty($phone)) {
                    $errors[] = "Row $row: Name and Phone are required";
                    continue;
                }
                
                // Validate phone format (basic validation)
                if (!preg_match('/^[0-9+\s()-]+$/', $phone)) {
                    $errors[] = "Row $row: Invalid phone format for '$phone'";
                    continue;
                }
                
                // Insert into database
                $stmt->execute([$name, $phone]);
                $uploadedCount++;
                
            } catch (PDOException $e) {
                $errors[] = "Row $row: Database error - " . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Row $row: " . $e->getMessage();
            }
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
    <title>Winners Excel Upload</title>
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
        
        .upload-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            <h1>📊 Winners Excel Upload</h1>
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
            <h2 class="section-title">📥 Download Excel Template</h2>
            
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
                    <li>Download and fill the Excel template with winner data</li>
                    <li>Ensure Name and Phone columns are filled for each row</li>
                    <li>Phone numbers should contain only digits, +, spaces, hyphens, or parentheses</li>
                    <li>Select the filled Excel file below and upload</li>
                    <li>System will validate and insert data into the winners table</li>
                </ol>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="file-input-wrapper">
                    <input type="file" name="excel_file" class="file-input" accept=".xls,.xlsx" required>
                    <div class="file-input-button">
                        📁 Choose Excel File (.xls or .xlsx)
                    </div>
                </div>
                <div class="file-name" id="file-name">No file selected</div>
                
                <button type="submit" class="submit-btn">
                    🚀 Upload Winners Data
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Update file name when file is selected
        document.querySelector('.file-input').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html>
