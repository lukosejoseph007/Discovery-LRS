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
$filterCourse = isset($_POST['courseName']) ? $_POST['courseName'] : '';
$sortBy = isset($_POST['sort']) ? $_POST['sort'] : 'timestamp';
$sortOrder = isset($_POST['sortOrder']) ? $_POST['sortOrder'] : 'ASC'; // Default to ascending
$rowLimit = isset($_POST['rowLimit']) ? intval($_POST['rowLimit']) : 10; // Default to 10 rows
$currentPage = isset($_POST['page']) ? intval($_POST['page']) : 1; // Default to page 1

// Calculate offset
$offset = ($currentPage - 1) * $rowLimit;

// Base query
$query = 'SELECT id, statement_id, actor, verb, object, result, timestamp, context, courseName, courseId FROM statements WHERE 1';

// Add filtering conditions
if (!empty($filterEmail)) {
    $query .= ' AND email LIKE :filterEmail';
}
if (!empty($filterActor)) {
    $query .= ' AND actor LIKE :filterActor';
}
if (!empty($filterVerb)) {
    $query .= ' AND verb LIKE :filterVerb';
}
if (!empty($filterCourse)) {
    $query .= ' AND courseName LIKE :filterCourse';
}

// Add sorting
$allowedSortColumns = ['timestamp', 'actor', 'courseName', 'courseId'];
if (in_array($sortBy, $allowedSortColumns)) {
    $query .= ' ORDER BY ' . $sortBy . ' ' . $sortOrder;
} else {
    $query .= ' ORDER BY timestamp ' . $sortOrder;
}

// Apply row limit and offset
$query .= ' LIMIT :offset, :rowLimit'; // Correct usage

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
if (!empty($filterCourse)) {
    $stmt->bindValue(':filterCourse', '%' . $filterCourse . '%');
}

// Bind parameters for row limit and offset
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Bind offset as first parameter
$stmt->bindValue(':rowLimit', $rowLimit, PDO::PARAM_INT); // Bind row limit as second parameter

$stmt->execute();
$statements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination logic
$totalQuery = 'SELECT COUNT(*) FROM statements WHERE 1';
if (!empty($filterEmail)) {
    $totalQuery .= ' AND email LIKE :filterEmail';
}
if (!empty($filterActor)) {
    $totalQuery .= ' AND actor LIKE :filterActor';
}
if (!empty($filterVerb)) {
    $totalQuery .= ' AND verb LIKE :filterVerb';
}
if (!empty($filterCourse)) {
    $totalQuery .= ' AND courseName LIKE :filterCourse';
}

$totalStmt = $pdo->prepare($totalQuery);

// Bind parameters for filtering
if (!empty($filterEmail)) {
    $totalStmt->bindValue(':filterEmail', '%' . $filterEmail . '%');
}
if (!empty($filterActor)) {
    $totalStmt->bindValue(':filterActor', '%' . $filterActor . '%');
}
if (!empty($filterVerb)) {
    $totalStmt->bindValue(':filterVerb', '%' . $filterVerb . '%');
}
if (!empty($filterCourse)) {
    $totalStmt->bindValue(':filterCourse', '%' . $filterCourse . '%');
}

$totalStmt->execute();
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $rowLimit);

// Functions for formatting
function formatActor($actor) {
    $data = json_decode($actor, true);
    return isset($data['name']) && isset($data['mbox']) ? "{$data['name']} ({$data['mbox']})" : 'N/A';
}

function formatVerb($verb) {
    $data = json_decode($verb, true);
    return isset($data['display']['en-US']) ? $data['display']['en-US'] : 'N/A';
}

function formatObject($object) {
    $data = json_decode($object, true);
    return isset($data['definition']['name']['en-US']) ? $data['definition']['name']['en-US'] : 'N/A';
}

function formatResult($result) {
    $data = json_decode($result, true);
    if ($data === null) {
        return 'N/A';
    }
    if (isset($data['success'])) {
        return $data['success'] ? 'Passed' : 'Failed';
    }
    return 'N/A';
}

