
<?php
/**
 * adullam_import_run_v2
 * - Safely inserts NEW users from CSV
 * - Updates EXISTING users **only if email matches non-empty email**
 * - Generates unique placeholder email for email-less applicants
 * - Generates admission_no in format ADM/JUN/2025/xxxx
 * - Converts Program names to short codes
 * - Marks application submitted only when a valid program exists
 */

function adullam_import_run(string $csvFile): string {
    ob_start();

    // --- DB credentials ---
    $pdo = new PDO(
        'mysql:host=localhost;dbname=u499616432_adullamn_cams;charset=utf8mb4',
        'u499616432_adullamn',
        'Rq;u54Y77#QFxx',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $passwordHash = password_hash('12345678', PASSWORD_BCRYPT);

    // Program mapping
    $programMap = [
        'Certificate in Theology'          => 'Certificate',
        'Diploma in Theology'              => 'Diploma',
        'Bachelor of Divinity'             => 'B.Div',
        'Postgraduate Diploma in Theology' => 'PGDT',
        'Postgraduate Diploma In Theology' => 'PGDT',
        'Postgraduate Diploma in THeology' => 'PGDT'
    ];

    function splitName($full) {
        $parts = preg_split('/\s+/', trim($full));
        return [
            'first'  => $parts[0] ?? '',
            'last'   => array_pop($parts) ?? '',
            'middle' => trim(implode(' ', array_slice($parts,1,-1)))
        ];
    }
    function yn($v){ return (stripos($v ?? '','yes')===0)?'yes':'no'; }
    function studymode($v){
    return (stripos(strtolower($v ?? ''), 'on') === 0) ? 'online' : 'onsite';
}

    function rowExists(PDO $db, string $table, int $uid): bool {
        $st = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=?");
        $st->execute([$uid]);
        return $st->fetchColumn() > 0;
    }

    function nextAdmission(PDO $db): string {
        $prefix = 'ADM/JUN/2025/';
        $max = $db->query("SELECT MAX(admission_no) FROM applications WHERE admission_no LIKE '{$prefix}%'")->fetchColumn();
        if (preg_match('/(\d{4})$/', $max, $m)) {
            $next = str_pad(((int)$m[1])+1, 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }
        return $prefix.$next;
    }

    // === Begin CSV import ===
    if (!($handle = fopen($csvFile,'r'))) { return 'CSV could not be opened'; }
    $header = fgetcsv($handle);
    $row = 0;
    while(($data = fgetcsv($handle)) !== false){
        $row++;
        $rec = array_combine($header,$data);
        if (!trim($rec['Full name'] ?? '')) { echo "Row $row skipped (no name)
"; continue;}

        $email = trim($rec['Email'] ?? '');
        $uid = null;

        // Only find existing user if email is provided
        if ($email !== ''){
            $st = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $st->execute([$email]);
            $uid = $st->fetchColumn();
        }

        $name = splitName($rec['Full name']);

        if (!$uid){
            // create new user
            $pdo->prepare("INSERT INTO users(first_name,middle_name,last_name,email,phone,verified,role,password)
                           VALUES(?,?,?,?,?,1,'student',?)")
                ->execute([
                    $name['first'],$name['middle'],$name['last'],
                    $email ?: null,
                    $rec['Phone Number'] ?? '',
                    $passwordHash
                ]);
            $uid = $pdo->lastInsertId();
            if ($email === ''){ // generate unique placeholder email
                $placeholder = "noemail+{$uid}@adullam.local";
                $pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$placeholder,$uid]);
            }
        } else {
            // update minimal fields
            $pdo->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, phone=?, password=?, verified=1 WHERE id=?")
                ->execute([$name['first'],$name['middle'],$name['last'],$rec['Phone Number'] ?? '',$passwordHash,$uid]);
        }

        // // Map program & focus
        // $programRaw = trim($rec['Program Applying For'] ?? '');
        // $programCode = $programMap[$programRaw] ?? ($programRaw ?: null);
        // $maFocus = null;
        // if(strtolower($programCode) === 'ma' || stripos($programRaw,'M.A.')===0){
        //     $programCode = 'MA';
        //     $maFocus = trim($rec['M.A. Christian Apologetic and M.A. Biblical Studies (OT/NT)'] ?? '');
        // }
//         // Map program & focus
// $programRaw = trim($rec['Program Applying For'] ?? '');
// $programCode = $programMap[$programRaw] ?? ($programRaw ?: null);
// $maFocus = null;

// // ✅ If the program starts with 'M.A.', treat it as MA and save the full name as focus
// if (stripos($programRaw, 'M.A.') === 0) {
//     $programCode = 'MA';
//     $maFocus = $programRaw;
// }

        // Map program & focus
$programRaw = trim($rec['Program Applying For'] ?? '');
$programCode = $programMap[$programRaw] ?? ($programRaw ?: null);
$maFocus = null;

// ✅ Detect and extract MA programs with specialization
if (stripos($programRaw, 'M.A.') === 0) {
    $programCode = 'MA';
    $maFocus = $programRaw;  // Save full MA string as focus
}


        // Start transaction for application data
        $pdo->beginTransaction();
        try{
            $app = $pdo->prepare("SELECT id,admission_no FROM applications WHERE user_id=?");
            $app->execute([$uid]);
            $appRow = $app->fetch(PDO::FETCH_ASSOC);
            $adm = ($appRow && !empty($appRow['admission_no'])) ? $appRow['admission_no'] : nextAdmission($pdo);


            // only mark submitted if program code exists
            if(!$appRow){
                $pdo->prepare("INSERT INTO applications(user_id,admission_no,status,submitted,submitted_at,created_at)
                               VALUES(?,?,?, ?, NOW(), NOW())")
                    ->execute([$uid,$adm,
                        $programCode ? 'submitted':'draft',
                        $programCode ? 1 : 0]);
            }else{
                $pdo->prepare("UPDATE applications SET admission_no=?, status=?, submitted=?, submitted_at=IF(?,NOW(),submitted_at) WHERE user_id=?")
                    ->execute([$adm,
                        $programCode ? 'submitted':'draft',
                        $programCode ? 1 : 0,
                        $programCode ? 1 : 0,
                        $uid]);
            }

            // application_details
            if (rowExists($pdo,'application_details',$uid)){
                $pdo->prepare("UPDATE application_details SET gender=?, dob=?, program=?, ma_focus=?, mode_of_study=? WHERE user_id=?")
                    ->execute([
                        $rec['Gender'] ?? null,
                        date('Y-m-d',strtotime($rec['Date of Birth'] ?? '1900-01-01')),
                        $programCode,
                        $maFocus,
                        studymode($rec['Mode of Study'] ?? ''),
                        $uid
                    ]);
            } else {
                $pdo->prepare("INSERT INTO application_details(user_id,gender,dob,program,ma_focus,mode_of_study)
                               VALUES(?,?,?,?,?,?)")
                    ->execute([$uid,
                        $rec['Gender'] ?? null,
                        date('Y-m-d',strtotime($rec['Date of Birth'] ?? '1900-01-01')),
                        $programCode,
                        $maFocus,
                        studymode($rec['Mode of Study'] ?? ''),
                    ]);
            }

            // (Other tables remain unchanged, but safe not to override unrelated records.)

            $pdo->commit();
            echo "Row $row OK (uid=$uid, adm=$adm)
";
        }catch(Exception $e){
            $pdo->rollBack();
            echo "Row $row FAILED: ".$e->getMessage()."
";
        }
    }
    fclose($handle);
    return ob_get_clean();
}
?>
