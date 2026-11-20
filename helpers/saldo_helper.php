<?php
function prosesSetoranIoT($conn, $kode_warga, $id_device, $berat_kg, $event_id) {
    // 1. Validasi berat minyak (> 0 kg)
    if ($berat_kg <= 0) {
        return ['status' => false, 'message' => 'Berat minyak harus lebih dari 0 kg.'];
    }

    // 2. Cek Idempotency (mencegah transaksi ganda jika sinyal IoT dikirim ulang)
    $stmtCekEvent = $conn->prepare("SELECT id_setoran FROM setoran_jelantah WHERE event_id = ?");
    $stmtCekEvent->bind_param("s", $event_id);
    $stmtCekEvent->execute();
    if ($stmtCekEvent->get_result()->num_rows > 0) {
        return ['status' => false, 'message' => 'Transaksi duplikat terdeteksi (Event ID sudah tercatat).'];
    }

    // 3. Validasi identitas warga berdasarkan kode_warga
    $stmtUser = $conn->prepare("SELECT id_user, nama, saldo_koperasi FROM users WHERE kode_warga = ? AND role = 'warga'");
    $stmtUser->bind_param("s", $kode_warga);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($resUser->num_rows === 0) {
        return ['status' => false, 'message' => 'Kode Warga tidak ditemukan atau tidak valid.'];
    }
    $user = $resUser->fetch_assoc();

    // 4. Cek status dan kapasitas tangki stasiun IoT (ambang batas 90%)
    $stmtDevice = $conn->prepare("SELECT * FROM stasiun_iot WHERE id_device = ?");
    $stmtDevice->bind_param("s", $id_device);
    $stmtDevice->execute();
    $resDevice = $stmtDevice->get_result();
    if ($resDevice->num_rows === 0) {
        return ['status' => false, 'message' => 'Perangkat Stasiun IoT tidak terdaftar.'];
    }
    $device = $resDevice->fetch_assoc();

    $persenSaatIni = ($device['berat_terisi_kg'] / $device['kapasitas_maks_kg']) * 100;
    $beratSetelahSetor = $device['berat_terisi_kg'] + $berat_kg;

    if ($persenSaatIni >= 90 || $beratSetelahSetor > $device['kapasitas_maks_kg']) {
        return ['status' => false, 'message' => 'TANGKI PENUH (>=90%). Pintu inlet dikunci otomatis!'];
    }

    // 5. Kalkulasi konversi berat ke saldo koperasi
    $harga_per_kg = (float)$device['harga_per_kg'];
    $nominal_saldo = round($berat_kg * $harga_per_kg, 2);

    // 6. Simpan transaksi secara atomik
    $conn->begin_transaction();
    try {
        $stmtInsert = $conn->prepare("INSERT INTO setoran_jelantah (event_id, id_user, id_device, berat_kg, harga_per_kg, nominal_saldo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtInsert->bind_param("sisddd", $event_id, $user['id_user'], $id_device, $berat_kg, $harga_per_kg, $nominal_saldo);
        $stmtInsert->execute();

        $stmtUpdUser = $conn->prepare("UPDATE users SET saldo_koperasi = saldo_koperasi + ?, total_kg = total_kg + ? WHERE id_user = ?");
        $stmtUpdUser->bind_param("ddi", $nominal_saldo, $berat_kg, $user['id_user']);
        $stmtUpdUser->execute();

        $stmtUpdIoT = $conn->prepare("UPDATE stasiun_iot SET berat_terisi_kg = berat_terisi_kg + ? WHERE id_device = ?");
        $stmtUpdIoT->bind_param("ds", $berat_kg, $id_device);
        $stmtUpdIoT->execute();

        $conn->commit();
        return [
            'status' => true,
            'message' => 'Setoran berhasil! Saldo koperasi bertambah Rp ' . number_format($nominal_saldo, 0, ',', '.'),
            'data' => [
                'nama_warga' => $user['nama'],
                'berat_kg' => $berat_kg,
                'nominal_saldo' => $nominal_saldo,
                'saldo_baru' => $user['saldo_koperasi'] + $nominal_saldo
            ]
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return ['status' => false, 'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()];
    }
}
?>
