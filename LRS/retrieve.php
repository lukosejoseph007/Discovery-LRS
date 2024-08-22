<?php
// db.php - Database connection settings
$host = 'localhost';
$dbname = 'lrs_db';
$user = 'root'; // Default XAMPP username
$pass = ''; // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Add filter, sort, and row limit options from the request
$filterEmail = isset($_POST['email']) ? $_POST['email'] : '';
$filterActor = isset($_POST['actor']) ? $_POST['actor'] : '';
$filterVerb = isset($_POST['verb']) ? $_POST['verb'] : '';
$sortBy = isset($_POST['sort']) ? $_POST['sort'] : 'timestamp';
$sortOrder = isset($_POST['sortOrder']) ? $_POST['sortOrder'] : 'ASC'; // Default to ascending
$rowLimit = isset($_POST['rowLimit']) ? intval($_POST['rowLimit']) : 10; // Default to 10 rows

// Base query
$query = 'SELECT * FROM statements WHERE 1';

// Add filtering by email if provided
if (!empty($filterEmail)) {
    $query .= ' AND email LIKE :filterEmail';
}

// Add filtering by actor if provided
if (!empty($filterActor)) {
    $query .= ' AND actor LIKE :filterActor';
}

// Add filtering by verb if provided
if (!empty($filterVerb)) {
    $query .= ' AND verb LIKE :filterVerb';
}

// Add sorting
$allowedSortColumns = ['timestamp', 'actor']; // Add other columns if needed
if (in_array($sortBy, $allowedSortColumns)) {
    $query .= ' ORDER BY ' . $sortBy . ' ' . $sortOrder;
} else {
    $query .= ' ORDER BY timestamp ' . $sortOrder; // Default sorting
}

// Apply row limit
$query .= ' LIMIT :rowLimit';

$stmt = $pdo->prepare($query);

// Bind parameters for filtering
if (!empty($filterEmail)) {
    $stmt->bindValue(':filterEmail', '%' . $filterEmail . '%');
}
if (!empty($filterActor)) {
    $stmt->bindValue(':filterActor', '%' . $filterActor . '%');
}
if (!empty($filterVerb)) {
    $stmt->bindValue(':filterVerb', '%' . $filterVerb . '%');
}

// Bind parameter for row limit
$stmt->bindValue(':rowLimit', $rowLimit, PDO::PARAM_INT);

$stmt->execute();
$statements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to format actor
function formatActor($actor) {
    $data = json_decode($actor, true);
    return isset($data['name']) && isset($data['mbox']) ? "{$data['name']} ({$data['mbox']})" : 'N/A';
}

// Function to format verb
function formatVerb($verb) {
    $data = json_decode($verb, true);
    return isset($data['display']['en-US']) ? $data['display']['en-US'] : 'N/A';
}

// Function to format object
function formatObject($object) {
    $data = json_decode($object, true);
    return isset($data['definition']['name']['en-US']) ? $data['definition']['name']['en-US'] : 'N/A';
}

// Function to format result
function formatResult($result) {
    $data = json_decode($result, true);
    if ($data === null) {
        // No result data
        return 'N/A';
    }
    // Check if success is present and return appropriate status
    return isset($data['success']) 
        ? ($data['success'] ? 'Succeeded' : 'Failed') 
        : 'N/A';
}

// Function to format timestamp with selected timezone
function formatTimestamp($timestamp, $timezone) {
    $date = new DateTime($timestamp, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone($timezone));
    $formattedDate = $date->format('Y-m-d H:i:s');
    $timezoneAbbr = $date->format('T');
    return "{$formattedDate} ({$timezoneAbbr})";
}

// Get the selected timezone from the request
$selectedTimezone = isset($_POST['timezone']) ? $_POST['timezone'] : 'Asia/Kolkata'; // Default timezone

// List of supported timezones
$timezones = DateTimeZone::listIdentifiers();

// Export functions
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function exportToCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="statements.csv"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['ID', 'Statement ID', 'Actor', 'Verb', 'Object', 'Result', 'Timestamp']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['statement_id'],
            formatActor($row['actor']),
            formatVerb($row['verb']),
            formatObject($row['object']),
            formatResult($row['result'] ?? 'N/A'),
            formatTimestamp($row['timestamp'], $_POST['timezone'] ?? 'Asia/Kolkata')
        ]);
    }
    
    fclose($output);
    exit;
}

function exportToXLSX($data) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Statement ID');
    $sheet->setCellValue('C1', 'Actor');
    $sheet->setCellValue('D1', 'Verb');
    $sheet->setCellValue('E1', 'Object');
    $sheet->setCellValue('F1', 'Result');
    $sheet->setCellValue('G1', 'Timestamp');
    
    $rowNum = 2;
    
    foreach ($data as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['id']);
        $sheet->setCellValue('B' . $rowNum, $row['statement_id']);
        $sheet->setCellValue('C' . $rowNum, formatActor($row['actor']));
        $sheet->setCellValue('D' . $rowNum, formatVerb($row['verb']));
        $sheet->setCellValue('E' . $rowNum, formatObject($row['object']));
        $sheet->setCellValue('F' . $rowNum, formatResult($row['result'] ?? 'N/A'));
        $sheet->setCellValue('G' . $rowNum, formatTimestamp($row['timestamp'], $_POST['timezone'] ?? 'Asia/Kolkata'));
        $rowNum++;
    }
    
    $writer = new Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="statements.xlsx"');
    
    $writer->save('php://output');
    exit;
}

