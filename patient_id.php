function generatePatientID($conn) {
    $query = "SELECT patient_id FROM patients ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $last_id = $row['patient_id'];
        $number = (int) substr($last_id, 3);
        $number++;
        $new_id = "MHS" . str_pad($number, 3, "0", STR_PAD_LEFT);
    } else {
        $new_id = "MHS001";
    }

    return $new_id;
}