function formatTimestamp($timestamp, $timezone) {
    $date = new DateTime($timestamp, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone($timezone));
    $formattedDate = $date->format('Y-m-d H:i:s');
    $timezoneAbbr = $date->format('T');
    return "{$formattedDate} ({$timezoneAbbr})";
}

$selectedTimezone = isset($_POST['timezone']) ? $_POST['timezone'] : 'Asia/Kolkata'; // Default timezone

// List of supported timezones
$timezones = DateTimeZone::listIdentifiers();

function extractCourseData($context) {
    $data = json_decode($context, true);
    $courseId = 'N/A';
    $courseName = 'N/A';
    
    if (isset($data['contextActivities']['parent'][0]['id'])) {
        $courseId = $data['contextActivities']['parent'][0]['id'];
    }
    if (isset($data['contextActivities']['parent'][0]['definition']['name']['en-US'])) {
        $courseName = $data['contextActivities']['parent'][0]['definition']['name']['en-US'];
    }
    
    return ['courseId' => $courseId, 'courseName' => $courseName];
}

// Process statements and extract course data
$processedStatements = [];
foreach ($statements as $row) {
    $courseData = extractCourseData($row['context']);
    $row['courseId'] = $courseData['courseId'];
    $row['courseName'] = $courseData['courseName'];
    $processedStatements[] = $row;
}

// Export functions
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function exportToCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="statements.csv"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['ID', 'Statement ID', 'Actor', 'Verb', 'Object', 'Result', 'Timestamp', 'Course ID', 'Course Name']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['statement_id'],
            formatActor($row['actor']),
            formatVerb($row['verb']),
            formatObject($row['object']),
            formatResult($row['result'] ?? 'N/A'),
            formatTimestamp($row['timestamp'], $_POST['timezone'] ?? 'Asia/Kolkata'),
            htmlspecialchars($row['courseId'] ?? 'N/A'),
            htmlspecialchars($row['courseName'] ?? 'N/A')
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
    $sheet->setCellValue('H1', 'Course ID');
    $sheet->setCellValue('I1', 'Course Name');
    
    $rowIndex = 2;
    foreach ($data as $row) {
        $sheet->setCellValue('A' . $rowIndex, $row['id']);
        $sheet->setCellValue('B' . $rowIndex, $row['statement_id']);
        $sheet->setCellValue('C' . $rowIndex, formatActor($row['actor']));
        $sheet->setCellValue('D' . $rowIndex, formatVerb($row['verb']));
        $sheet->setCellValue('E' . $rowIndex, formatObject($row['object']));
        $sheet->setCellValue('F' . $rowIndex, formatResult($row['result'] ?? 'N/A'));
        $sheet->setCellValue('G' . $rowIndex, formatTimestamp($row['timestamp'], $_POST['timezone'] ?? 'Asia/Kolkata'));
        $sheet->setCellValue('H' . $rowIndex, htmlspecialchars($row['courseId'] ?? 'N/A'));
        $sheet->setCellValue('I' . $rowIndex, htmlspecialchars($row['courseName'] ?? 'N/A'));
        $rowIndex++;
    }
    
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="statements.xlsx"');
    $writer->save('php://output');
    exit;
}

// Handle export request
if (isset($_POST['export']) && $_POST['export'] === 'csv') {
    exportToCSV($processedStatements);
}

