<?php

function silah_get_cities($pdo) {
    $fallback = [
        'Islamabad',
        'Karachi', 'Lahore', 'Peshawar', 'Quetta',
        'Rawalpindi', 'Faisalabad', 'Multan', 'Hyderabad', 'Gujranwala', 'Sialkot', 'Sukkur', 'Larkana',
        'Bahawalpur', 'Rahim Yar Khan', 'Dera Ghazi Khan', 'Sargodha', 'Sheikhupura', 'Kasur', 'Okara', 'Sahiwal',
        'Abbottabad', 'Mardan', 'Swat', 'Nowshera', 'Kohat',
        'Mirpur Khas', 'Nawabshah', 'Gwadar', 'Khuzdar',
        'Gilgit', 'Skardu', 'Muzaffarabad', 'Mirpur'
    ];

    if (!$pdo) {
        return $fallback;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS cities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                country VARCHAR(80) NOT NULL DEFAULT 'Pakistan',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_cities_name_country (name, country),
                INDEX idx_cities_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Exception $e) {
        return $fallback;
    }

    try {
        try {
            $pdo->exec("UPDATE cities SET is_active = 0 WHERE country <> 'Pakistan'");
        } catch (Exception $e) {
        }

        $seed = [
                ['Islamabad', 'Pakistan'],
                ['Karachi', 'Pakistan'], ['Lahore', 'Pakistan'], ['Rawalpindi', 'Pakistan'], ['Faisalabad', 'Pakistan'],
                ['Multan', 'Pakistan'], ['Peshawar', 'Pakistan'], ['Quetta', 'Pakistan'], ['Hyderabad', 'Pakistan'],
                ['Gujranwala', 'Pakistan'], ['Sialkot', 'Pakistan'], ['Sukkur', 'Pakistan'], ['Larkana', 'Pakistan'],
                ['Bahawalpur', 'Pakistan'], ['Bahawalnagar', 'Pakistan'], ['Rahim Yar Khan', 'Pakistan'],
                ['Dera Ghazi Khan', 'Pakistan'], ['Muzaffargarh', 'Pakistan'], ['Layyah', 'Pakistan'], ['Rajanpur', 'Pakistan'],
                ['Khanewal', 'Pakistan'], ['Vehari', 'Pakistan'], ['Lodhran', 'Pakistan'], ['Okara', 'Pakistan'],
                ['Sahiwal', 'Pakistan'], ['Pakpattan', 'Pakistan'], ['Kasur', 'Pakistan'], ['Sheikhupura', 'Pakistan'],
                ['Nankana Sahib', 'Pakistan'], ['Gujrat', 'Pakistan'], ['Jhelum', 'Pakistan'], ['Chakwal', 'Pakistan'],
                ['Attock', 'Pakistan'], ['Mandi Bahauddin', 'Pakistan'], ['Hafizabad', 'Pakistan'], ['Narowal', 'Pakistan'],
                ['Sargodha', 'Pakistan'], ['Khushab', 'Pakistan'], ['Mianwali', 'Pakistan'], ['Bhakkar', 'Pakistan'],
                ['Jhang', 'Pakistan'], ['Chiniot', 'Pakistan'], ['Toba Tek Singh', 'Pakistan'], ['Ferozewala', 'Pakistan'],
                ['Gujranwala', 'Pakistan'], ['Sialkot', 'Pakistan'],

                ['Peshawar', 'Pakistan'], ['Charsadda', 'Pakistan'], ['Nowshera', 'Pakistan'], ['Mardan', 'Pakistan'],
                ['Swabi', 'Pakistan'], ['Kohat', 'Pakistan'], ['Karak', 'Pakistan'], ['Hangu', 'Pakistan'],
                ['Bannu', 'Pakistan'], ['Lakki Marwat', 'Pakistan'], ['Dera Ismail Khan', 'Pakistan'], ['Tank', 'Pakistan'],
                ['Abbottabad', 'Pakistan'], ['Haripur', 'Pakistan'], ['Mansehra', 'Pakistan'], ['Battagram', 'Pakistan'],
                ['Shangla', 'Pakistan'], ['Swat', 'Pakistan'], ['Buner', 'Pakistan'], ['Malakand', 'Pakistan'],
                ['Lower Dir', 'Pakistan'], ['Upper Dir', 'Pakistan'], ['Chitral', 'Pakistan'], ['Bajaur', 'Pakistan'],
                ['Mohmand', 'Pakistan'], ['Khyber', 'Pakistan'], ['Orakzai', 'Pakistan'], ['Kurram', 'Pakistan'],
                ['North Waziristan', 'Pakistan'], ['South Waziristan', 'Pakistan'], ['Torghar', 'Pakistan'],

                ['Karachi', 'Pakistan'], ['Thatta', 'Pakistan'], ['Sujawal', 'Pakistan'], ['Badin', 'Pakistan'],
                ['Hyderabad', 'Pakistan'], ['Jamshoro', 'Pakistan'], ['Matiari', 'Pakistan'],
                ['Tando Allahyar', 'Pakistan'], ['Tando Muhammad Khan', 'Pakistan'],
                ['Dadu', 'Pakistan'], ['Larkana', 'Pakistan'], ['Qambar Shahdadkot', 'Pakistan'],
                ['Shikarpur', 'Pakistan'], ['Jacobabad', 'Pakistan'], ['Kashmore', 'Pakistan'],
                ['Ghotki', 'Pakistan'], ['Khairpur', 'Pakistan'], ['Sukkur', 'Pakistan'],
                ['Naushahro Feroze', 'Pakistan'], ['Shaheed Benazirabad', 'Pakistan'], ['Sanghar', 'Pakistan'],
                ['Mirpur Khas', 'Pakistan'], ['Umerkot', 'Pakistan'], ['Tharparkar', 'Pakistan'],

                ['Quetta', 'Pakistan'], ['Pishin', 'Pakistan'], ['Killa Abdullah', 'Pakistan'], ['Killa Saifullah', 'Pakistan'],
                ['Ziarat', 'Pakistan'], ['Mastung', 'Pakistan'], ['Kalat', 'Pakistan'], ['Khuzdar', 'Pakistan'],
                ['Lasbela', 'Pakistan'], ['Awaran', 'Pakistan'], ['Kech', 'Pakistan'], ['Panjgur', 'Pakistan'],
                ['Gwadar', 'Pakistan'], ['Turbat', 'Pakistan'], ['Chagai', 'Pakistan'], ['Nushki', 'Pakistan'],
                ['Kharan', 'Pakistan'], ['Washuk', 'Pakistan'], ['Sibi', 'Pakistan'], ['Harnai', 'Pakistan'],
                ['Kohlu', 'Pakistan'], ['Dera Bugti', 'Pakistan'], ['Loralai', 'Pakistan'], ['Barkhan', 'Pakistan'],
                ['Musakhel', 'Pakistan'], ['Jaffarabad', 'Pakistan'], ['Nasirabad', 'Pakistan'], ['Sohbatpur', 'Pakistan'],

                ['Gilgit', 'Pakistan'], ['Skardu', 'Pakistan'], ['Hunza', 'Pakistan'], ['Nagar', 'Pakistan'],
                ['Ghizer', 'Pakistan'], ['Diamer', 'Pakistan'], ['Astore', 'Pakistan'], ['Shigar', 'Pakistan'],
                ['Ghanche', 'Pakistan'],

                ['Muzaffarabad', 'Pakistan'], ['Neelum', 'Pakistan'], ['Hattian Bala', 'Pakistan'],
                ['Bagh', 'Pakistan'], ['Haveli', 'Pakistan'], ['Poonch', 'Pakistan'], ['Sudhnoti', 'Pakistan'],
                ['Kotli', 'Pakistan'], ['Mirpur', 'Pakistan'], ['Bhimber', 'Pakistan'],
        ];

        $pkCount = 0;
        try {
            $pkCount = (int)$pdo->query("SELECT COUNT(*) FROM cities WHERE country = 'Pakistan'")->fetchColumn();
        } catch (Exception $e) {
            $pkCount = 0;
        }

        if ($pkCount < 50) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO cities (name, country, is_active) VALUES (?, ?, 1)");
            foreach ($seed as $row) {
                $stmt->execute([(string)$row[0], (string)$row[1]]);
            }
        }
    } catch (Exception $e) {
    }

    $cities = [];
    try {
        $stmt = $pdo->query("SELECT name FROM cities WHERE is_active = 1 AND country = 'Pakistan' ORDER BY name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $name = isset($r['name']) ? trim((string)$r['name']) : '';
            if ($name !== '') {
                $cities[] = $name;
            }
        }
    } catch (Exception $e) {
        $cities = [];
    }

    return !empty($cities) ? $cities : $fallback;
}
