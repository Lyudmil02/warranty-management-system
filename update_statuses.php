<?php
date_default_timezone_set("Europe/Sofia");

$result = $conn->query("SELECT id, warranty_end, notify_days_before FROM warranties");

$today = new DateTime();

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $end_date = new DateTime($row['warranty_end']);
    $notify_days = intval($row['notify_days_before']);
    $diff_days = (int)$today->diff($end_date)->format('%r%a'); 

    if ($diff_days < 0) {
        $status = 'EXPIRED';
    } elseif ($diff_days <= $notify_days) {
        $status = 'EXPIRES_SOON';
    } else {
        $status = 'ACTIVE';
    }

    $stmt = $conn->prepare("UPDATE warranties SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
}

