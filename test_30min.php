<!DOCTYPE html>
<html>
<head>
    <title>Test 30-Minute Restriction</title>
    <style>
        .schedule-card { 
            border: 1px solid #ccc; 
            padding: 10px; 
            margin: 5px; 
            cursor: pointer; 
        }
        .schedule-card-disabled { 
            opacity: 0.4; 
            cursor: not-allowed; 
            background: #ffcccc; 
        }
    </style>
</head>
<body>
    <h1>Test 30-Minute Booking Restriction</h1>
    <p>Current time: <span id="current-time"></span></p>
    <input type="date" id="test-date" value="">
    <button onclick="testGenerateSchedules()">Generate Test Schedules</button>
    <div id="schedule-cards"></div>

    <script>
        // Test function to check 30-minute logic
        function isShowtimeWithin30Minutes(dateString, timeString) {
            const now = new Date();
            const [startTimeStr] = timeString.split(' - ');
            const showtimeDateTime = new Date(`${dateString} ${startTimeStr}`);
            const diffMinutes = (showtimeDateTime.getTime() - now.getTime()) / (1000 * 60);
            return diffMinutes < 30;
        }

        function testGenerateSchedules() {
            const date = document.getElementById('test-date').value;
            const schedulesContainer = document.getElementById('schedule-cards');
            const schedules = [
                { time: '2:30 PM - 4:25 PM', cinema: 'Cinema: 1D' },
                { time: '5:15 PM - 7:10 PM', cinema: 'Cinema: 1D' },
                { time: '8:00 PM - 9:55 PM', cinema: 'Cinema: IMAX' }
            ];

            let html = '';
            schedules.forEach((schedule) => {
                const isDisabled = isShowtimeWithin30Minutes(date, schedule.time);
                const disabledClass = isDisabled ? 'schedule-card-disabled' : '';
                
                html += `
                <div class="schedule-card ${disabledClass}">
                    <div class="schedule-time">${schedule.time}</div>
                    <div class="schedule-cinema">${schedule.cinema}</div>
                    <div class="schedule-seats">
                        <span>${isDisabled ? '🚫' : '👤'}</span>
                        <span>${isDisabled ? 'Booking closed (less than 30 min)' : 'Available'}</span>
                    </div>
                </div>`;
            });

            schedulesContainer.innerHTML = html;
        }

        // Update current time and set default date
        function updateTime() {
            document.getElementById('current-time').textContent = new Date().toLocaleString();
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // Set today's date as default
        document.getElementById('test-date').value = new Date().toISOString().split('T')[0];
        testGenerateSchedules();
    </script>
</body>
</html>
