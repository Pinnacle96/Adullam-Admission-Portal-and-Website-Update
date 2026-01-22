<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
    exit;
}

// Check registration status
$regOpen = $pdo->query("SELECT value FROM settings WHERE `key` = 'registration_open'")->fetchColumn();
if (!$regOpen) {
    header("Location: student_dashboard");
    exit;
}

$user_id = $_SESSION['user_id'];

// 🟢 Load existing data if available
$stmt = $pdo->prepare("SELECT * FROM application_personal WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// 🟢 Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'maritalstatus',
        'children',
        'dhealth',
        'disciplinary',
        'mental_health',
        'fbank',
        'drug',
        'employment',
        'felony',
        'smisconduct',
        'soffence',
        'divource',
        'spouse'
    ];

    $data = [];

    foreach ($fields as $field) {
        // Special handling for children field when maritalstatus is Single
        if ($field === 'children') {
            $data[$field] = ($_POST['maritalstatus'] ?? '') === 'Single' ? '0' : trim($_POST['children'] ?? '');
        } else {
            $data[$field] = trim($_POST[$field] ?? '');
        }
    }

    $stmt = $pdo->prepare("INSERT INTO application_personal (
        user_id, maritalstatus, children, dhealth, disciplinary, mental_health,
        fbank, drug, employment, felony, smisconduct, soffence, divource, spouse
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        maritalstatus = VALUES(maritalstatus),
        children = VALUES(children),
        dhealth = VALUES(dhealth),
        disciplinary = VALUES(disciplinary),
        mental_health = VALUES(mental_health),
        fbank = VALUES(fbank),
        drug = VALUES(drug),
        employment = VALUES(employment),
        felony = VALUES(felony),
        smisconduct = VALUES(smisconduct),
        soffence = VALUES(soffence),
        divource = VALUES(divource),
        spouse = VALUES(spouse)");

    $stmt->execute([
        $user_id,
        $data['maritalstatus'],
        $data['children'],
        $data['dhealth'],
        $data['disciplinary'],
        $data['mental_health'],
        $data['fbank'],
        $data['drug'],
        $data['employment'],
        $data['felony'],
        $data['smisconduct'],
        $data['soffence'],
        $data['divource'],
        $data['spouse']
    ]);

    if (isset($_POST['continue'])) {
        $pdo->prepare("UPDATE applications SET current_level = 3 WHERE user_id = ?")
            ->execute([$user_id]);
        echo "<script>window.location.href = 'form_level3';</script>";
    } elseif (isset($_POST['save'])) {
        echo "<script>
            localStorage.setItem('form2_done', '1');
            window.location.href = 'form_level2';
        </script>";
    } elseif (isset($_POST['previous'])) {
        echo "<script>window.location.href = 'form_level1';</script>";
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application - Step 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-3xl">
        <h2 class="text-xl font-bold text-purple-800 mb-4 text-center">Step 2 of 6: Personal Evaluation</h2>
        <p class="text-xl font-light text-purple-800 mb-4 text-center">The following questions in this section are for counselling purposes and will in no way jeopardize your acceptance into the Theological Seminary.</p>
        

        <form method="POST" class="space-y-6">
    <?php
    function selectField($label, $name, $existing)
    {
        $val = $existing[$name] ?? '';
        echo "<div>
                <label class='block text-sm font-medium text-gray-700 mb-1'>{$label}<span class='text-red-500'>*</span></label>
                <select name='{$name}' required class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
                    <option value=''>Choose an Option</option>
                    <option value='Yes' " . ($val === 'Yes' ? 'selected' : '') . ">Yes</option>
                    <option value='No' " . ($val === 'No' ? 'selected' : '') . ">No</option>
                </select>
            </div>";
    }
    ?>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class='block text-sm font-medium text-gray-700 mb-1'>Marital Status<span
                    class='text-red-500'>*</span></label>
            <select name='maritalstatus' id="maritalSelect" required
                class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
                <option value=''>Marital Status</option>
                <?php
                $marital_options = ['Married', 'Divorced', 'Remarried', 'Separated', 'Single', 'Widowed'];
                foreach ($marital_options as $opt) {
                    $selected = ($existing['maritalstatus'] ?? '') === $opt ? "selected" : "";
                    echo "<option value='{$opt}' {$selected}>{$opt}</option>";
                }
                ?>
            </select>
        </div>

        <div id="children-wrapper">
            <label class='block text-sm font-medium text-gray-700 mb-1'>Number of Children<span
                    class='text-red-500'>*</span></label>
            <input name='children' id="childrenField" type='number' min='0'
                value="<?= htmlspecialchars($existing['children'] ?? '') ?>"
                class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
        </div>
    </div>

    <?php
    selectField("Do you have any physical, mental or emotional disabilities?", "dhealth", $existing);
    selectField("Have you ever been on academic or disciplinary probation?", "disciplinary", $existing);
    selectField("Have you ever been under mental health care?", "mental_health", $existing);
    selectField("Have you ever declared bankruptcy or legal action against your finances?", "fbank", $existing);
    selectField("Have you ever used illegal drugs or abused alcohol?", "drug", $existing);
    selectField("Have you ever been dismissed/fired from a job?", "employment", $existing);
    selectField("Have you ever been convicted of a felony or dishonorably discharged?", "felony", $existing);
    selectField("Have you ever been involved in sexual activity outside of marriage?", "smisconduct", $existing);
    selectField("Are you a registered sex offender or convicted of a sex offence?", "soffence", $existing);
    selectField("Have you ever been divorced?", "divource", $existing);
    ?>

    <div id="spouse-wrapper" class="<?= ($existing['maritalstatus'] ?? '') === 'Married' ? '' : 'hidden' ?>">
        <label class='block text-sm font-medium text-gray-700 mb-1'>If married, is your spouse in agreement with this program?</label>
        <select name='spouse' id="spouseField" class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>
            <option value=''>Choose an Option</option>
            <option value='Yes' <?= ($existing['spouse'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
            <option value='No' <?= ($existing['spouse'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
        </select>
    </div>

            <div class="flex flex-col sm:flex-row justify-between gap-4">
                <button type="submit" name="previous"
                    class="w-full sm:w-auto bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow">⬅
                    Previous</button>
                <button type="submit" name="save"
                    class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-500 text-white px-6 py-2 rounded-lg shadow">💾
                    Save for Later</button>
                <button type="submit" name="continue"
                    class="w-full sm:w-auto bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg shadow">Next
                    ➡</button>
            </div>
        </form>
    </div>

    <script>
        if (localStorage.getItem('form2_done')) {
            Swal.fire({
                icon: 'success',
                title: 'Step 2 Complete!',
                text: 'Your personal info was saved.',
                confirmButtonColor: '#6B21A8'
            });
            localStorage.removeItem('form2_done');
        }

        // Show/hide children field based on marital status
        const maritalSelect = document.getElementById("maritalSelect");
        const childrenWrapper = document.getElementById("children-wrapper");
        const childrenField = document.getElementById("childrenField");
        const spouseWrapper = document.getElementById("spouse-wrapper");
        const spouseField = document.getElementById("spouseField");

        function toggleFields() {
            // Children field logic
            if (maritalSelect.value === "Single") {
                childrenWrapper.style.display = "none";
                childrenField.disabled = true;
                childrenField.value = "0";
                childrenField.required = false;
            } else {
                childrenWrapper.style.display = "block";
                childrenField.disabled = false;
                childrenField.required = true;
            }

            // Spouse field logic
            if (maritalSelect.value === "Married") {
                spouseWrapper.style.display = "block";
                spouseField.disabled = false;
            } else {
                spouseWrapper.style.display = "none";
                spouseField.disabled = true;
                spouseField.value = ""; // Clear the value when hidden
            }
        }

        maritalSelect.addEventListener("change", toggleFields);
        document.addEventListener("DOMContentLoaded", toggleFields);
    </script>
</body>

</html>
<!--<!DOCTYPE html>-->
<!--<html lang="en">-->

<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <meta name="viewport" content="width=device-width, initial-scale=1.0">-->
<!--    <title>Application - Step 2</title>-->
<!--    <script src="https://cdn.tailwindcss.com"></script>-->
<!--    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>-->
<!--    <link rel="icon" type="image/png" href="../assets/img/favicon.png">-->
<!--</head>-->

<!--<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">-->
<!--    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-3xl">-->
<!--        <h2 class="text-xl font-bold text-purple-800 mb-4 text-center">Step 2: Personal Evaluation</h2>-->
<!--<p class="text-xl font-light text-purple-800 mb-4 text-center">The following questions in this section are for counselling purposes and will in no way jeopardize your acceptance into the Theological Seminary.</p>-->
       

<!--        <form method="POST" class="space-y-6">-->
<!--            <?php
// <!--            function selectField($label, $name)-->
// <!--            {-->
// <!--                echo "<div>-->
// <!--                    <label class='block text-sm font-medium text-gray-700 mb-1'>{$label}<span class='text-red-500'>*</span></label>-->
// <!--                    <select name='{$name}' required class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>-->
// <!--                        <option value=''>Choose an Option</option>-->
// <!--                        <option value='Yes'>Yes</option>-->
// <!--                        <option value='No'>No</option>-->
// <!--                    </select>-->
// <!--                </div>";-->
// <!--            }-->
            ?>-->

<!--            <div class="grid sm:grid-cols-2 gap-4">-->
                <!-- Marital Status -->
<!--                <div>-->
<!--                    <label class='block text-sm font-medium text-gray-700 mb-1'>Marital Status<span-->
<!--                            class='text-red-500'>*</span></label>-->
<!--                    <select name='maritalstatus' id="maritalSelect" required-->
<!--                        class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>-->
<!--                        <option value=''>Marital Status</option>-->
<!--                        <option value='Married'>Married</option>-->
<!--                        <option value='Divorced'>Divorced</option>-->
<!--                        <option value='Remarried'>Remarried</option>-->
<!--                        <option value='Separated'>Separated</option>-->
<!--                        <option value='Single'>Single</option>-->
<!--                        <option value='Widowed'>Widowed</option>-->
<!--                    </select>-->
<!--                </div>-->

                <!-- Children (Conditional) -->
<!--                <div id="children-wrapper">-->
<!--                    <label class='block text-sm font-medium text-gray-700 mb-1'>Number of Children<span-->
<!--                            class='text-red-500'>*</span></label>-->
<!--                    <input name='children' id="childrenField" type='number' min='0' required-->
<!--                        class='w-full px-4 py-2 border rounded-md focus:ring-purple-600 focus:border-purple-600'>-->
<!--                </div>-->
<!--            </div>-->

<!--            <?php
// <!--            selectField("Do you have any physical, mental or emotional disabilities?", "dhealth");-->
// <!--            selectField("Have you ever been on academic or disciplinary probation?", "disciplinary");-->
// <!--            selectField("Have you ever been under mental health care?", "mental_health");-->
// <!--            selectField("Have you ever declared bankruptcy or legal action against your finances?", "fbank");-->
// <!--            selectField("Have you ever used illegal drugs or abused alcohol?", "drug");-->
// <!--            selectField("Have you ever been dismissed/fired from a job?", "employment");-->
// <!--            selectField("Have you ever been convicted of a felony or dishonorably discharged?", "felony");-->
// <!--            selectField("Have you ever been accused of sexually related misconduct?", "smisconduct");-->
// <!--            selectField("Are you a registered sex offender or convicted of a sex offence?", "soffence");-->
// <!--            selectField("Have you ever been divorced?", "divource");-->
// <!--            selectField("If married, is your spouse in agreement with this program?", "spouse");-->
           ?>

<!--            <div class="flex flex-col sm:flex-row justify-between gap-4">-->
<!--                <button type="submit" name="previous"-->
<!--                    class="w-full sm:w-auto bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow">⬅-->
<!--                    Previous</button>-->
<!--                <button type="submit" name="save"-->
<!--                    class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-500 text-white px-6 py-2 rounded-lg shadow">💾-->
<!--                    Save for Later</button>-->
<!--                <button type="submit" name="continue"-->
<!--                    class="w-full sm:w-auto bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg shadow">Next-->
<!--                    ➡</button>-->
<!--            </div>-->
<!--        </form>-->
<!--    </div>-->

    <!-- SweetAlert for Save Notification -->
<!--    <script>-->
<!--        if (localStorage.getItem('form2_done')) {-->
<!--            Swal.fire({-->
<!--                icon: 'success',-->
<!--                title: 'Step 2 Complete!',-->
<!--                text: 'Your personal info was saved.',-->
<!--                confirmButtonColor: '#6B21A8'-->
<!--            });-->
<!--            localStorage.removeItem('form2_done');-->
<!--        }-->

        <!--// Show/hide children field based on marital status-->
<!--        const maritalSelect = document.getElementById("maritalSelect");-->
<!--        const childrenWrapper = document.getElementById("children-wrapper");-->
<!--        const childrenField = document.getElementById("childrenField");-->

<!--        function toggleChildren() {-->
<!--            if (maritalSelect.value === "Single") {-->
<!--                childrenWrapper.style.display = "none";-->
<!--                childrenField.disabled = true;-->
<!--                childrenField.value = "0";-->
<!--            } else {-->
<!--                childrenWrapper.style.display = "block";-->
<!--                childrenField.disabled = false;-->
<!--            }-->
<!--        }-->

<!--        maritalSelect.addEventListener("change", toggleChildren);-->
<!--        document.addEventListener("DOMContentLoaded", toggleChildren);-->
<!--    </script>-->
<!--</body>-->

<!--</html>-->