if (isset($_POST['export']) && $_POST['export'] === 'xlsx') {
    exportToXLSX($processedStatements);
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
            <img src="images/logo.png" alt="Brand Logo" class="logo">
            <h1 class="logo-text"><a href="index.html">Discovery LRS</a></h1>
        </div>
        <nav>
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
                        <option value="<?= $timezone ?>" <?= $timezone === $selectedTimezone ? 'selected' : '' ?>><?= $timezone ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Apply</button>
            </form>

            <h3>Sorting and Filtering</h3>

                <!-- Sorting and Filtering Form -->
                <form method="post" action="retrieve.php">
                    <label for="email">Filter by Email:</label>
                    <input type="text" id="email" name="email" value="<?= htmlspecialchars($filterEmail) ?>">

                    <label for="actor">Filter by Actor:</label>
                    <input type="text" id="actor" name="actor" value="<?= htmlspecialchars($filterActor) ?>">

                    <label for="verb">Filter by Verb:</label>
                    <input type="text" id="verb" name="verb" value="<?= htmlspecialchars($filterVerb) ?>">

                    <label for="course">Filter by Course:</label>
                    <input type="text" id="courseName" name="courseName" value="<?= htmlspecialchars($filterCourse) ?>">

                    <!-- Wrap "Sort by:" in a div -->
                    <div class="sort-group">
                        <label for="sort">Sort By:</label>
                        <select id="sort" name="sort">
                            <option value="timestamp" <?= $sortBy === 'timestamp' ? 'selected' : '' ?>>Timestamp</option>
                            <option value="actor" <?= $sortBy === 'actor' ? 'selected' : '' ?>>Actor</option>
                            <option value="courseName" <?= $sortBy === 'courseName' ? 'selected' : '' ?>>Course Name</option>
                            <option value="courseId" <?= $sortBy === 'courseId' ? 'selected' : '' ?>>Course ID</option>
                        </select>

                    <label for="sortOrder">Sort Order:</label>
                        <select id="sortOrder" name="sortOrder">
                            <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                            <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Descending</option>
                        </select>

                    <button type="submit">Apply Filters</button>
                    </div>
                </form>

            
            <!-- Data Table -->
            <table id="statementsTable" class="display">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Actor</th>
                        <th>Verb</th>
                        <th>Object</th>
                        <th>Result</th>
                        <th>Timestamp</th>
                        <th>Statement ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statements as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['courseId'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['courseName'] ?? 'N/A') ?></td>
                            <td><?= formatActor($row['actor']) ?></td>
                            <td><?= formatVerb($row['verb']) ?></td>
                            <td><?= formatObject($row['object']) ?></td>
                            <td><?= formatResult($row['result'] ?? 'N/A') ?></td>
                            <td><?= formatTimestamp($row['timestamp'], $selectedTimezone) ?></td>
                            <td><?= htmlspecialchars($row['statement_id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Row limit selector -->
            <form method="post" action="retrieve.php">
                <div class="row-limit">
                    <label for="rowLimit">Show:</label>
                    <select id="rowLimit" name="rowLimit">
                        <option value="10" <?= $rowLimit === 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $rowLimit === 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $rowLimit === 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $rowLimit === 100 ? 'selected' : '' ?>>100</option>
                    </select>
                    <input type="hidden" name="page" value="<?= $currentPage ?>">
                    <button type="submit">Apply</button>
                </div>
            </form>
            <!-- Container for Centering -->
            <div class="form-container">
                <!-- Pagination Links -->
                <form method="post" action="retrieve.php" id="paginationForm">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($filterEmail) ?>">
                    <input type="hidden" name="actor" value="<?= htmlspecialchars($filterActor) ?>">
                    <input type="hidden" name="verb" value="<?= htmlspecialchars($filterVerb) ?>">
                    <input type="hidden" name="courseName" value="<?= htmlspecialchars($filterCourse) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>">
                    <input type="hidden" name="sortOrder" value="<?= htmlspecialchars($sortOrder) ?>">
                    <input type="hidden" name="rowLimit" value="<?= htmlspecialchars($rowLimit) ?>">
                    <input type="hidden" name="timezone" value="<?= htmlspecialchars($selectedTimezone) ?>">
                    <input type="hidden" name="page" value="<?= $currentPage ?>">

                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="javascript:void(0);" onclick="document.getElementById('paginationForm').elements['page'].value = <?= $currentPage - 1; ?>; document.getElementById('paginationForm').submit();">Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="javascript:void(0);" onclick="document.getElementById('paginationForm').elements['page'].value = <?= $i; ?>; document.getElementById('paginationForm').submit();"><?= $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="javascript:void(0);" onclick="document.getElementById('paginationForm').elements['page'].value = <?= $currentPage + 1; ?>; document.getElementById('paginationForm').submit();">Next</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
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
        <p>&copy; <?= date('Y') ?> Discovery LRS. All rights reserved.</p>
    </footer>
    <script>
        $(document).ready( function () {
            $('#statementsTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true
            });
        });
        function changePage(pageNumber) {
            var form = document.getElementById('paginationForm');
            form.page.value = pageNumber;
            form.submit();
    }
    </script>
</body>
</html>
