<?php
/**
 * driver_api/check_unique.php
 *
 * GET params: username, email, phone, plate
 * Returns { ok:true, available:true/false, matched: [fields] }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_resp(['ok'=>false,'message'=>'Method not allowed'],405);

$username = isset($_GET['username']) ? trim($_GET['username']) : null;
$email = isset($_GET['email']) ? trim($_GET['email']) : null;
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : null;
$plate = isset($_GET['plate']) ? trim($_GET['plate']) : null;

if (!$username && !$email && !$phone && !$plate) json_resp(['ok'=>false,'message'=>'Provide at least one parameter'],400);

$db = new Database();
$conn = $db->getConnection();

$clauses = [];
$types = '';
$values = [];
if ($username) { $clauses[] = 'LOWER(Username)=LOWER(?)'; $types .= 's'; $values[] = $username; }
if ($email) { $clauses[] = 'LOWER(Email)=LOWER(?)'; $types .= 's'; $values[] = $email; }
if ($phone) { $clauses[] = 'Phone_Number=?'; $types .= 's'; $values[] = $phone; }
if ($plate) { $clauses[] = 'Car_Plate_Number=?'; $types .= 's'; $values[] = $plate; }

$sql = 'SELECT Username, Email, Phone_Number, Car_Plate_Number FROM drivers WHERE ' . implode(' OR ', $clauses) . ' LIMIT 1';
$stmt = $conn->prepare($sql);
if (!$stmt) json_resp(['ok'=>false,'message'=>'DB error (prepare)'],500);
if (!empty($values)) $stmt->bind_param($types, ...$values);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $matched = [];
    if ($username && strtolower($row['Username']) === strtolower($username)) $matched[] = 'username';
    if ($email && strtolower($row['Email']) === strtolower($email)) $matched[] = 'email';
    if ($phone && $row['Phone_Number'] === $phone) $matched[] = 'phone';
    if ($plate && $row['Car_Plate_Number'] === $plate) $matched[] = 'plate';
    json_resp(['ok'=>true,'available'=>false,'matched'=>$matched],200);
}

json_resp(['ok'=>true,'available'=>true],200);