if (isset($_POST['export'])) {
    if ($_POST['export'] === 'csv') {
        exportToCSV($statements);
    } elseif ($_POST['export'] === 'xlsx') {
        exportToXLSX($statements);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="variables.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Retrieve Statements</title>
</head>
<body>
    <header>
        <div class="header-content">
            <img src="images\logo.png" alt="Brand Logo" class="logo">
            <h1 class="logo-text"><a href="index.html">Discovery LRS</a></h1>
        </div>
        <nav>
        <a href="insert.html">Insert Statement</a>
        <a href="retrieve.php">View Statements</a>
        </nav>
    </header>
    <div class="container">
        <main class="main-content">
            <h2>View xAPI Statements</h2>
            <!-- Timezone selection form -->
            <form method="post" action="retrieve.php">
                <label for="timezone">Select Timezone:</label>
                <select id="timezone" name="timezone">
                    <?php foreach ($timezones as $timezone): ?>
                        <option value="<?php echo htmlspecialchars($timezone); ?>" <?php echo ($timezone === $selectedTimezone) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($timezone); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Apply</button>
            </form>

    <!-- Sorting and Filtering Form -->
    <form method="post" action="retrieve.php">
        <label for="email">Filter by Email:</label>
        <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($filterEmail); ?>">

        <label for="actor">Filter by Actor:</label>
        <input type="text" id="actor" name="actor" value="<?php echo htmlspecialchars($filterActor); ?>">

        <label for="verb">Filter by Verb:</label>
        <input type="text" id="verb" name="verb" value="<?php echo htmlspecialchars($filterVerb); ?>">

        <!-- Wrap "Sort by:" in a div -->
        <div class="sort-group">
            <label for="sort">Sort by:</label>
            <select id="sort" name="sort">
                <option value="timestamp" <?php echo ($sortBy === 'timestamp') ? 'selected' : ''; ?>>Timestamp</option>
                <option value="actor" <?php echo ($sortBy === 'actor') ? 'selected' : ''; ?>>Actor</option>
            </select>

        <label for="sortOrder">Sort Order:</label>
        <select id="sortOrder" name="sortOrder">
            <option value="ASC" <?php echo ($sortOrder === 'ASC') ? 'selected' : ''; ?>>Ascending</option>
            <option value="DESC" <?php echo ($sortOrder === 'DESC') ? 'selected' : ''; ?>>Descending</option>
        </select>

        <button type="submit">Apply Filters</button>
        </div>
    </form>



            <!-- Display the table -->
            <table id="statementsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Statement ID</th>
                        <th>Actor</th>
                        <th>Verb</th>
                        <th>Object</th>
                        <th>Result</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statements as $statement): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($statement['id']); ?></td>
                            <td><?php echo htmlspecialchars($statement['statement_id']); ?></td>
                            <td><?php echo formatActor($statement['actor']); ?></td>
                            <td><?php echo formatVerb($statement['verb']); ?></td>
                            <td><?php echo formatObject($statement['object']); ?></td>
                            <td><?php echo formatResult($statement['result'] ?? 'N/A'); ?></td>
                            <td><?php echo formatTimestamp($statement['timestamp'], $selectedTimezone); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Row limit selector -->
            <form method="post" action="retrieve.php">
                <div class="row-limit">
                    <label for="rowLimit">Show:</label>
                    <select id="rowLimit" name="rowLimit">
                        <option value="10" <?php echo ($rowLimit == 10) ? 'selected' : ''; ?>>10 rows</option>
                        <option value="20" <?php echo ($rowLimit == 20) ? 'selected' : ''; ?>>20 rows</option>
                        <option value="50" <?php echo ($rowLimit == 50) ? 'selected' : ''; ?>>50 rows</option>
                        <option value="100" <?php echo ($rowLimit == 100) ? 'selected' : ''; ?>>100 rows</option>
                    </select>
                    <button type="submit">Apply</button>
                </div>
            </form>

            <!-- Export buttons -->
            <form method="post" action="retrieve.php">
                <button type="submit" name="export" value="csv">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
                <button type="submit" name="export" value="xlsx">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </form>
        </main>
    </div>
    <footer>
        <p>&copy; 2024 <a href="https://lukosejoseph.com" target="_blank" rel="noopener noreferrer">LukoseJoseph.com</a>. All Rights Reserved.</p>
    </footer>
    <script>
        $(document).ready( function () {
            $('#statementsTable').DataTable({
                paging: true,
                searching: false,
                info: true,
                autoWidth: false,
                columnDefs: [
                    { orderable: true, targets: '_all' }
                ]
            });
        });
    </script>
</body>
</html>
