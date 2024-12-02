<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

class NetworkScanner {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new mysqli("localhost", "root", "", "local_speed");
            if ($this->conn->connect_error) {
                throw new Exception("Ma'lumotlar bazasiga ulanishda xatolik: " . $this->conn->connect_error);
            }
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            throw new Exception("Ma'lumotlar bazasi xatosi: " . $e->getMessage());
        }
    }

    public function checkDevice($address, $type) {
        try {
            if ($type === 'ip') {
                return $this->checkByIP($address);
            } else {
                return $this->checkByMAC($address);
            }
        } catch (Exception $e) {
            throw new Exception("Qurilmani tekshirishda xatolik: " . $e->getMessage());
        }
    }

    private function checkByIP($ip) {
        // IP manzil validatsiyasi
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new Exception("Noto'g'ri IP manzil formati");
        }

        // Ping test
        $isOnline = $this->pingDevice($ip);
        $mac = $this->getMacAddress($ip);
        $hostname = $this->getHostname($ip);

        // Tezlik testi
        $speeds = $this->measureSpeed($ip);

        return array_merge([
            'ip' => $ip,
            'mac' => $mac,
            'hostname' => $hostname,
            'status' => $isOnline ? 'online' : 'offline'
        ], $speeds);
    }

    private function checkByMAC($mac) {
        // MAC manzil validatsiyasi
        if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac)) {
            throw new Exception("Noto'g'ri MAC manzil formati");
        }

        // MAC orqali IP topish
        $ip = $this->getIPByMAC($mac);
        if (!$ip) {
            throw new Exception("Bu MAC manzil uchun IP topilmadi");
        }

        return $this->checkByIP($ip);
    }

    private function pingDevice($ip) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = sprintf('ping -n 1 -w 100 %s', escapeshellarg($ip));
        } else {
            $cmd = sprintf('ping -c 1 -W 1 %s', escapeshellarg($ip));
        }
        
        exec($cmd, $output, $return);
        return $return === 0;
    }

    private function getMacAddress($ip) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = sprintf('arp -a %s', escapeshellarg($ip));
            exec($cmd, $output);
            foreach ($output as $line) {
                if (strpos($line, $ip) !== false) {
                    if (preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $line, $matches)) {
                        return $matches[0];
                    }
                }
            }
        } else {
            $cmd = sprintf('arp -n %s', escapeshellarg($ip));
            exec($cmd, $output);
            foreach ($output as $line) {
                if (strpos($line, $ip) !== false) {
                    if (preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $line, $matches)) {
                        return $matches[0];
                    }
                }
            }
        }
        return null;
    }

    private function getIPByMAC($mac) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = 'arp -a';
            exec($cmd, $output);
            foreach ($output as $line) {
                if (strpos($line, str_replace('-', ':', $mac)) !== false) {
                    if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $line, $matches)) {
                        return $matches[1];
                    }
                }
            }
        } else {
            $cmd = 'arp -n';
            exec($cmd, $output);
            foreach ($output as $line) {
                if (strpos($line, $mac) !== false) {
                    if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $line, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }
        return null;
    }

    private function getHostname($ip) {
        $hostname = gethostbyaddr($ip);
        return $hostname !== $ip ? $hostname : null;
    }

    private function measureSpeed($ip) {
        // Bu yerda haqiqiy tezlik o'lchash mantiqini qo'shishingiz mumkin
        // Hozircha namuna qiymatlar qaytaramiz
        return [
            'download_speed' => rand(10, 100),
            'upload_speed' => rand(5, 50),
            'ping' => rand(1, 100)
        ];
    }

    public function saveResults($data) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO network_logs (ip_address, mac_address, hostname, download_speed, upload_speed, ping, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                throw new Exception("SQL so'rovini tayyorlashda xatolik");
            }

            $stmt->bind_param("sssddds", 
                $data['ip'],
                $data['mac'],
                $data['hostname'],
                $data['download_speed'],
                $data['upload_speed'],
                $data['ping'],
                $data['status']
            );

            if (!$stmt->execute()) {
                throw new Exception("Ma'lumotlarni saqlashda xatolik");
            }

            $stmt->close();
        } catch (Exception $e) {
            throw new Exception("Natijalarni saqlashda xatolik: " . $e->getMessage());
        }
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

try {
    $scanner = new NetworkScanner();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['address']) || !isset($input['type'])) {
            throw new Exception("Noto'g'ri so'rov ma'lumotlari");
        }

        $address = trim($input['address']);
        $type = trim($input['type']);

        if (empty($address)) {
            throw new Exception("Manzil kiritilmagan");
        }

        if (!in_array($type, ['ip', 'mac'])) {
            throw new Exception("Noto'g'ri manzil turi");
        }

        $result = $scanner->checkDevice($address, $type);
        $scanner->saveResults($result);

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}