<?php
session_start();

define('CLOUD_NAME',   'dmbckag42');
define('API_KEY',      '383436453913485');
define('API_SECRET',   'AHKl0FLAzfWlY4ty2OZSo1DLzJM');
define('BULK_API_URL', 'https://rgw.apis.ng/abia/sandbox/rgw.apis.ng/abia/sandbox/v1/BulkBillPayment');
define('CLIENT_ID',    '130d455797627474d441d98732ddb644');
define('MERCHANT_KEY', '1754309023682');
define('LOG_FILE',     __DIR__ . '/invoice.log');


$test = cloudinary(
  "https://api.cloudinary.com/v1_1/" . CLOUD_NAME . "/folders"
);
file_put_contents(LOG_FILE, json_encode($test, JSON_PRETTY_PRINT));

function logMsg($m)
{
    file_put_contents(LOG_FILE, "[" . date('c') . "] $m\n", FILE_APPEND);
}

function cloudinary($url, $post = null)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => API_KEY . ':' . API_SECRET,
        CURLOPT_HTTPHEADER     => ['Content-Type:application/json'],
    ]);

    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }

    $r = curl_exec($ch);

    if ($r === false) {
        logMsg('Cloudinary cURL error: ' . curl_error($ch));
        curl_close($ch);
        return [];
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        logMsg("Cloudinary HTTP $status: $r");
        return [];
    }

    return json_decode($r, true) ?: [];
}



