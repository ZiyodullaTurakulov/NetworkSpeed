document.addEventListener('DOMContentLoaded', function() {
    // Elementlarni olish
    const speedElement = document.getElementById('speed');
    const downloadElement = document.getElementById('download-speed');
    const uploadElement = document.getElementById('upload-speed');
    const pingElement = document.getElementById('ping');
    const historyTable = document.getElementById('history-table');
    const needle = document.querySelector('.speed-needle');
    const testButton = document.getElementById('test-device');
    const addressInput = document.getElementById('address-input');
    const addressType = document.getElementById('address-type');
    const deviceIP = document.getElementById('device-ip');
    const deviceMAC = document.getElementById('device-mac');
    const deviceHostname = document.getElementById('device-hostname');
    const deviceStatus = document.getElementById('device-status');
    
    // Chart.js sozlamalari
    const ctx = document.getElementById('speedChart').getContext('2d');
    const speedChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Download Speed (Mbps)',
                borderColor: '#08fdd8',
                backgroundColor: 'rgba(8, 253, 216, 0.1)',
                borderWidth: 2,
                data: [],
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#08fdd8',
                        font: {
                            family: 'Orbitron'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(8, 253, 216, 0.1)'
                    },
                    ticks: {
                        color: '#08fdd8'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(8, 253, 216, 0.1)'
                    },
                    ticks: {
                        color: '#08fdd8'
                    }
                }
            }
        }
    });

    // Manzil formatini tekshirish
    function validateAddress(address, type) {
        if (type === 'ip') {
            const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ipRegex.test(address)) return false;
            
            const parts = address.split('.');
            return parts.every(part => {
                const num = parseInt(part, 10);
                return num >= 0 && num <= 255;
            });
        } else {
            const macRegex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
            return macRegex.test(address);
        }
    }

    // Qurilmani tekshirish
    async function testDevice() {
        const address = addressInput.value.trim();
        const type = addressType.value;

        if (!address) {
            alert('Iltimos, manzilni kiriting');
            return;
        }

        if (!validateAddress(address, type)) {
            alert(`Noto'g'ri ${type.toUpperCase()} manzil formati`);
            return;
        }

        try {
            testButton.disabled = true;
            testButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            
            const response = await fetch('network_scanner.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    address: address,
                    type: type
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Test jarayonida xatolik');
            }

            updateDeviceInfo(data.data);
            updateSpeedDisplay(data.data);
            updateSpeedChart(data.data.download_speed);
            await updateHistory();

        } catch (error) {
            console.error('Test xatosi:', error);
            alert('Xatolik: ' + error.message);
            resetDeviceInfo();
        } finally {
            testButton.disabled = false;
            testButton.innerHTML = '<i class="fas fa-play"></i> Testni Boshlash';
        }
    }

    // Qurilma ma'lumotlarini yangilash
    function updateDeviceInfo(data) {
        deviceIP.textContent = data.ip || '-';
        deviceMAC.textContent = data.mac || '-';
        deviceHostname.textContent = data.hostname || '-';
        deviceStatus.textContent = data.status;
        deviceStatus.className = `status-${data.status}`;
    }

    // Qurilma ma'lumotlarini tozalash
    function resetDeviceInfo() {
        deviceIP.textContent = '-';
        deviceMAC.textContent = '-';
        deviceHostname.textContent = '-';
        deviceStatus.textContent = '-';
        deviceStatus.className = '';
    }

    // Tezlik ko'rsatkichlarini yangilash
    function updateSpeedDisplay(data) {
        speedElement.textContent = Math.round(data.download_speed);
        downloadElement.textContent = `${data.download_speed} Mbps`;
        uploadElement.textContent = `${data.upload_speed} Mbps`;
        pingElement.textContent = `${data.ping} ms`;
        updateSpeedometer(data.download_speed);
    }

    // Spidometr ko'rsatkichini yangilash
    function updateSpeedometer(speed) {
        const maxSpeed = 100;
        const rotation = Math.min((speed / maxSpeed) * 180, 180);
        needle.style.transform = `translateX(-50%) rotate(${rotation}deg)`;
    }

    // Grafik yangilash
    function updateSpeedChart(speed) {
        speedChart.data.labels.push(new Date().toLocaleTimeString());
        speedChart.data.datasets[0].data.push(speed);
        
        if (speedChart.data.labels.length > 10) {
            speedChart.data.labels.shift();
            speedChart.data.datasets[0].data.shift();
        }
        
        speedChart.update();
    }

    // Test tarixini yangilash
    async function updateHistory() {
        try {
            const response = await fetch('get_history.php');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Tarix ma\'lumotlarini olishda xatolik');
            }

            historyTable.innerHTML = '';
            
            if (data.history.length === 0) {
                historyTable.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center">Test tarixi mavjud emas</td>
                    </tr>
                `;
                return;
            }

            data.history.forEach(test => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${new Date(test.test_time).toLocaleString('uz-UZ')}</td>
                    <td>${test.ip_address || '-'}</td>
                    <td>${test.mac_address || '-'}</td>
                    <td>${test.hostname || '-'}</td>
                    <td class="text-success">${test.download_speed} Mbps</td>
                    <td class="text-info">${test.upload_speed} Mbps</td>
                    <td class="text-warning">${test.ping} ms</td>
                    <td class="status-${test.status}">${test.status}</td>
                `;
                historyTable.appendChild(row);
            });
        } catch (error) {
            console.error('Tarix yangilashda xatolik:', error);
            historyTable.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        Ma'lumotlarni yuklashda xatolik yuz berdi
                    </td>
                </tr>
            `;
        }
    }

    // Test tugmasiga hodisa qo'shish
    testButton.addEventListener('click', testDevice);

    // Sahifa yuklanganda tarixni ko'rsatish
    updateHistory();

    // Har 30 sekundda tarixni avtomatik yangilash
    setInterval(updateHistory, 30000);
});