<?php
session_start();

class BankAccount {
    private $accountNumber;
    private $ownerName;
    private $balance;
    private $transactions;

    public function __construct($accountNumber, $ownerName, $initialBalance = 0) {
        $this->accountNumber = $accountNumber;
        $this->ownerName = $ownerName;
        $this->balance = $initialBalance;
        $this->transactions = [];
    }

    public function getAccountNumber() { return $this->accountNumber; }
    public function getOwnerName() { return $this->ownerName; }
    public function getBalance() { return $this->balance; }
    public function getTransactions() { return $this->transactions; }

    public function deposit($amount, $description = "Wpłata") {
        if ($amount <= 0) return false;
        
        $this->balance += $amount;
        $this->addTransaction($amount, $description);
        return true;
    }

    public function withdraw($amount, $description = "Wypłata") {
        if ($amount <= 0 || $amount > $this->balance) return false;
        
        $this->balance -= $amount;
        $this->addTransaction(-$amount, $description);
        return true;
    }

    public function transfer($amount, $targetAccount, $description = "Przelew") {
        if ($amount <= 0 || $amount > $this->balance) return false;
        
        $this->withdraw($amount, $description . " do " . $targetAccount->getAccountNumber());
        $targetAccount->deposit($amount, "Przelew od " . $this->accountNumber);
        return true;
    }

    private function addTransaction($amount, $description) {
        $this->transactions[] = [
            'date' => date('Y-m-d H:i:s'),
            'amount' => $amount,
            'description' => $description,
            'balance' => $this->balance
        ];
    }
}

class Bank {
    private $accounts = [];

    public function createAccount($accountNumber, $ownerName, $initialBalance = 0) {
        if (isset($this->accounts[$accountNumber])) return false;
        
        $this->accounts[$accountNumber] = new BankAccount($accountNumber, $ownerName, $initialBalance);
        return true;
    }

    public function getAccount($accountNumber) {
        return $this->accounts[$accountNumber] ?? null;
    }

    public function getAccounts() {
        return $this->accounts;
    }
}

if (!isset($_SESSION['bank'])) {
    $bank = new Bank();
    
    $bank->createAccount("12345678", "Jan Kowalski", 5000);
    $bank->createAccount("87654321", "Anna Nowak", 7500);
    $bank->createAccount("13579246", "Piotr Wiśniewski", 2500);
    
    $_SESSION['bank'] = serialize($bank);
} else {
    $bank = unserialize($_SESSION['bank']);
}

$action = $_GET['action'] ?? 'home';
$message = '';
$error = '';
$currentAccount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $accountNumber = $_POST['account_number'];
        $currentAccount = $bank->getAccount($accountNumber);
        
        if ($currentAccount) {
            $_SESSION['current_account'] = $accountNumber;
        } else {
            $error = "Nie znaleziono konta o podanym numerze.";
        }
    } 
    elseif (isset($_POST['deposit'])) {
        $account = $bank->getAccount($_SESSION['current_account']);
        
        if ($account && $account->deposit(floatval($_POST['amount']), $_POST['description'])) {
            $message = "Wpłata zrealizowana pomyślnie.";
            $_SESSION['bank'] = serialize($bank);
        } else {
            $error = "Nie udało się zrealizować wpłaty.";
        }
    } 
    elseif (isset($_POST['withdraw'])) {
        $account = $bank->getAccount($_SESSION['current_account']);
        
        if ($account && $account->withdraw(floatval($_POST['amount']), $_POST['description'])) {
            $message = "Wypłata zrealizowana pomyślnie.";
            $_SESSION['bank'] = serialize($bank);
        } else {
            $error = "Nie udało się zrealizować wypłaty.";
        }
    } 
    elseif (isset($_POST['transfer'])) {
        $sourceAccount = $bank->getAccount($_SESSION['current_account']);
        $targetAccount = $bank->getAccount($_POST['target_account']);
        
        if ($sourceAccount && $targetAccount && 
            $sourceAccount->transfer(floatval($_POST['amount']), $targetAccount, $_POST['description'])) {
            $message = "Przelew zrealizowany pomyślnie.";
            $_SESSION['bank'] = serialize($bank);
        } else {
            $error = "Nie udało się zrealizować przelewu.";
        }
    } 
    elseif (isset($_POST['logout'])) {
        unset($_SESSION['current_account']);
    }
}

if (isset($_SESSION['current_account'])) {
    $currentAccount = $bank->getAccount($_SESSION['current_account']);
}

