<?php
/**
 * Enhanced import script: updates or inserts student records from Google Form CSV.
 * Sets password to '12345678', verifies all users, and marks all applications as 'submitted'.
 * USAGE: php import_students_with_update.php /path/to/google_form.csv
 */

$csvFile = $argv[1] ?? '';
$csvFile = $argv[1];
if (!is_readable($csvFile)) exit("Cannot read $csvFile\n");

$db = new PDO(
    'mysql:host=localhost;dbname=u499616432_adullamn_cams;charset=utf8mb4',
    'DB_USER', // <-- EDIT THIS
    'DB_PASS', // <-- EDIT THIS
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Password hash for '12345678'
$commonPass = password_hash('12345678', PASSWORD_BCRYPT);

// Helper functions
function parts($full) {
    $bits = preg_split('/\s+/', trim($full));
    return [
        'first'=> $bits[0] ?? '',
        'last' => array_pop($bits) ?? '',
        'mid'  => trim(implode(' ', array_slice($bits,1,-1)))
    ];
}
function yesno($v){ return (stripos($v,'yes')===0)?'yes':'no'; }
function mode($v){ return (stripos($v,'on')===0)?'online':'onsite'; }
function rowExists($pdo, $table, $uid) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = ?");
    $stmt->execute([$uid]);
    return $stmt->fetchColumn() > 0;
}

