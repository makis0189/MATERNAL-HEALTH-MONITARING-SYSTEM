function generatePatientID($conn) {
    $query = "SELECT MAX(id) as max_id FROM patients";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    $number = ($row['max_id'] ?? 0) + 1;

    return "MHS" . str_pad($number, 3, "0", STR_PAD_LEFT);
}