function sendBulk(array $payload)
{
    logMsg("BulkPayload: " . json_encode($payload));

    $ch = curl_init(BULK_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type:application/json',
            'Accept:application/json',
            'X-IBM-Client-Id:' . CLIENT_ID,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);

    $r = curl_exec($ch);

    if ($r === false) {
        logMsg('RGW cURL error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    logMsg("BulkResp($code): $r");

    return json_decode($r, true);
}

if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
    $file = $_SESSION['file_to_serve'] ?? null;

    if (!$file) {
        echo "<p class='text-red-600 text-center mt-10'>
                Session expired. Please reselect your document.
              </p>";
        exit;
    }

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Payment Successful</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-xl shadow-lg max-w-md text-center">
  <h2 class="text-2xl font-bold text-green-700 mb-4">Payment Successful </h2>
  <p class="text-gray-600 mb-6">
    Your payment was successful. Click below to download your document.
  </p>

  <a href="index.php?serve=1"
     class="inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
     Download Document
  </a>
</div>

</body>
</html>
HTML;
    exit;
}



if (isset($_GET['pay_for']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['file_to_serve'] = urldecode($_GET['pay_for']);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enter Payment Details</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="https://yzub7xjzmf.ufs.sh/f/p5WCAJ95HVcjOjruKYVLETORg95fNF2JACMGoxnzqIHQPmeW">
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col min-h-screen">

  <header class="bg-white border-b border-gray-200 shadow-sm p-4 sm:p-6">
    <div class="max-w-6xl mx-auto flex flex-wrap items-center gap-4">
        <img src="https://yzub7xjzmf.ufs.sh/f/p5WCAJ95HVcjOjruKYVLETORg95fNF2JACMGoxnzqIHQPmeW" class="h-10 sm:h-12" alt="Logo">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Ministry of Justice Document Library</h1>
            <p class="text-xs sm:text-sm text-gray-500">Government of Abia State</p>
        </div>
    </div>
  </header>

  <main class="flex justify-center items-center flex-1 px-4 py-8">
    <div class="bg-white w-full max-w-md p-6 sm:p-8 rounded-2xl shadow-lg">
      <h2 class="text-lg sm:text-xl font-bold text-center mb-6">Pay to Access Document</h2>
      <form method="post" class="space-y-6">
        <input type="hidden" name="pay_for" value="{$_GET['pay_for']}">

        <div>
          <label class="block text-sm font-medium mb-1" for="abssin">ABSSIN</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <i data-feather="user"></i>
            </span>
            <input id="abssin" name="abssin" required type="text"
              class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-red-200 focus:outline-none" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="fullName">Full Name</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <i data-feather="user-check"></i>
            </span>
            <input id="fullName" name="fullName" required type="text"
              class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-red-200 focus:outline-none" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="phoneNumber">Phone Number</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <i data-feather="phone"></i>
            </span>
            <input id="phoneNumber" name="phoneNumber" required type="tel"
              class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-red-200 focus:outline-none" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="emailAddress">Email Address</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <i data-feather="mail"></i>
            </span>
            <input id="emailAddress" name="emailAddress" required type="email"
              class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-red-200 focus:outline-none" />
          </div>
        </div>

        <button type="submit"
          class="w-full bg-green-600 text-white py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition">
          Proceed to Pay
        </button>
      </form>
    </div>
  </main>

  <script>feather.replace();</script>
</body>
</html>
HTML;
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_for'])) {
    $payFor = filter_input(INPUT_POST, 'pay_for', FILTER_UNSAFE_RAW);
    $_SESSION['file_to_serve'] = is_string($payFor) ? urldecode($payFor) : '';

    $abssinRaw = filter_input(INPUT_POST, 'abssin', FILTER_UNSAFE_RAW);
    $abssin    = is_string($abssinRaw) ? preg_replace('/\D+/', '', $abssinRaw) : '';

    $fullNameRaw = filter_input(INPUT_POST, 'fullName', FILTER_UNSAFE_RAW);
    $fullName    = is_string($fullNameRaw) ? trim($fullNameRaw) : '';
    $fullName    = preg_replace('/[^\p{L}\s\.\'\-]/u', '', $fullName);
    $fullName    = preg_replace('/\s{2,}/', ' ', $fullName);

    $phoneRaw    = filter_input(INPUT_POST, 'phoneNumber', FILTER_UNSAFE_RAW);
    $phoneNumber = is_string($phoneRaw) ? preg_replace('/[^0-9\+]/', '', $phoneRaw) : '';
    if (strlen($phoneNumber) > 20) { $phoneNumber = substr($phoneNumber, 0, 20); }

    $emailAddress = filter_input(INPUT_POST, 'emailAddress', FILTER_VALIDATE_EMAIL) ?: '';

    $payload = [
        'abssin'       => (string)$abssin,
        'fullName'     => $fullName,
        'phoneNumber'  => (int)$phoneNumber,
        'emailAddress' => $emailAddress,
        'bills'        => [[
            'ministryAgency' => '15102001',
            'revenueItem'   => '15102001-12060047',
            'amount'        => 1500
        ]],
        'agentEmail'   => $emailAddress,
        'merchant_key' => MERCHANT_KEY,
        'year'         => '2025',
        'channel'      => 'Bank',
        'type'         => 'CDN',
        'redirectUrl' => '',


    ];

    $res = sendBulk($payload);

    if (!$res || !isset($res['payment_ref'])) {
    echo "<p class='text-center text-red-600 mt-6'>
            Payment initialization failed. Please try again.
          </p>";
    exit;
}

$pr = $res['payment_ref'];
// $gatewayUrl = "https://sandboxportalgateway.abiapay.com/$pr/" . MERCHANT_KEY;
$gatewayUrl = "https://sandboxportal.abiapay.ng/instant-payment/pay-with-bill-ref?id=$pr";

// header("Location: $gatewayUrl");
header("Location: index.php?wait_for_payment=$pr");
exit;

}



if (isset($_GET['wait_for_payment'])):
    $ref = $_GET['wait_for_payment'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Waiting for Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-xl shadow-lg max-w-md text-center">
    <h2 class="text-2xl font-bold text-green-700 mb-4">Complete Your Payment</h2>
    <p class="text-gray-600 mb-6">
        Please complete the payment in the new tab. This page will automatically detect when your payment is successful.
    </p>
    <a href="https://sandboxportal.abiapay.ng/instant-payment/pay-with-bill-ref?id=<?= $ref ?>" target="_blank"
       class="inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
       Open Payment Page
    </a>
</div>

<script>
const paymentRef = "<?= $ref ?>";

async function checkPayment() {
    try {
        const res = await fetch('check_payment.php?ref=' + paymentRef);
        const data = await res.json();
        if (data.status === 'success') {
            window.location.href = "index.php?serve=1";
        } else {
            setTimeout(checkPayment, 3000);
        }
    } catch (e) {
        console.error(e);
        setTimeout(checkPayment, 5000);
    }
}

checkPayment();
</script>

</body>
</html>
<?php
exit;
endif;



if (isset($_GET['serve'])) {
    if (!isset($_SESSION['paid']) || $_SESSION['paid'] !== true) {
        die("Unauthorized access. Please complete payment first.");
    }

    $url = $_SESSION['file_to_serve'] ?? null;

    if (!$url) {
        die("No file selected.");
    }

    if (strpos($url, 'cloudinary.com') !== false) {
        $downloadUrl = str_replace('/upload/', '/upload/fl_attachment/', $url);
        
        
        header('Location: ' . $downloadUrl);
    } else {
        header('Location: ' . $url);
    }
    exit;
}

$base    = 'abia_moj_library';
$folder  = $_GET['folder'] ?? $base;
$folders = cloudinary("https://api.cloudinary.com/v1_1/" . CLOUD_NAME . "/folders/$folder")['folders'] ?? [];
$files   = cloudinary("https://api.cloudinary.com/v1_1/" . CLOUD_NAME . "/resources/search", [
    'expression' => "folder:\"$folder\"",
    'max_results' => 100
])['resources'] ?? [];

$foldersResp = cloudinary(
  "https://api.cloudinary.com/v1_1/" . CLOUD_NAME . "/folders"
);
logMsg('FOLDERS ROOT: ' . json_encode($foldersResp));

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Library</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="https://yzub7xjzmf.ufs.sh/f/p5WCAJ95HVcjOjruKYVLETORg95fNF2JACMGoxnzqIHQPmeW">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 text-gray-800">
    
    <header class="bg-white border-b border-gray-200 shadow-sm p-4 sm:p-6">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-4">
            <img src="https://yzub7xjzmf.ufs.sh/f/p5WCAJ95HVcjOjruKYVLETORg95fNF2JACMGoxnzqIHQPmeW" class="h-12" alt="Logo">
            <div class="text-center sm:text-left">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Ministry of Justice Document Library</h1>
                <p class="text-sm text-gray-500">Government of Abia State</p>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4 sm:p-6 space-y-8">
        
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <?php if ($folder !== $base):
                $parent = dirname($folder) === '.' ? $base : dirname($folder);
            ?>
                <a href="?folder=<?= urlencode($parent) ?>" class="text-green-700 text-sm hover:underline">&larr; Back to <?= htmlspecialchars(basename($parent)) ?></a>
            <?php endif; ?>

            <div class="relative w-full sm:w-64">
                <input id="searchBox" type="text" placeholder="Search documents..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-red-200 focus:outline-none w-full">
                <span class="absolute left-3 top-2.5 text-gray-400">
                    <i data-feather="search"></i>
                </span>
            </div>
        </div>

        <?php if ($folders): ?>
            <section>
                <h2 class="text-lg sm:text-xl font-semibold mb-4 text-red-800">Folders</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6" id="folderGrid">
                    <?php foreach ($folders as $f):
                        $p = $f['path'];
                        $label = ucwords(str_replace(['_', '-'], ' ', basename($p)));
                    ?>
                        <div class="bg-white p-4 rounded-xl shadow flex flex-col">
                            <div class="flex items-center mb-4 text-base sm:text-lg font-medium">
                                <i data-feather="folder" class="mr-2 text-yellow-500"></i>
                                <span class="truncate" title="<?= $label ?>"><?= $label ?></span>
                            </div>
                            <a href="?folder=<?= urlencode($p) ?>" class="mt-auto bg-red-800 text-white py-2 rounded hover:bg-red-900 text-center text-sm sm:text-base">Open</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($files): ?>
            <?php
            $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
            $perPage = 9;
            $start = ($page - 1) * $perPage;
            $totalFiles = count($files);
            $totalPages = ceil($totalFiles / $perPage);
            $filesPage = array_slice($files, $start, $perPage);
            ?>

            <section>
                <h2 class="text-lg sm:text-2xl font-bold text-gray-800 mb-6 border-b pb-2 border-gray-300">
                    Files in <?= htmlspecialchars(basename($folder)) ?>
                </h2>

                <div class="grid gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3" id="fileGrid">
                    <?php foreach ($filesPage as $f):
                        $url = $f['secure_url'];
                        $id = urlencode($url);
                        $raw = basename($f['public_id']);
                        $name = ucwords(str_replace(['_', '-'], ' ', $raw));
                    ?>
                        <div class="bg-white border border-gray-200 shadow-sm hover:shadow-lg transition-all rounded-xl p-4 flex flex-col">
                            <div class="flex items-start gap-2 mb-3">
                                <i data-feather="file-text" class="text-blue-500 w-5 h-5 mt-1 shrink-0"></i>
                                <span class="text-sm sm:text-base font-medium text-gray-700 break-words leading-snug max-w-full"
                                      title="<?= $name ?>">
                                    <?= $name ?>
                                </span>
                            </div>

                            <div class="mt-auto">
                                <a href="?pay_for=<?= $id ?>" 
                                   class="block text-xs sm:text-sm text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-lg transition">
                                    Pay & Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="mt-8 flex flex-wrap justify-center items-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?folder=<?= urlencode($folder) ?>&page=<?= $page - 1 ?>" class="px-3 py-1 rounded border bg-white text-gray-800 hover:bg-gray-200 text-sm">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?folder=<?= urlencode($folder) ?>&page=<?= $i ?>"
                               class="px-3 py-1 rounded border text-sm <?= $i == $page ? 'bg-red-800 text-white' : 'bg-white text-gray-800 hover:bg-gray-200' ?>">
                               <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?folder=<?= urlencode($folder) ?>&page=<?= $page + 1 ?>" class="px-3 py-1 rounded border bg-white text-gray-800 hover:bg-gray-200 text-sm">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </main>

    <script>
        feather.replace();
        const searchBox = document.getElementById('searchBox');
        const folderGrid = document.getElementById('folderGrid');
        const fileGrid = document.getElementById('fileGrid');

        searchBox.addEventListener('input', () => {
            const term = searchBox.value.toLowerCase();
            [folderGrid, fileGrid].forEach(grid => {
                if (!grid) return;
                const cards = grid.querySelectorAll('.flex.flex-col');
                cards.forEach(card => {
                    const text = card.innerText.toLowerCase();
                    card.style.display = text.includes(term) ? '' : 'none';
                });
            });

            const newUrl = new URL(window.location);
            newUrl.searchParams.set('search', searchBox.value);
            window.history.pushState({}, '', newUrl);
        });
    </script>
</body>
</html>