// Open CSV
$row = 0;
$header = [];
if (($h = fopen($csvFile,'r')) !== false){
    $header = fgetcsv($h);
    while(($data = fgetcsv($h)) !== false){
        $row++;
        $rec = array_combine($header,$data);
        if (empty(trim($rec['Full name']??''))) continue;

        // Check if user exists by email
        $email = trim($rec['Email'] ?? '');
        if ($email === '') $email = null;
        $uid = null;

        if ($email){
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $uid = $stmt->fetchColumn();
        }

        $name = parts($rec['Full name']);
        if (!$uid){
            // Create new user
            $stmt = $db->prepare("INSERT INTO users(first_name,middle_name,last_name,email,phone,verified,role,password) VALUES(?,?,?,?,?,1,'student',?)");
            $stmt->execute([$name['first'],$name['mid'],$name['last'],$email,$rec['Phone Number'] ?? '', $commonPass]);
            $uid = $db->lastInsertId();
            if (!$email){
                $email = "user{$uid}@noemail.local";
                $db->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email,$uid]);
            }
        } else {
            // Update user
            $stmt = $db->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, phone=?, password=?, verified=1 WHERE id=?");
            $stmt->execute([$name['first'],$name['mid'],$name['last'],$rec['Phone Number'] ?? '', $commonPass, $uid]);
        }

        $db->beginTransaction();
        try {
            // Applications
            if (!rowExists($db, 'applications', $uid)) {
                $stmt = $db->prepare("INSERT INTO applications(user_id,status,submitted,submitted_at,created_at) VALUES(?, 'submitted', 1, NOW(), NOW())");
                $stmt->execute([$uid]);
            } else {
                $stmt = $db->prepare("UPDATE applications SET status='submitted', submitted=1, submitted_at=NOW() WHERE user_id=?");
                $stmt->execute([$uid]);
            }

            // application_details
            $stmt = rowExists($db, 'application_details', $uid)
                ? $db->prepare("UPDATE application_details SET gender=?, dob=?, program=?, ma_focus=?, mode_of_study=?, res_address=?, perm_address=? WHERE user_id=?")
                : $db->prepare("INSERT INTO application_details(gender,dob,program,ma_focus,mode_of_study,res_address,perm_address,user_id) VALUES(?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $rec['Gender'] ?? null,
                date('Y-m-d',strtotime($rec['Date of Birth']??'1900-01-01')),
                $rec['Program Applying For'] ?? null,
                $rec['MA Focus'] ?? null,
                mode($rec['Mode of Study'] ?? ''),
                $rec['Residential Address'] ?? null,
                $rec['Permanent Address'] ?? null,
                $uid
            ]);

            // application_personal
            $stmt = rowExists($db, 'application_personal', $uid)
                ? $db->prepare("UPDATE application_personal SET maritalstatus=?,children=?,dhealth=?,disciplinary=?,mental_health=?,fbank=?,drug=?,employment=?,felony=?,smisconduct=?,soffence=?,divource=?,spouse=? WHERE user_id=?")
                : $db->prepare("INSERT INTO application_personal(maritalstatus,children,dhealth,disciplinary,mental_health,fbank,drug,employment,felony,smisconduct,soffence,divource,spouse,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $rec['Marital Status'] ?? null,
                $rec['Number of Children'] ?? null,
                yesno($rec['Do you have any physical, mental or emotional disabilities?']),
                yesno($rec['Have you ever been on academic or disciplinary probation?']),
                yesno($rec['Have you ever been under mental health care?']),
                yesno($rec['Have you ever declared bankruptcy or legal action against your finances?']),
                yesno($rec['Have you ever used illegal drugs or abused alcohol?']),
                yesno($rec['Have you ever been dismissed/fired from a job?']),
                yesno($rec['Have you ever been convicted of a felony or dishonorably discharged?']),
                yesno($rec['Have you ever been accused of sexually related misconduct?']),
                yesno($rec['Are you a registered sex offender or convicted of a sex offence?']),
                yesno($rec['If married, is your spouse in agreement with this program?']),
                null, $uid
            ]);

            // application_references
            $stmt = rowExists($db, 'application_references', $uid)
                ? $db->prepare("UPDATE application_references SET ref1Name=?, ref1Phone=?, ref1Email=?, ref2Name=?, ref2Phone=?, ref2Email=? WHERE user_id=?")
                : $db->prepare("INSERT INTO application_references(ref1Name,ref1Phone,ref1Email,ref2Name,ref2Phone,ref2Email,user_id) VALUES(?,?,?,?,?,?,?)");
            $stmt->execute([
                $rec['Reference 1 Name'] ?? '', $rec['Reference 1 Phone'] ?? '', $rec['Reference 1 Email'] ?? '',
                $rec['Reference 2 Name'] ?? '', $rec['Reference 2 Phone'] ?? '', $rec['Reference 2 Email'] ?? '', $uid
            ]);

            // application_church
            $stmt = rowExists($db, 'application_church', $uid)
                ? $db->prepare("UPDATE application_church SET church_name=?, caddress=? WHERE user_id=?")
                : $db->prepare("INSERT INTO application_church(church_name,caddress,user_id) VALUES(?,?,?)");
            $stmt->execute([$rec['Church Name'] ?? '', $rec['Church Address'] ?? '', $uid]);

            // application_autobiography
            $stmt = rowExists($db, 'application_autobiography', $uid)
                ? $db->prepare("UPDATE application_autobiography SET qgospel=?, sgrowth=?, callto=? WHERE user_id=?")
                : $db->prepare("INSERT INTO application_autobiography(qgospel,sgrowth,callto,user_id) VALUES(?,?,?,?)");
            $stmt->execute([
                $rec['How would you explain the gospel of Jesus Christ?'] ?? '',
                $rec['Describe your conversion experience and spiritual growth'] ?? '',
                $rec['Explain if you have a call to ministry and/or reason for applying'] ?? '',
                $uid
            ]);

            // application_documents
            $stmt = rowExists($db, 'application_documents', $uid)
                ? $db->prepare("UPDATE application_documents SET passport=?, ssce_cert=?, birth_cert=?, origin_cert=?, recommendation=?, payment_proof=?, degree_cert=?, transcript=? WHERE user_id=?")
                : $db->prepare("INSERT INTO application_documents(passport,ssce_cert,birth_cert,origin_cert,recommendation,payment_proof,degree_cert,transcript,user_id) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $rec['Passport Photograph (JPG, PNG, less than 1MB)'] ?? null,
                $rec['SSCE Certificate (JPG, PNG, PDF, less than 1MB)'] ?? null,
                $rec['Birth Certificate (JPG, PNG, PDF, less than 1MB)'] ?? null,
                $rec['LGA/State/Nationality Certificate (JPG, PNG, PDF, less than 1MB)'] ?? null,
                $rec['Recommendation Letter (PDF, DOCX, DOC, less than 1MB)'] ?? null,
                $rec['Application Proof of Payment (JPG, PNG, PDF, less than 1MB) Download Account Details'] ?? null,
                $rec['Degree Certificate (JPG, PNG, PDF, less than 1MB)'] ?? null,
                $rec['Transcript (JPG, PNG, PDF, less than 1MB) – Optional'] ?? null,
                $uid
            ]);

            $db->commit();
            echo "Row $row done (user_id=$uid)\n";
        } catch (Exception $e) {
            $db->rollBack();
            echo "Row $row failed: " . $e->getMessage() . "\n";
        }
    }
    fclose($h);
}
echo "=== IMPORT COMPLETE ===\n";