function formatAmount($amount) {
    return number_format($amount, 2, ',', ' ') . ' PLN';
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Bankowy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .amount-positive {
            color: green;
        }
        .amount-negative {
            color: red;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            margin-right: 5px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Bankowy</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($currentAccount): ?>
            <h2>Witaj, <?php echo $currentAccount->getOwnerName(); ?></h2>
            <p>Numer konta: <?php echo $currentAccount->getAccountNumber(); ?></p>
            <p>Aktualne saldo: <strong><?php echo formatAmount($currentAccount->getBalance()); ?></strong></p>
            
            <div class="tabs">
                <div class="tab active" data-tab="operations">Operacje</div>
                <div class="tab" data-tab="history">Historia transakcji</div>
                <div class="tab" data-tab="settings">Ustawienia</div>
            </div>
            
            <div id="operations" class="tab-content active">
                <h3>Wpłata środków</h3>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="deposit-amount">Kwota:</label>
                        <input type="number" id="deposit-amount" name="amount" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="deposit-description">Opis:</label>
                        <input type="text" id="deposit-description" name="description" value="Wpłata środków">
                    </div>
                    <button type="submit" name="deposit">Wykonaj wpłatę</button>
                </form>
                
                <h3>Wypłata środków</h3>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="withdraw-amount">Kwota:</label>
                        <input type="number" id="withdraw-amount" name="amount" step="0.01" min="0.01" max="<?php echo $currentAccount->getBalance(); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="withdraw-description">Opis:</label>
                        <input type="text" id="withdraw-description" name="description" value="Wypłata środków">
                    </div>
                    <button type="submit" name="withdraw">Wykonaj wypłatę</button>
                </form>
                
                <h3>Wykonaj przelew</h3>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="transfer-target">Numer konta odbiorcy:</label>
                        <select id="transfer-target" name="target_account" required>
                            <option value="">Wybierz konto odbiorcy</option>
                            <?php foreach ($bank->getAccounts() as $account): ?>
                                <?php if ($account->getAccountNumber() != $currentAccount->getAccountNumber()): ?>
                                    <option value="<?php echo $account->getAccountNumber(); ?>">
                                        <?php echo $account->getAccountNumber(); ?> (<?php echo $account->getOwnerName(); ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="transfer-amount">Kwota:</label>
                        <input type="number" id="transfer-amount" name="amount" step="0.01" min="0.01" max="<?php echo $currentAccount->getBalance(); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="transfer-description">Opis:</label>
                        <input type="text" id="transfer-description" name="description" value="Przelew środków">
                    </div>
                    <button type="submit" name="transfer">Wykonaj przelew</button>
                </form>
            </div>
            
            <div id="history" class="tab-content">
                <h3>Historia transakcji</h3>
                <?php if (empty($currentAccount->getTransactions())): ?>
                    <p>Brak transakcji na koncie.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Opis</th>
                                <th>Kwota</th>
                                <th>Saldo po transakcji</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $transactions = $currentAccount->getTransactions();
                            $reversed = array_reverse($transactions);
                            foreach ($reversed as $transaction): 
                                $amountClass = $transaction['amount'] >= 0 ? 'amount-positive' : 'amount-negative';
                            ?>
                                <tr>
                                    <td><?php echo $transaction['date']; ?></td>
                                    <td><?php echo $transaction['description']; ?></td>
                                    <td class="<?php echo $amountClass; ?>">
                                        <?php echo formatAmount($transaction['amount']); ?>
                                    </td>
                                    <td><?php echo formatAmount($transaction['balance']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div id="settings" class="tab-content">
                <h3>Ustawienia konta</h3>
                <form method="post" action="">
                    <button type="submit" name="logout">Wyloguj się</button>
                </form>
            </div>
            
        <?php else: ?>
            <h2>Logowanie do konta</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="account-number">Numer konta:</label>
                    <input type="text" id="account-number" name="account_number" required>
                </div>
                <p>Dostępne konta do testów:</p>
                <ul>
                    <?php foreach ($bank->getAccounts() as $account): ?>
                        <li><?php echo $account->getAccountNumber(); ?> (<?php echo $account->getOwnerName(); ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <button type="submit" name="login">Zaloguj się</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Skrypt do obsługi zakładek
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Usuń klasę 'active' ze wszystkich zakładek i zawartości
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Dodaj klasę 'active' do klikniętej zakładki i powiązanej zawartości
                    this.classList.add('active');
                    document.getElementById(this.getAttribute('data-tab')).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>